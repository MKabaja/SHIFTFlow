<?php

namespace App\DataTransferObjects;

class EmployeeImportData
{
    public function __construct(
        public readonly string $name,
        public readonly string $contractType,
        public readonly array $positions
    ) {}
}
