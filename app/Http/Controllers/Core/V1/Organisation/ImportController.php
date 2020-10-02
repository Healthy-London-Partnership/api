<?php

namespace App\Http\Controllers\Core\V1\Organisation;

use App\BatchUpload\SpreadsheetParser;
use App\BatchUpload\StoresSpreadsheets;
use App\Contracts\SpreadsheetController;
use App\Exceptions\DuplicateContentException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organisation\ImportRequest;
use App\Models\Role;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ImportController extends Controller implements SpreadsheetController
{
    use StoresSpreadsheets;

    /**
     * Number of rows to import at once.
     */
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
     * @return \Illuminate\Http\Response
     */
    public function __invoke(ImportRequest $request)
    {
        $this->processSpreadsheet($request->input('spreadsheet'));

        $responseStatus = 201;
        $response = ['imported_row_count' => $this->imported];

        if (count($this->rejected)) {
            $responseStatus = 422;
            $response['errors'] = ['spreadsheet' => $this->rejected];
        }

        if (count($this->duplicates)) {
            $responseStatus = 422;
            $response['duplicates'] = $this->duplicates;
        }

        return response()->json([
            'data' => $response,
        ], $responseStatus);
    }

    /**
     * Validate the spreadsheet rows.
     *
     * @param string $filePath
     * @return array
     */
    public function validateSpreadsheet(string $filePath)
    {
        $spreadsheetParser = new SpreadsheetParser();

        $spreadsheetParser->import(Storage::disk('local')->path($filePath));

        $spreadsheetParser->readHeaders();

        $rejectedRows = $acceptedRows = [];

        foreach ($spreadsheetParser->readRows() as $i => $row) {
            $validator = Validator::make($row, [
                'name' => ['required', 'string', 'min:1', 'max:255'],
                'description' => ['required', 'string', 'min:1', 'max:10000'],
                'url' => ['present', 'url', 'max:255'],
                'email' => ['present', 'nullable', 'required_without:phone', 'email', 'max:255'],
                'phone' => [
                    'present',
                    'nullable',
                    'required_without:email',
                    'string',
                    'min:1',
                    'max:255',
                ],
            ]);

            $row['index'] = $i + 2;
            if ($validator->fails()) {
                $rejectedRows[] = ['row' => $row, 'errors' => $validator->errors()];
            }
        }

        return $rejectedRows;
    }

    /**
     * Find exisiting Orgaisations that match rows in the spreadsheet
     *
     * @return array
     **/
    public function rowsExist()
    {
        $sql = 'select group_concat(distinct id order by id separator ";") as ids,'
            . ' group_concat(distinct name order by name separator ";") as results, count(name) as row_count,'
            . ' replace(replace(replace(replace(replace(lower(trim(name)),"-","")," ",""),".",""),",",""),"\'","") as normalised_col'
            . ' FROM organisations group by normalised_col having count(name) > 1';
        return DB::select($sql);
    }

    /**
     * Import the uploaded file contents.
     *
     * @param string $filePath
     */
    public function importSpreadsheet(string $filePath)
    {
        $spreadsheetParser = new SpreadsheetParser();

        $spreadsheetParser->import(Storage::disk('local')->path($filePath));

        /**
         * Load the first row of the Spreadsheet as column names
         */
        $spreadsheetParser->readHeaders();

        $importedRows = 0;
        $adminRowBatch = [];

        DB::transaction(function () use ($spreadsheetParser, &$importedRows, &$adminRowBatch) {
            $organisationAdminRoleId = Role::organisationAdmin()->id;
            $globalAdminIds = Role::globalAdmin()->users()->pluck('users.id');
            $organisationRowBatch = $adminRowBatch = $nameIndex = [];
            foreach ($spreadsheetParser->readRows() as $i => $organisationRow) {
                /**
                 * Generate a new Organisation ID.
                 */
                $organisationRow['id'] = (string) Str::uuid();

                /**
                 * Build the name index in case of name clashes
                 */
                $nameIndex[$i + 2] = [
                    'id' => $organisationRow['id'],
                    'name' => $organisationRow['name'],
                    'index' => $i + 2,
                ];

                /**
                 * Add the meta fields to the Organisation row.
                 */
                $organisationRow['slug'] = Str::slug($organisationRow['name'] . ' ' . uniqid(), '-');
                $organisationRow['created_at'] = Date::now();
                $organisationRow['updated_at'] = Date::now();
                $organisationRowBatch[] = $organisationRow;

                /**
                 * Create the user_roles rows for Organisation Admin for each Global Admin.
                 */
                foreach ($globalAdminIds as $globalAdminId) {
                    $adminRowBatch[] = [
                        'id' => (string) Str::uuid(),
                        'user_id' => $globalAdminId,
                        'role_id' => $organisationAdminRoleId,
                        'organisation_id' => $organisationRow['id'],
                        'created_at' => Date::now(),
                        'updated_at' => Date::now(),
                    ];
                }

                /**
                 * If the batch array has reach the import batch size create the insert queries.
                 */
                if (count($organisationRowBatch) === self::ROW_IMPORT_BATCH_SIZE) {
                    DB::table('organisations')->insert($organisationRowBatch);
                    DB::table('user_roles')->insert($adminRowBatch);
                    $importedRows += self::ROW_IMPORT_BATCH_SIZE;
                    $organisationRowBatch = $adminRowBatch = [];
                }
            }

            /**
             * If there are a final batch that did not meet the import batch size, create queries for these
             */
            if (count($organisationRowBatch) && count($organisationRowBatch) !== self::ROW_IMPORT_BATCH_SIZE) {
                DB::table('organisations')->insert($organisationRowBatch);
                DB::table('user_roles')->insert($adminRowBatch);
                $importedRows += count($organisationRowBatch);
            }

            /**
             * Look for duplicates in the database
             */
            $duplicates = $this->rowsExist();
            if (count($duplicates)) {
                foreach ($duplicates as $duplicate) {
                    /**
                     * Get the IDs of the duplicate Organisations
                     */
                    $organisationIds = explode(';', $duplicate->ids);

                    /**
                     * Get the names which were duplicates
                     */
                    $names = explode(';', $duplicate->results);

                    foreach ($names as $i => $name) {
                        /**
                         * Find the imported row details for the duplicate name
                         */
                        $rowIndex = array_search($name, array_column($nameIndex, 'name', 'index'));
                        if (false !== $rowIndex) {
                            /**
                             * Get the details of the row that was being imported
                             */
                            $duplicateRow = DB::table('organisations')
                                ->where('id', $nameIndex[$rowIndex]['id'])
                                ->select($spreadsheetParser->headers)
                                ->first();
                            break;
                        }
                    }

                    /**
                     * Get the details of the rows the import row clashes with
                     */
                    unset($organisationIds[array_search($nameIndex[$rowIndex]['id'], $organisationIds)]);
                    $originalRows = DB::table('organisations')
                        ->whereIn('id', $organisationIds)
                        ->select(array_merge(['id'], $spreadsheetParser->headers))
                        ->get();

                    /**
                     * Add the result to the duplicates array
                     */
                    $this->duplicates[] = [
                        'row' => array_merge(['index' => $rowIndex], json_decode(json_encode($duplicateRow), true)),
                        'originals' => $originalRows,
                    ];
                }

                throw new DuplicateContentException();
            }
        }, 5);

        return $importedRows;
    }
}
