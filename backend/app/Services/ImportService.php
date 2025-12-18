<?php

namespace App\Services;

use App\Models\User;
use App\Models\Position;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\EmployeeService;

class ImportService
{
    protected $employeeService;
    private const EXCEL_TO_DATEBASE_POSITIONS_MAP = [
        'PW' => ['PW', 'PW2'],
        'B' => ['B1', 'B2', 'B3', 'B4', 'B5', 'B5', 'B6', 'B7', 'B8'],
        'K' => ['K1', 'K2'],
        'WS' => ['WS', 'WS2'],
        'WR' => ['WR', 'WR2', 'WR3'],
        'OTG' => ['OTG', 'OTG2'],
        'PTG' => ['PTG', 'PTG2'],
        'PD' => 'PD',
        'SR' => 'SR',
        'TG' => 'TG',
        'TGT' => 'TGT',
        'BT' => 'BT'


    ];

    public function __construct(EmployeeService $employeeService)
    {
        $this->employeeService = $employeeService;
    }
    public function importEmployeesFromCSV(UploadedFile $csvFile)
    {
        $filePath = $csvFile->getRealPath();
        $fileResource = $this->readCsvFile($filePath);

        if (!$fileResource) {
            return ['error' => 'Could not open file via fopen'];
        }
        $separator = $this->detectCsvSeparator($fileResource);

        $headerToIndexMap = $this->mapRowHeadersToIndexes($fileResource, $separator);

        $nameAndPositionIndexes = $this->readCsvRows($fileResource, $separator);

        $employeesByNameAndPosition = $this->assembleEmployeeNameAndPositions($headerToIndexMap, $nameAndPositionIndexes);












        return [
            'message' => 'Podgląd pliku CSV',
            'rows' => $headerToIndexMap,
            'separator' => $separator,
            'Employes' => $employeesByNameAndPosition,
        ];
    }
    /**
     * Summary of readCsvFile
     * @param string $filePath absolute path
     * @return bool|resource returns file handeler
     */
    private function readCsvFile(string $filePath)
    {
        return fopen($filePath, 'r');
    }

    /**
     * Summary of mapRowHeadersToIndexes
     * @param array $firstCsvRow  First Row form excel file
     * @return array $cellNameToIndex return array of Key=>index
     */
    private function mapRowHeadersToIndexes($fileResource, $separator): array
    {
        $firstCsvRow = fgetcsv($fileResource, 0, $separator);

        $cellNameToIndex = [];

        foreach ($firstCsvRow as $cellIndex => $cellName) {
            if ($cellIndex <= 1) continue;
            $cellNameToIndex[trim($cellName)] = $cellIndex;
        }

        return  array_flip($cellNameToIndex);
    }

    private function readCsvRows($fileResource, $separator)
    {
        $keyAndPositionsArray = [];

        $contract_type = 'uop';

        while (($currentRow = fgetcsv($fileResource, 0, $separator)) !== false) {

            $newType = $this->detectContractTypeChange($currentRow);

            if ($newType) {
                $contract_type = $newType;
                continue;
            }


            if ($this->shouldSkipEmpty($currentRow)) continue;


            $cell = trim($currentRow[1] ?? '');

            $nameKey = $this->checkRightNameFormat($cell);
            $positionIndexes = $this->mapRowToEmloyeeData($currentRow);
            $keyAndPositionsArray[$contract_type][$nameKey] = $positionIndexes;
        }
        fclose($fileResource);
        return $keyAndPositionsArray;
    }
    private function detectContractTypeChange(array $row): string|null
    {
        if (in_array('ZLECONE', $row)) return 'zlecenie';
        if (in_array('ETATY', $row)) return 'uop';
        return null;
    }
    private function mapRowToEmloyeeData(array $currentRow): array
    {
        $positionIndexes = [];

        foreach ($currentRow as $column => $cellValue) {
            if ($column <= 1) continue;

            if (trim($cellValue) !== '') {
                $positionIndexes[] = $column;
            }
        }
        return $positionIndexes;
    }




    /**
     * Summary of shouldSkipEmptyRow
     * @param array|string  $value
     * @return bool  true-> row is empty/ false-> row  has something
     */
    private function shouldSkipEmpty(array|string $value): bool
    {
        if (is_array($value)) {
            return empty(array_filter($value));
        }
        return empty($value);
    }
    /**
     * Summary of checkRightNameFormat
     * @param string $cellValue
     * @return bool|string  e.g. 'jon Smith' or false
     */
    private function checkRightNameFormat(string $cellValue): bool|string
    {
        $words = explode(' ', $cellValue);
        $wordsWithoutDoubleSpaces = array_filter($words);

        if (count($wordsWithoutDoubleSpaces) === 2)
            return mb_strtolower($wordsWithoutDoubleSpaces[0] . ' ' . $wordsWithoutDoubleSpaces[1]);
        return false;
    }

    private function detectCsvSeparator($fileResource): string
    {
        $allowedCsvSeparators = [',', ';', "\t", '|', ':'];
        $statisics = [];



        for ($i = 0; $i <= 10; $i++) {
            $currentRow = fgets($fileResource);
            if (!$currentRow) break;

            if ($this->shouldSkipEmpty($currentRow)) continue;

            foreach ($allowedCsvSeparators as $separator) {
                $statisics[$separator][] = substr_count(trim($currentRow), $separator);
            }
        }

        $winner = $this->chooseSeparatorFromStatisics($statisics);
        rewind($fileResource);
        return $winner ?? ';';
    }

    private function chooseSeparatorFromStatisics($statisics)
    {
        foreach ($statisics as $separator => $counts) {
            $onlyUniquesValues = array_unique($counts);

            if (count($onlyUniquesValues) === 1 && current($onlyUniquesValues) > 0) {
                return $separator;
            }
        };
    }

    private function assembleEmployeeNameAndPositions(array $headerToIndexMap, array  $nameAndPositionIndexes): array
    {
        $employeeNameAndPositions = [];

        foreach ($nameAndPositionIndexes as $name => $indexes) {
            $employeeNameAndPositions[$name] = array_map(function ($index) use ($headerToIndexMap) {
                return $headerToIndexMap[$index] ?? "unknown($index)";
            }, $indexes);
        }
        return $employeeNameAndPositions;
    }
}
