<?php

namespace App\Http\Controllers\Core\V1\Organisation;

use App\BatchUpload\SpreadsheetHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organisation\ImportRequest;
use App\Models\Organisation;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use League\Flysystem\FileNotFoundException;

class ImportController extends Controller
{
    /**
     * Number of rows to import at once
     *
     **/
    const ROW_IMPORT_BATCH_SIZE = 100;

    /**
     * OrganisationController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Http\Requests\Organisation\ImportRequest $request
     * @throws Illuminate\Validation\ValidationException
     * @return \Illuminate\Http\Response
     */
    public function __invoke(ImportRequest $request)
    {
        if (!$request->file('spreadsheet')->isValid()) {
            throw ValidationException::withMessages(['Supplied file is not valid']);
        }
        $filePath = $request->file('spreadsheet')->store('batch-upload');

        if (!Storage::disk('local')->exists($filePath) || !is_readable(Storage::disk('local')->path($filePath))) {
            throw new FileNotFoundException($filePath);
        }

        $rejectedRows = $this->validateSpreadsheet($filePath);
        $importedRows = 0;

        if (!count($rejectedRows)) {
            $importedRows = $this->importSpreadsheet($filePath);
        }

        Storage::disk('local')->delete($filePath);

        return response()->json([
            'imported_row_count' => $importedRows,
            'errors' => $rejectedRows,
        ]);
    }

    /**
     * Validate the spreadsheet rows
     *
     * @param String $filePath
     * @return Array
     **/
    public function validateSpreadsheet(String $filePath)
    {
        $spreadsheetHandler = new SpreadsheetHandler();

        $spreadsheetHandler->import(Storage::disk('local')->path($filePath));

        $spreadsheetHandler->readHeaders();

        $validationRules = $this->app->make('App\Http\Requests\Organisation\StoreRequest')->rules();

        $rejectedRows = [];

        foreach ($spreadsheetHandler->readRows() as $row) {
            $validator = Validator::make($row, $validationRules);

            if ($validator->fails()) {
                $rejectedRows[] = $row;
            }
        }

        return $rejectedRows;
    }

    /**
     * Import the uploaded file contents
     *
     * @param String $filePath
     * @return null
     **/
    public function importSpreadsheet(String $filePath)
    {
        $spreadsheetHandler = new SpreadsheetHandler();

        $spreadsheetHandler->import(Storage::disk('local')->path($filePath));

        $spreadsheetHandler->readHeaders();

        $importedRows = 0;

        DB::transaction(function () use ($spreadsheetHandler, $importedRows) {
            $rowBatch = [];
            foreach ($spreadsheetHandler->readRows() as $row) {
                $rowBatch[] = $row;

                if (count($rowBatch) === self::ROW_IMPORT_BATCH_SIZE) {
                    DB::table('organisations')->insert($rowBatch);
                    $importedRows += self::ROW_IMPORT_BATCH_SIZE;
                    $rowBatch = [];
                }
            }
            if (count($rowBatch)) {
                DB::table('organisations')->insert($rowBatch);
                $importedRows += count($rowBatch);
            }
        }, 5);

        return $importedRows;
    }
}
