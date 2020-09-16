<?php

namespace App\Http\Controllers\Core\V1\Organisation;

use App\BatchUpload\SpreadsheetHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organisation\ImportRequest;
use App\Models\Organisation;
use Illuminate\Validation\ValidationException;

class ImportController extends Controller
{
    /**
     * Number of rows to import at once
     *
     **/
    const ROW_IMPORT_CHUMK_SIZE = 100;

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
        $filePath = $request->spreadsheet->store('batch-upload', 'local');

        $spreadsheetHandler = new SpreadsheetHandler($filePath);

        $validationRules = $this->app->make('App\Http\Requests\Organisation\StoreRequest')->rules();

        $importedRows = 0;

        if ($spreadsheetHandler->validate($validationRules)) {
            DB::transaction(function () use ($spreadsheetHandler, $importedRows) {
                foreach ($spreadsheetHandler->rows()->chunk(self::ROW_IMPORT_CHUMK_SIZE) as $rows) {
                    DB::table('organisations')->insert($rows->all());
                    $importedRows += $rows->count();
                }
            }, 5);
        }
        return response()->json([
            'imported_row_count' => $importedRows,
            'errors' => $spreadsheetHandler->errors()->all(),
        ]);
    }
}
