<?php
/**
 * Purchase-bill scanning via Gemini's vision API. Sends the uploaded
 * photo/PDF straight through (no separate OCR step) with a strict JSON
 * response schema, so the model returns structured line items directly
 * instead of free text to parse ourselves.
 */
class GeminiHelper {
    public static function isConfigured(): bool {
        return GEMINI_API_KEY !== '';
    }

    /**
     * @return array{supplierName?:string,invoiceNumber?:string,invoiceDate?:string,items:array}|array{error:string}
     */
    public static function scanPurchaseBill(string $fileContent, string $mimeType): array {
        if (!self::isConfigured()) {
            return ['error' => 'AI bill scanning isn\'t set up yet — add a Gemini API key in config/constants.php to enable it.'];
        }

        $schema = [
            'type' => 'OBJECT',
            'properties' => [
                'supplierName'  => ['type' => 'STRING'],
                'invoiceNumber' => ['type' => 'STRING'],
                'invoiceDate'   => ['type' => 'STRING', 'description' => 'YYYY-MM-DD if determinable'],
                'items' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'name'          => ['type' => 'STRING'],
                            'quantity'      => ['type' => 'NUMBER'],
                            'unit'          => ['type' => 'STRING'],
                            // Required, not optional — an earlier version of this exact
                            // feature (in the prior app this was ported from) made this
                            // optional and Gemini would sometimes drop/zero it on larger
                            // bills. Marking it required in the schema stops that.
                            'purchasePrice' => ['type' => 'NUMBER'],
                            'gstPercent'    => ['type' => 'NUMBER'],
                            'hsn'           => ['type' => 'STRING'],
                        ],
                        'required' => ['name', 'quantity', 'purchasePrice'],
                    ],
                ],
            ],
            'required' => ['items'],
        ];

        $prompt = 'This is a photo or scan of a supplier purchase bill/invoice, possibly in a regional Indian '
            . 'language. Extract every line item exactly as billed — do not skip rows, do not invent data. '
            . 'quantity and purchasePrice must be numbers (not strings), purchasePrice is the per-unit rate '
            . 'actually billed for that line, not the line total. If a field truly isn\'t present, omit it.';

        $body = [
            'contents' => [[
                'parts' => [
                    ['inline_data' => ['mime_type' => $mimeType, 'data' => base64_encode($fileContent)]],
                    ['text' => $prompt],
                ],
            ]],
            'generationConfig' => [
                'response_mime_type' => 'application/json',
                'response_schema' => $schema,
            ],
        ];

        $lastError = 'AI scan failed. Please try again.';
        foreach (GEMINI_SCAN_MODELS as $model) {
            for ($attempt = 1; $attempt <= 2; $attempt++) {
                $result = self::callModel($model, $body);
                if (!isset($result['error'])) {
                    return $result;
                }
                if ($result['transient'] ?? false) {
                    usleep(800000 * $attempt); // brief backoff, only for a transient "model overloaded" error
                    continue;
                }
                $lastError = $result['error'];
                break; // non-transient (quota/auth/bad request) — move to the next model, not worth retrying this one
            }
        }
        return ['error' => $lastError];
    }

    /** @return array{error?:string,transient?:bool}|array (parsed bill data on success) */
    private static function callModel(string $model, array $body): array {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . urlencode(GEMINI_API_KEY);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_TIMEOUT => 45,
        ]);
        $raw = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            return ['error' => 'Could not reach Gemini: ' . $curlError, 'transient' => true];
        }
        if ($httpCode === 429) {
            return ['error' => 'This Gemini API key has hit its quota for now (common on free-tier keys — as low as 20-50 requests/day per model). Try again later, or use a different key.'];
        }
        if ($httpCode === 503 || stripos($raw, 'UNAVAILABLE') !== false) {
            return ['error' => 'Gemini is temporarily overloaded.', 'transient' => true];
        }
        if ($httpCode !== 200) {
            return ['error' => "Gemini returned an error (HTTP {$httpCode})."];
        }

        $decoded = json_decode($raw, true);
        $text = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? null;
        if (!$text) {
            return ['error' => 'Gemini returned an empty response.'];
        }
        $parsed = json_decode($text, true);
        if (!is_array($parsed) || empty($parsed['items'])) {
            return ['error' => 'No line items found in the scanned bill — try a clearer photo.'];
        }
        return $parsed;
    }
}
