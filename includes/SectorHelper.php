<?php

class SectorHelper {
    private static ?array $data = null;

    private static function data(): array {
        if (self::$data === null) {
            self::$data = require __DIR__ . '/../config/sectors.php';
        }
        return self::$data;
    }

    /** @return array<string,string> code => label */
    public static function tradeSectors(): array {
        return self::data()['trade'];
    }

    /** @return array<string,string> code => label */
    public static function serviceSectors(): array {
        return self::data()['service'];
    }

    public static function tradeLabel(?string $code): ?string {
        return $code ? (self::data()['trade'][$code] ?? null) : null;
    }

    public static function serviceLabel(?string $code): ?string {
        return $code ? (self::data()['service'][$code] ?? null) : null;
    }

    /** Product category suggestions for a trade sector code, falling back to a generic list. */
    public static function categoriesFor(?string $tradingSectorCode): array {
        $d = self::data();
        if ($tradingSectorCode && isset($d['categories'][$tradingSectorCode])) {
            return $d['categories'][$tradingSectorCode];
        }
        return $d['default_categories'];
    }
}
