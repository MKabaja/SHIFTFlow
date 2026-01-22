<?php

namespace App\ValueObjects;

readonly class BatchResult
{
    private function __construct(
        private array $shifts = [],
        private array $errors = [],
    ) {}

    public static function success(array $shifts): self
    {
        return new self(shifts: $shifts);
    }

    public static function withErrors(array $errors): self
    {
        return new self(errors: $errors);
    }

    public function hasErrors(): bool
    {
        return ! empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function shifts(): array
    {
        return $this->shifts;
    }

    public function count(): int
    {
        return count($this->shifts);
    }
}
