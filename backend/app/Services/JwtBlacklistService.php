<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class JwtBlacklistService
{
    public function setBlacklist(string $jti, int $ttlSeconds): void
    {
        Cache::put($this->getCacheKey($jti), true, $ttlSeconds);

    }

    public function isBlacklisted(string $jti): bool
    {
        return Cache::has($this->getCacheKey($jti));
    }

    private function getCacheKey(string $jti): string
    {
        return "jwt_blacklist:{$jti}";
    }
}
