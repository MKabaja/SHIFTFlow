<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Str;

class LoginGeneratorService
{
    /**
     * Generates unique login from full name.
     *
     * @param  string  $fullName  e.g., 'Jan Kowalski'
     * @param  list<string>  $existingLogins  e.g., ['jkowal', 'jkowal1']
     * @return string e.g., 'jkowal2'
     */
    public function generate(string $fullName, array $existingLogins = []): string
    {
        $baseLogin = $this->createBaseLogin($fullName);

        $matchingLogins = $this->findMatchingLogin($baseLogin, $existingLogins);

        if (! in_array($baseLogin, $matchingLogins)) {
            return $baseLogin;
        }

        return $this->findNextAvailableLogin($baseLogin, $matchingLogins);
    }

    private function createBaseLogin(string $fullName): string
    {
        $length = config('employees.login_length');
        $parts = explode(' ', trim($fullName));

        if (count($parts) < 2) {
            $login = mb_substr($parts[0], 0, 10);
        } else {
            $firstLetter = mb_substr($parts[0], 0, 1);
            $lastNameFragment = mb_substr($parts[1], 0, $length);
            $login = $firstLetter.$lastNameFragment;
        }

        return Str::ascii(mb_strtolower($login));
    }

    /** @param list<string> $existingLogins */
    private function findNextAvailableLogin(string $baseLogin, array $existingLogins): string
    {
        $maxNumber = $this->getMaxLoginValue($existingLogins);
        $nextNumber = $maxNumber + 1;

        return $baseLogin.$nextNumber;
    }

    /** @param list<string> $logins */
    private function getMaxLoginValue(array $logins): int
    {
        return collect($logins)
            ->map(function ($login) {
                preg_match('/(\d+)$/', $login, $matches);

                return isset($matches[1]) ? (int) $matches[1] : 0;
            })

            ->max() ?? 0;
    }

    /**
     * @param list<string> $existingLogins
     * @return list<string>
     */
    private function findMatchingLogin(string $baseLogin, array $existingLogins): array
    {
        return collect($existingLogins)
            ->filter(fn ($login) => str_starts_with($login, $baseLogin))
            ->values()
            ->toArray();
    }
}
