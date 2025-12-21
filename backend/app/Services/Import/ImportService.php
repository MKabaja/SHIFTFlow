<?php

namespace App\Services\Import;

use Illuminate\Http\UploadedFile;

class ImportService
{
    public function __construct(
        protected EmployeesCsvExtractor $extractor,
        // protected EmployeeCsvValidator $validator,
        // protected EmployeeDataAssembler $assembler,
        // protected EmployeeRepository $repository
    ) {}

    public function import(UploadedFile $file)
    {
        $rawRows = $this->extractor->extract($file);
        // $validRows = $this->validator->validate($rawRows);
        // $employeeData = $this->assembler->assemble($validRows);
        // $result = $this->repository->saveMany($employeeData);

        // return $this->mergeIssues($validRows->issues, $result->issues);

        return $rawRows;
    }
}
