<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

class EmployeeImportData
{
    /** @param list<string> $positions */
    public function __construct(
        public readonly string $name,
        public readonly string $contractType,
        public readonly array $positions
    ) {}
}
