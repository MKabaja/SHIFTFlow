<?php

namespace App\ValueObjects;

use Illuminate\Support\Collection;

readonly class BatchResult
{
    private function __construct(
        private Collection $shifts,
        private array $errors = [],
    ) {}

    public function hasErrors(): bool
    {
        return ! empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function shifts(): Collection
    {
        return $this->shifts;
    }

    public function count(): int
    {
        return $this->shifts->count();
    }

    public static function success(Collection $shifts): self
    {
        return new self(shifts: $shifts);
    }

    public static function withErrors(array $errors): self
    {
        return new self(shifts: collect(), errors: $errors);
    }
}
