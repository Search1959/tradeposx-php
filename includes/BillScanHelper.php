<?php
/**
 * Purchase-bill scanning via OCR.space (free text extraction, 25,000 pages/month)
 * + Groq (free, fast text model) to structure the raw OCR text into line items —
 * with Claude vision as an automatic paid fallback ONLY when that free path fails
 * outright or comes back with no usable quantity/price data. Replaces GeminiHelper
 * as the active scan pipeline — Gemini's free tier caps out at 20-50 requests/day,
 * which kept getting exhausted between testing and real shop use. Same public
 * interface (isConfigured() / scanPurchaseBill()) as GeminiHelper, so
 * PurchaseController::scanAction() and JobCardController::scanPartsAction() don't
 * need to change at all, just which helper they call.
 */
class BillScanHelper {
    public static function isConfigured(): bool {
        return (OCR_SPACE_API_KEY !== '' && GROQ_API_KEY !== '') || CLAUDE_API_KEY !== '';
    }

    /** Validates an uploaded bill file and normalizes it to a (content, mimeType)
     * pair neither OCR.space nor Claude's vision API will reject. The one real
     * format gap: iPhones save photos as HEIC by default, which neither service
     * accepts, and the browser-side canvas compression (resizeImageIfNeeded in
     * the scan views) can silently pass a HEIC file straight through unconverted
     * when the browser itself can't decode it into a canvas — so this has to be
     * caught server-side too, with an actionable fix instead of a bare
     * "unsupported file type" that gives the user nothing to act on.
     * @return array{content:string,mimeType:string}|array{error:string} */
    public static function readUploadedBill(array $file): array {
        if (empty($file['tmp_name']) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['error' => 'Please choose a photo or PDF of the bill.'];
        }
        $mimeType = mime_content_type($file['tmp_name']);
        $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        // Some Android browsers report an empty/generic type for HEIC and some
        // PHP/libmagic builds misidentify it as a QuickTime/MP4 container (HEIC
        // shares ISOBMFF framing with both) — the file extension catches those.
        $isHeic = in_array($mimeType, ['image/heic', 'image/heif'], true) || in_array($ext, ['heic', 'heif'], true);

        if (in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'], true)) {
            return ['content' => file_get_contents($file['tmp_name']), 'mimeType' => $mimeType];
        }

        if ($isHeic) {
            if (extension_loaded('imagick')) {
                try {
                    $image = new Imagick($file['tmp_name']);
                    $image->setImageFormat('jpeg');
                    $image->setImageCompressionQuality(85);
                    return ['content' => $image->getImageBlob(), 'mimeType' => 'image/jpeg'];
                } catch (Throwable $e) {
                    error_log('BillScanHelper: HEIC conversion via Imagick failed: ' . $e->getMessage());
                    // fall through to the actionable HEIC error below
                }
            }
            return ['error' => 'That\'s an iPhone HEIC photo, which this server can\'t read yet. Either open your '
                . 'phone\'s Settings → Camera → Formats and switch to "Most Compatible" (new photos save as JPEG), '
                . 'or share/export this photo as JPG before uploading.'];
        }

        return ['error' => 'Unsupported file type — upload a JPG, PNG, WEBP, or PDF.'];
    }

    /** @return array{supplierName?:string,invoiceNumber?:string,invoiceDate?:string,items:array}|array{error:string} */
    public static function scanPurchaseBill(string $fileContent, string $mimeType): array {
        $result = self::scanFree($fileContent, $mimeType);
        if (self::isUsable($result)) {
            return $result;
        }
        // Free path failed (e.g. OCR.space's 3-page cap) or came back with every
        // item missing both quantity and price (common on a cramped/photographed
        // table where OCR flattens the layout) — worth a real dollar to get a
        // usable scan rather than handing back a dead end.
        if (CLAUDE_API_KEY !== '') {
            $claudeResult = self::scanWithClaude($fileContent, $mimeType);
            if (self::isUsable($claudeResult)) {
                return $claudeResult;
            }
            // Both failed — Claude's is the more recent/relevant attempt, so
            // surface that one (e.g. a billing error is more actionable to know
            // about than "free OCR hit its page limit", which just repeats why
            // the fallback was needed in the first place).
            error_log('BillScanHelper: free path failed (' . ($result['error'] ?? 'no usable items') . '), Claude fallback also failed (' . ($claudeResult['error'] ?? 'no usable items') . ')');
            return isset($claudeResult['error']) ? $claudeResult : $result;
        }
        return $result;
    }

