<?php

namespace App\Services;

use App\Models\User;

class EmployeeService
{
    /**
     * Summary of createLogin
     *
     * @param  string  $fullName  The raw name from the input, e.g., 'Jon Smith'
     * @param  int  $length  The maximum length for the login that will be created
     * @return string login in the format [first initial][surname fragment], e.g., 'jsmit'.
     */
    public function createLogin(string $fullName, int $length): string
    {
        $length = max(1, $length);
        $parts = explode(' ', trim($fullName));

        if (count($parts) < 2) {
            $maxLoginLength = 10;

            return mb_substr($parts[0], 0, $maxLoginLength, 'UTF-8');
        }

        $firstName = $parts[0];
        $lastName = $parts[1];

        $firstLetter = mb_substr($firstName, 0, 1, 'UTF-8');
        $lastNameLength = mb_strlen($lastName, 'UTF-8');

        if ($lastNameLength <= $length) {
            $lastNameFragment = $lastName;
        } else {
            $lastNameFragment = mb_substr($lastName, 0, $length, 'UTF-8');
        }

        $login = $firstLetter.$lastNameFragment;

        return mb_strtolower($login, 'UTF-8');
    }

    /**
     * Summary of findUniqueLogin
     * This method ensures the uniqueness of a user login. If an identical login is found, it appends an identifier (e.g., 'jsmit1', 'jsmit2', etc.).
     *
     * @param  string  $baseLogin  The base login string created in the @see createLogin method.
     * @return string The unique login, either in its base form or with an appended numeric identifier.
     */
    public function findUniqueLogin(string $baseLogin): string
    {
        if (User::where('login', $baseLogin)->doesntExist()) {
            return $baseLogin;
        }

        $i = 1;
        $uniqueLogin = $baseLogin.$i;

        while (User::where('login', $uniqueLogin)->exists()) {
            $i++;
            $uniqueLogin = $baseLogin.$i;
        }

        return $uniqueLogin;
    }
}
