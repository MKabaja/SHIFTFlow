<?php

namespace App\Services\Import;

use App\Repositories\EmployeeRepository;
use Exception;
use Illuminate\Http\UploadedFile;

class ImportService
{
    public function __construct(
        protected EmployeesCsvExtractor $extractor,
        protected EmployeeCsvValidator $validator,
        protected EmployeeDataAssembler $assembler,
        protected EmployeeRepository $repository,
    ) {}

    public function import(UploadedFile $file)
    {
        $headersAndRows = $this->extractor->extract($file);

        $extractedHeader = $headersAndRows['header_map'];
        $extractedRows = $headersAndRows['rows'];

        $validRowsAndIssues = $this->validator->validate($extractedRows);

        $validatedRows = $validRowsAndIssues['valid_rows'];
        $validationIssues = $validRowsAndIssues['issues'];

        $employeeData = $this->assembler->assemble($validatedRows, $extractedHeader);
        try {
            $this->repository->saveMany($employeeData);

            return [
                'success' => true,
                'imported' => count($employeeData),
                'validation_issues' => $validationIssues,
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'validation_issues' => $validationIssues,
            ];
        }

    }
}
