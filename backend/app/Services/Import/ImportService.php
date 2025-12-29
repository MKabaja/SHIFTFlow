<?php

namespace App\Services\Import;

use App\Repositories\EmployeeRepository;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class ImportService
{
    public function __construct(
        protected EmployeesCsvExtractor $extractor,
        protected EmployeeCsvValidator $validator,
        protected EmployeeDataAssembler $assembler,
        protected EmployeeRepository $repository,
    ) {}

    public function import(UploadedFile $file): array
    {
        $headersAndRows = $this->extractor->extract($file);

        $extractedHeader = $headersAndRows['header_map'];
        $extractedRows = $headersAndRows['rows'];

        $validRowsAndIssues = $this->validator->validate($extractedRows);

        $validatedRows = $validRowsAndIssues['valid_rows'];
        $validationIssues = $validRowsAndIssues['issues'];

        $employeeData = $this->assembler->assemble($validatedRows, $extractedHeader);
        try {
            $stats = $this->repository->saveMany($employeeData);

            return [
                'success' => true,
                'created' => $stats['created'],
                'updated' => $stats['updated'],
                'total' => $stats['created'] + $stats['updated'],
                'validation_issues' => $validationIssues,

            ];

        } catch (QueryException $e) {
            Log::error('Database error during CSV import', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to save data to the database.',
                'validation_issues' => $validationIssues,
            ];

        } catch (Exception $e) {
            Log::error('Unexpected error during CSV import', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => 'Unexpected import error.',
                'validation_issues' => $validationIssues,
            ];
        }

    }
}
