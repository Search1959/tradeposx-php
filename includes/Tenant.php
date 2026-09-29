<?php
// Resolves the org-slug URL segment into an organizations row and exposes it
// as a request-wide singleton — this is the core of path-based multi-tenancy
// (tradeposx.com/{org-slug}/...). Controllers/models never re-look-it-up;
// they call Tenant::id() to scope every query, and Auth::requireOrgAccess()
// to reject cross-tenant access even if a request forges another org's id.
class Tenant {
    private static ?array $org = null;
    private static bool $resolved = false;

    public static function resolve(string $slug): ?array {
        self::$resolved = true;
        $orgModel = new Organization();
        self::$org = $orgModel->findBySlug($slug);
        return self::$org;
    }

    public static function current(): ?array {
        return self::$org;
    }

    public static function id(): ?int {
        return self::$org['id'] ?? null;
    }

    public static function wasResolved(): bool {
        return self::$resolved;
    }
}
