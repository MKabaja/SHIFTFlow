<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\DataTransferObjects\EmployeeImportData;
use Illuminate\Support\Collection;

class EmployeeDataAssembler
{
    // One Excel column can represent multiple DB positions (e.g. 'B' covers B1-B8).
    private const EXCEL_TO_DATABASE_POSITIONS_MAP = [
        'PW' => ['PW', 'PW2'],
        'B' => ['B1', 'B2', 'B3', 'B4', 'B5', 'B6', 'B7', 'B8'],
        'K' => ['K1', 'K2'],
        'WS' => ['WS', 'WS2'],
        'WR' => ['WR', 'WR2', 'WR3'],
        'OTG' => ['OTG', 'OTG2'],
        'PTG' => ['PTG', 'PTG2'],
        'PD' => ['PD'],
        'SR' => ['SR'],
        'TG' => ['TG'],
        'TGT' => ['TGT'],
        'BT' => ['BT'],
    ];

    /**
     * @param  array<int, array{name:string,contract_type:string,positions:list<int>}>  $validRows
     * @param  array<int, string>  $headerMap
     * @return Collection<int, EmployeeImportData>
     */
    public function assemble(array $validRows, array $headerMap): Collection
    {
        return collect($validRows)->map(function ($employeeData) use ($headerMap) {

            return new EmployeeImportData(
                name: $employeeData['name'],
                contractType: $employeeData['contract_type'],
                positions: $this->mapIndexesToCodes(
                    $employeeData['positions'],
                    $headerMap
                )
            );
        });

    }

    /**
     * @param  list<int>  $positionIndexes
     * @param  array<int, string>  $headerMap
     * @return list<string>
     */
    private function mapIndexesToCodes(array $positionIndexes, array $headerMap): array
    {

        $headers = collect($headerMap)
            ->only($positionIndexes)
            ->values();

        return $headers->flatMap(
            fn ($header) => self::EXCEL_TO_DATABASE_POSITIONS_MAP[$header] ?? []
        )->all();
    }
}
