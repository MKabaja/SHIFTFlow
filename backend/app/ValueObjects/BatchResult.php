<?php

declare(strict_types=1);

namespace App\ValueObjects;

use Illuminate\Support\Collection;

readonly class BatchResult
{
    /**
     * @param  Collection<int, \App\Models\Shift>  $shifts
     * @param  array<string, array<string, list<string>>>  $errors
     */
    private function __construct(
        private Collection $shifts,
        private array $errors = [],
    ) {}

    public function hasErrors(): bool
    {
        return ! empty($this->errors);
    }

    /**
     * @return array<string, array<string, list<string>>>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * @return Collection<int, \App\Models\Shift>
     */
    public function shifts(): Collection
    {
        return $this->shifts;
    }

    public function count(): int
    {
        return $this->shifts->count();
    }

    /**
     * @param  Collection<int, \App\Models\Shift>  $shifts
     */
    public static function success(Collection $shifts): self
    {
        return new self(shifts: $shifts);
    }

    /**
     * @param  array<string, array<string, list<string>>>  $errors
     */
    public static function withErrors(array $errors): self
    {
        return new self(shifts: collect(), errors: $errors);
    }
}
