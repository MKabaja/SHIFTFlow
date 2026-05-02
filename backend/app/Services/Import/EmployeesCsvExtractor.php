<?php

declare(strict_types=1);

namespace App\Services\Import;

use Illuminate\Http\UploadedFile;

class EmployeesCsvExtractor
{
    /**
     * @return array{
     *     header_map: array<int,string>,
     *     rows: array<int,array<int,string>>
     * }
     */
    public function extract(UploadedFile $csvFile): array
    {
        $filePath = $csvFile->getRealPath();

        $fileResource = $this->readCsvFile($filePath);

        $separator = $this->detectCsvSeparator($fileResource);

        $headerToIndexMap = $this->mapRowHeadersToIndexes($fileResource, $separator);

        $rawData = $this->readRowsFromCsv($fileResource, $separator);

        return [
            'header_map' => $headerToIndexMap,
            'rows' => $rawData,
        ];
    }

    /**
     * @return resource
     */
    private function readCsvFile(string $filePath)
    {
        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            throw new \RuntimeException("Unable to open CSV file: {$filePath}");
        }

        return $handle;
    }

    /**
     * @param  mixed  $fileResource
     */
    private function detectCsvSeparator($fileResource): string
    {
        $allowedCsvSeparators = [',', ';', "\t", '|', ':'];
        $statistics = [];

        for ($i = 0; $i <= 10; $i++) {
            $currentRow = fgets($fileResource);
            if (! $currentRow) {
                break;
            }

            if ($this->shouldSkipEmpty($currentRow)) {
                continue;
            }

            foreach ($allowedCsvSeparators as $separator) {
                $statistics[$separator][] = substr_count(trim($currentRow), $separator);
            }
        }

        $detectedSeparator = $this->chooseSeparatorFromStatistics($statistics);
        rewind($fileResource);

        return $detectedSeparator ?? ';';
    }

    /** @param array<string, list<int>> $statistics */
    private function chooseSeparatorFromStatistics(array $statistics): ?string
    {
        foreach ($statistics as $separator => $counts) {
            $onlyUniquesValues = array_unique($counts);

            if (count($onlyUniquesValues) === 1 && current($onlyUniquesValues) > 0) {

                return $separator;
            }
        }

        return null;
    }

    /** @param list<string>|string $value */
    private function shouldSkipEmpty(array|string $value): bool
    {
        if (is_array($value)) {
            return empty(array_filter($value));
        }

        return empty($value);
    }

    /**
     * @param  resource  $fileResource
     * @return array<int, string>
     */
    private function mapRowHeadersToIndexes($fileResource, string $separator): array
    {
        $firstCsvRow = fgetcsv($fileResource, 0, $separator);

        $cellNameToIndex = [];

        foreach ($firstCsvRow as $cellIndex => $cellName) {
            if ($cellIndex <= 1) {
                continue;
            }
            $cellNameToIndex[trim($cellName)] = $cellIndex;
        }

        return array_flip($cellNameToIndex);
    }

    /**
     * @param  resource  $fileResource
     * @return array<int, array<int, string>>
     */
    private function readRowsFromCsv($fileResource, string $separator): array
    {
        $rowNumber = 1;
        $rawData = [];

        while (($currentRow = fgetcsv($fileResource, 0, $separator)) !== false) {
            $rowNumber++;

            if ($this->shouldSkipEmpty($currentRow)) {
                continue;
            }

            $rawData[$rowNumber] = array_map('trim', $currentRow);

        }

        fclose($fileResource);

        return $rawData;
    }
}