    /** A result only counts as usable if at least one item has a real quantity
     * or price — an all-null items array technically "succeeded" but is useless
     * for actually recording a purchase, so it's treated the same as a failure. */
    private static function isUsable(array $result): bool {
        if (isset($result['error']) || empty($result['items'])) {
            return false;
        }
        foreach ($result['items'] as $item) {
            if (($item['quantity'] ?? null) !== null || ($item['purchasePrice'] ?? null) !== null) {
                return true;
            }
        }
        return false;
    }

    private static function scanFree(string $fileContent, string $mimeType): array {
        if (OCR_SPACE_API_KEY === '' || GROQ_API_KEY === '') {
            return ['error' => 'Free OCR scanning isn\'t set up — add OCR_SPACE_API_KEY and GROQ_API_KEY in config/constants.php.'];
        }
        $ocr = self::runOcr($fileContent, $mimeType);
        if (isset($ocr['error'])) {
            return $ocr;
        }
        if (trim($ocr['text']) === '') {
            return ['error' => 'No readable text found in the scanned bill — try a clearer, well-lit photo.'];
        }
        return self::structureWithGroq($ocr['text']);
    }

    /** OCR.space accepts image or PDF directly and returns raw extracted text —
     * no line-item structure, just what the words on the page were. */
    private static function runOcr(string $content, string $mimeType): array {
        $filetypeMap = ['application/pdf' => 'PDF', 'image/jpeg' => 'JPG', 'image/png' => 'PNG', 'image/webp' => 'WEBP'];
        $filetype = $filetypeMap[$mimeType] ?? 'JPG';

        $tmp = tmpfile();
        fwrite($tmp, $content);
        $tmpPath = stream_get_meta_data($tmp)['uri'];

        $ch = curl_init('https://api.ocr.space/parse/image');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['apikey: ' . OCR_SPACE_API_KEY],
            CURLOPT_POSTFIELDS => [
                'file' => new CURLFile($tmpPath, $mimeType, 'bill.' . strtolower($filetype)),
                'filetype' => $filetype,
                'OCREngine' => '2', // more accurate engine, better with tables/receipts
                'scale' => 'true',
                'isTable' => 'true',
            ],
            CURLOPT_TIMEOUT => 60,
        ]);
        $raw = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);
        fclose($tmp);

        if ($raw === false) {
            return ['error' => 'Could not reach the OCR service: ' . $curlError];
        }
        $data = json_decode($raw, true);
        if (!is_array($data) || !empty($data['IsErroredOnProcessing'])) {
            $msg = $data['ErrorMessage'] ?? 'OCR failed for an unknown reason.';
            if (is_array($msg)) $msg = implode(' ', $msg);
            return ['error' => 'OCR failed: ' . $msg];
        }

        $text = '';
        foreach (($data['ParsedResults'] ?? []) as $page) {
            $text .= ($page['ParsedText'] ?? '') . "\n";
        }
        return ['text' => $text];
    }

    /** Groq's response_format=json_object forces valid JSON back but (unlike
     * Gemini's response_schema) doesn't enforce a specific shape — the shape is
     * described in the prompt instead, and validated after decoding. */
    private static function structureWithGroq(string $ocrText): array {
        $prompt = <<<PROMPT
The following text was OCR'd from a supplier purchase bill/invoice, possibly with OCR
errors, misread characters, or garbled table alignment — use context to correct obvious
OCR mistakes. Extract every line item exactly as billed — do not skip rows, do not invent
data. quantity and purchasePrice must be numbers (not strings), purchasePrice is the
per-unit rate actually billed for that line, not the line total. If a field truly isn't
present, omit it or use null.

Return ONLY a JSON object with exactly this shape, nothing else:
{"supplierName": string or null, "invoiceNumber": string or null, "invoiceDate": "YYYY-MM-DD" or null,
 "items": [{"name": string, "quantity": number, "unit": string or null, "purchasePrice": number, "gstPercent": number or null, "hsn": string or null}]}

OCR TEXT:
---
{$ocrText}
---
PROMPT;

        $body = [
            'model' => GROQ_TEXT_MODEL,
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'response_format' => ['type' => 'json_object'],
            'temperature' => 0,
        ];
        $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . GROQ_API_KEY, 'Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_TIMEOUT => 45,
        ]);
        $raw = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            return ['error' => 'Could not reach the structuring service: ' . $curlError];
        }
        if ($httpCode === 429) {
            return ['error' => 'The free structuring service has hit its rate limit for now. Try again in a minute.'];
        }
        if ($httpCode !== 200) {
            return ['error' => "Structuring service returned an error (HTTP {$httpCode})."];
        }

        $decoded = json_decode($raw, true);
        $content = $decoded['choices'][0]['message']['content'] ?? null;
        if (!$content) {
            return ['error' => 'Structuring service returned an empty response.'];
        }
        $parsed = json_decode($content, true);
        if (!is_array($parsed) || empty($parsed['items'])) {
            return ['error' => 'No line items found in the scanned bill — try a clearer photo.'];
        }
        return $parsed;
    }

    /** Vision fallback — sees the actual 2D table layout instead of reading
     * OCR's flattened text, so it doesn't lose which number belongs to which
     * row. Uses forced tool-use (Anthropic's equivalent of Gemini's
     * response_schema) so the shape is actually enforced, not just prompted for. */
    private static function scanWithClaude(string $fileContent, string $mimeType): array {
        $tool = self::claudeToolSchema();

        $mediaBlock = $mimeType === 'application/pdf'
            ? ['type' => 'document', 'source' => ['type' => 'base64', 'media_type' => 'application/pdf', 'data' => base64_encode($fileContent)]]
            : ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => $mimeType, 'data' => base64_encode($fileContent)]];

        $body = [
            'model' => CLAUDE_MODEL,
            'max_tokens' => 4096,
            'tools' => [$tool],
            'tool_choice' => ['type' => 'tool', 'name' => 'extract_bill_items'],
            'messages' => [[
                'role' => 'user',
                'content' => [
                    $mediaBlock,
                    ['type' => 'text', 'text' => 'This is a photo or scan of a supplier purchase bill/invoice, possibly in a regional '
                        . 'Indian language or handwritten. Extract every line item exactly as billed — do not skip rows, do not invent data.'],
                ],
            ]],
        ];

        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'x-api-key: ' . CLAUDE_API_KEY,
                'anthropic-version: 2023-06-01',
                'content-type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_TIMEOUT => 60,
        ]);
        $raw = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            return ['error' => 'Could not reach Claude: ' . $curlError];
        }
        if ($httpCode === 429) {
            return ['error' => 'Claude has hit its rate limit for now. Try again shortly.'];
        }
        if ($httpCode !== 200) {
            $decoded = json_decode($raw, true);
            $msg = $decoded['error']['message'] ?? "Claude returned an error (HTTP {$httpCode}).";
            return ['error' => $msg];
        }

        $decoded = json_decode($raw, true);
        foreach (($decoded['content'] ?? []) as $block) {
            if (($block['type'] ?? '') === 'tool_use') {
                $parsed = $block['input'] ?? [];
                if (!is_array($parsed) || empty($parsed['items'])) {
                    return ['error' => 'No line items found in the scanned bill — try a clearer photo.'];
                }
                return $parsed;
            }
        }
        return ['error' => 'Claude returned an unexpected response.'];
    }

    /** Same two-tier pipeline as scanPurchaseBill() (free OCR+Groq first, Claude
     * vision as a paid fallback) but for a service-business price list / rate
     * card / menu photo instead of a supplier purchase bill — different prompt
     * and item shape (name/price/category/durationMinutes/gstPercent, no
     * quantity or HSN, since a menu isn't billing a quantity of anything yet).
     * Kept as fully separate methods from the purchase-bill path rather than
     * parameterizing it, so nothing here can regress the already-working
     * Purchases/Job Card scan flows.
     * @return array{items:array}|array{error:string} */
    public static function scanServiceList(string $fileContent, string $mimeType): array {
        $result = self::scanFreeServiceList($fileContent, $mimeType);
        if (self::isUsableServiceList($result)) {
            return $result;
        }
        if (CLAUDE_API_KEY !== '') {
            $claudeResult = self::scanServiceListWithClaude($fileContent, $mimeType);
            if (self::isUsableServiceList($claudeResult)) {
                return $claudeResult;
            }
            error_log('BillScanHelper: free service-list scan failed (' . ($result['error'] ?? 'no usable items') . '), Claude fallback also failed (' . ($claudeResult['error'] ?? 'no usable items') . ')');
            return isset($claudeResult['error']) ? $claudeResult : $result;
        }
        return $result;
    }

    private static function isUsableServiceList(array $result): bool {
        if (isset($result['error']) || empty($result['items'])) {
            return false;
        }
        foreach ($result['items'] as $item) {
            if (($item['price'] ?? null) !== null) {
                return true;
            }
        }
        return false;
    }

    private static function scanFreeServiceList(string $fileContent, string $mimeType): array {
        if (OCR_SPACE_API_KEY === '' || GROQ_API_KEY === '') {
            return ['error' => 'Free OCR scanning isn\'t set up — add OCR_SPACE_API_KEY and GROQ_API_KEY in config/constants.php.'];
        }
        $ocr = self::runOcr($fileContent, $mimeType);
        if (isset($ocr['error'])) {
            return $ocr;
        }
        if (trim($ocr['text']) === '') {
            return ['error' => 'No readable text found in the scanned photo — try a clearer, well-lit photo.'];
        }
        return self::structureServiceListWithGroq($ocr['text']);
    }

    private static function structureServiceListWithGroq(string $ocrText): array {
        $prompt = <<<PROMPT
The following text was OCR'd from a price list, rate card, or menu for a service business
(e.g. salon, repair shop, clinic, tuition centre, restaurant, gym) — possibly with OCR errors
or garbled table alignment — use context to correct obvious OCR mistakes. Extract every
service/item exactly as listed — do not skip rows, do not invent data. price must be a number
(not a string) — the price actually shown for that item. If a duration (in minutes) or GST% is
shown, include it; otherwise omit it or use null.

Return ONLY a JSON object with exactly this shape, nothing else:
{"items": [{"name": string, "price": number, "category": string or null, "durationMinutes": number or null, "gstPercent": number or null}]}

OCR TEXT:
---
{$ocrText}
---
PROMPT;

        $body = [
            'model' => GROQ_TEXT_MODEL,
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'response_format' => ['type' => 'json_object'],
            'temperature' => 0,
        ];
        $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . GROQ_API_KEY, 'Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_TIMEOUT => 45,
        ]);
        $raw = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            return ['error' => 'Could not reach the structuring service: ' . $curlError];
        }
        if ($httpCode === 429) {
            return ['error' => 'The free structuring service has hit its rate limit for now. Try again in a minute.'];
        }
        if ($httpCode !== 200) {
            return ['error' => "Structuring service returned an error (HTTP {$httpCode})."];
        }

        $decoded = json_decode($raw, true);
        $content = $decoded['choices'][0]['message']['content'] ?? null;
        if (!$content) {
            return ['error' => 'Structuring service returned an empty response.'];
        }
        $parsed = json_decode($content, true);
        if (!is_array($parsed) || empty($parsed['items'])) {
            return ['error' => 'No line items found in the scanned photo — try a clearer photo.'];
        }
        return $parsed;
    }

    private static function scanServiceListWithClaude(string $fileContent, string $mimeType): array {
        $tool = self::serviceListToolSchema();

        $mediaBlock = $mimeType === 'application/pdf'
            ? ['type' => 'document', 'source' => ['type' => 'base64', 'media_type' => 'application/pdf', 'data' => base64_encode($fileContent)]]
            : ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => $mimeType, 'data' => base64_encode($fileContent)]];

        $body = [
            'model' => CLAUDE_MODEL,
            'max_tokens' => 4096,
            'tools' => [$tool],
            'tool_choice' => ['type' => 'tool', 'name' => 'extract_service_list_items'],
            'messages' => [[
                'role' => 'user',
                'content' => [
                    $mediaBlock,
                    ['type' => 'text', 'text' => 'This is a photo or scan of a price list, rate card, or menu for a service '
                        . 'business, possibly in a regional Indian language or handwritten. Extract every service/item exactly '
                        . 'as listed — do not skip rows, do not invent data.'],
                ],
            ]],
        ];

        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'x-api-key: ' . CLAUDE_API_KEY,
                'anthropic-version: 2023-06-01',
                'content-type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_TIMEOUT => 60,
        ]);
        $raw = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            return ['error' => 'Could not reach Claude: ' . $curlError];
        }
        if ($httpCode === 429) {
            return ['error' => 'Claude has hit its rate limit for now. Try again shortly.'];
        }
        if ($httpCode !== 200) {
            $decoded = json_decode($raw, true);
            $msg = $decoded['error']['message'] ?? "Claude returned an error (HTTP {$httpCode}).";
            return ['error' => $msg];
        }

        $decoded = json_decode($raw, true);
        foreach (($decoded['content'] ?? []) as $block) {
            if (($block['type'] ?? '') === 'tool_use') {
                $parsed = $block['input'] ?? [];
                if (!is_array($parsed) || empty($parsed['items'])) {
                    return ['error' => 'No line items found in the scanned photo — try a clearer photo.'];
                }
                return $parsed;
            }
        }
        return ['error' => 'Claude returned an unexpected response.'];
    }

    private static function serviceListToolSchema(): array {
        return [
            'name' => 'extract_service_list_items',
            'description' => 'Extract structured items from a service-business price list, rate card, or menu.',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'items' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'name' => ['type' => 'string'],
                                'price' => ['type' => 'number'],
                                'category' => ['type' => 'string'],
                                'durationMinutes' => ['type' => 'number'],
                                'gstPercent' => ['type' => 'number'],
                            ],
                            'required' => ['name', 'price'],
                        ],
                    ],
                ],
                'required' => ['items'],
            ],
        ];
    }

    private static function claudeToolSchema(): array {
        return [
            'name' => 'extract_bill_items',
            'description' => 'Extract structured line items from a supplier purchase bill/invoice.',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'supplierName' => ['type' => 'string'],
                    'invoiceNumber' => ['type' => 'string'],
                    'invoiceDate' => ['type' => 'string', 'description' => 'YYYY-MM-DD if determinable'],
                    'items' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'name' => ['type' => 'string'],
                                'quantity' => ['type' => 'number'],
                                'unit' => ['type' => 'string'],
                                'purchasePrice' => ['type' => 'number', 'description' => 'per-unit rate actually billed for that line, not the line total'],
                                'gstPercent' => ['type' => 'number'],
                                'hsn' => ['type' => 'string'],
                            ],
                            'required' => ['name', 'quantity', 'purchasePrice'],
                        ],
                    ],
                ],
                'required' => ['items'],
            ],
        ];
    }
}
