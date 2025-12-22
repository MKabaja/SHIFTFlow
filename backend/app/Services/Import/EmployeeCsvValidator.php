<?php

namespace App\Services\Import;

class EmployeeCsvValidator
{
    private const KEYWORDS_MANDATE = [
        'ZLEC',
        'UZ',
        'CONTRACT',
        'MANDATE',
    ];

    private const KEYWORDS_EMPLOYMENT = [
        'ETAT',
        'PRAC',
        'UOP',
        'EMPLOY',
    ];

    /**
     * @return array{
     *   valid_rows: array<int,array{name:string,contract_type:string,positions:array<int,int>>>,
     *   issues: array<int,array<int,string>>
     * }
     */
    public function validate(array $rawRows)
    {
        $validRows = [];
        $issues = [];

        $contractType = null;

        foreach ($rawRows as $rowNumber => $cells) {
            $nameCell = trim($cells[1] ?? '');
            $contractCell = trim($cells[2] ?? '');

            $newType = $this->detectContractTypeFromCell($contractCell);

            if ($newType) {

                $contractType = $newType;

            }
            $nameKey = $this->checkRightNameFormat($nameCell);

            if ($nameKey === false) {
                $issues[$rowNumber][] = "Invalid employee name format:{$nameCell}";

                continue;
            }
            if (! $contractType) {
                $issues[$rowNumber][] = 'Invalid employee contract type';

                continue;
            }

            $positionIndexes = $this->mapRowToEmployeeData($cells);

            $validRows[$rowNumber] = [
                'name' => $nameKey,
                'contract_type' => $contractType,
                'positions' => $positionIndexes,

            ];

        }

        return [
            'valid_rows' => $validRows,
            'issues' => $issues,
        ];
    }

    private function detectContractTypeFromCell(string $rawType): ?string
    {

        $normalizedCell = $this->normalizeText($rawType);

        if (str($normalizedCell)->contains(self::KEYWORDS_MANDATE)) {
            return 'mandate_contract';
        }

        if (str($normalizedCell)->contains(self::KEYWORDS_EMPLOYMENT)) {
            return 'employment_contract';
        }

        return null;
    }

    private function normalizeText(string $sample): string
    {
        return str($sample)
            ->ascii()
            ->upper()
            ->replace(['-', ',', '.', '(', ')', ':'], ' ')
            ->squish()
            ->toString();
    }

    private function checkRightNameFormat(string $cellValue): bool|string
    {
        $words = explode(' ', $cellValue);
        $wordsWithoutDoubleSpaces = array_filter($words);

        if (count($wordsWithoutDoubleSpaces) === 2) {
            return mb_strtolower($wordsWithoutDoubleSpaces[0].' '.$wordsWithoutDoubleSpaces[1]);
        }

        return false;
    }

    private function mapRowToEmployeeData(array $cells): array
    {
        $positionIndexes = [];

        foreach ($cells as $index => $cellValue) {
            if ($index <= 1) {
                continue;
            }

            if (trim($cellValue) === 'TAK') {
                $positionIndexes[] = $index;
            }
        }

        return $positionIndexes;
    }
}
