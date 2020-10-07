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
        $this->ignoreDuplicateIds = $request->input('ignore_duplicates', []);
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
     * Find exisiting Orgaisations that match rows in the spreadsheet.
     *
     * @return array
     */
    public function rowsExist()
    {
        $normaliseCharacters = mb_str_split('-, _.\'"');

        $sql = [
            implode(',', [
                'select group_concat(distinct id order by id separator ";") as ids',
                'group_concat(distinct name order by name separator ";") as results',
                'count(name) as row_count',
                $this->buildSqlReplaceCharacterSet('lower(trim(name))', $normaliseCharacters) . ' as normalised_col',
            ]),
        ];
        $sql[] = 'FROM organisations';
        if (count($this->ignoreDuplicateIds)) {
            $sql[] = 'where id NOT IN ("' . implode('","', $this->ignoreDuplicateIds) . '")';
        }
        $sql[] = 'group by normalised_col having count(name) > 1';

        return DB::select(implode(' ', $sql));
    }

    /**
     * Wrap a string in SQL replace functions for a character set.
     *
     * @param string $string
     * @param array $replace
     * @param string $replacement
     * @return string
     */
    public function buildSqlReplaceCharacterSet(string $string, array $replace, $replacement = '')
    {
        $sql = $string;
        foreach ($replace as $chr) {
            if ($chr === "'" || $chr === '"') {
                $chr = '\\' . $chr;
            }
            $sql = 'replace(' . $sql . ',"' . $chr . '","' . $replacement . '")';
        }

        return $sql;
    }

    /**
     * Format the duplicate Organisations and store details of them.
     *
     * @param array $duplicates
     * @param array $headers
     * @param array $nameIndex
     * @throws App\Exceptions\DuplicateContentException
     */
    public function formatDuplicates(array $duplicates, array $headers, array $nameIndex)
    {
        foreach ($duplicates as $duplicate) {
            /**
             * Get the IDs of the duplicate Organisations.
             */
            $organisationIds = explode(';', $duplicate->ids);

            /**
             * Get the names which were duplicates.
             */
            $names = explode(';', $duplicate->results);

            foreach ($names as $i => $name) {
                /**
                 * Find the imported row details for the duplicate name.
                 */
                $rowIndex = array_search($name, array_column($nameIndex, 'name', 'index'));
                if (false !== $rowIndex) {
                    /**
                     * Get the details of the row that was being imported.
                     */
                    $duplicateRow = DB::table('organisations')
                        ->where('id', $nameIndex[$rowIndex]['id'])
                        ->select($headers)
                        ->first();
                    break;
                }
            }

            /**
             * Get the details of the rows the import row clashes with.
             */
            unset($organisationIds[array_search($nameIndex[$rowIndex]['id'], $organisationIds)]);
            $originalRows = DB::table('organisations')
                ->whereIn('id', $organisationIds)
                ->select(array_merge(['id'], $headers))
                ->get();

            /**
             * Add the result to the duplicates array.
             */
            $this->duplicates[] = [
                'row' => array_merge(['index' => $rowIndex], json_decode(json_encode($duplicateRow), true)),
                'originals' => $originalRows,
            ];
        }

        throw new DuplicateContentException();
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
         * Load the first row of the Spreadsheet as column names.
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
                 * Generate a new Organisation ID, normalise the Organistion name
                 * and add the meta fields to the Organisation row.
                 */
                $organisationRow['id'] = (string)Str::uuid();
                $organisationRow['name'] = preg_replace('/[^a-zA-Z0-9,\.\'\&" ]/', '', $organisationRow['name']);
                $organisationRow['slug'] = Str::slug($organisationRow['name'] . ' ' . uniqid(), '-');
                $organisationRow['created_at'] = Date::now();
                $organisationRow['updated_at'] = Date::now();

                /**
                 * Build the name index in case of name clashes.
                 */
                $nameIndex[$i + 2] = [
                    'id' => $organisationRow['id'],
                    'name' => $organisationRow['name'],
                    'index' => $i + 2,
                ];

                /**
                 * Add the row to the batch array.
                 */
                $organisationRowBatch[] = $organisationRow;

                /**
                 * Create the user_roles rows for Organisation Admin for each Global Admin.
                 */
                foreach ($globalAdminIds as $globalAdminId) {
                    $adminRowBatch[] = [
                        'id' => (string)Str::uuid(),
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
             * If there are a final batch that did not meet the import batch size, create queries for these.
             */
            if (count($organisationRowBatch) && count($organisationRowBatch) !== self::ROW_IMPORT_BATCH_SIZE) {
                DB::table('organisations')->insert($organisationRowBatch);
                DB::table('user_roles')->insert($adminRowBatch);
                $importedRows += count($organisationRowBatch);
            }

            /**
             * Look for duplicates in the database.
             */
            $duplicates = $this->rowsExist();

            if (count($duplicates)) {
                /**
                 * If there are still duplicates despite having ignore_duplicates IDs.
                 * As this is an atomic process, all duplicates should be returned as none have been inserted.
                 */
                if (count($this->ignoreDuplicateIds)) {
                    $this->ignoreDuplicateIds = [];
                    $duplicates = $this->rowsExist();
                }
                /**
                 * Throws an exception which will be caught in self::processSpreadsheet.
                 */
                $this->formatDuplicates($duplicates, $spreadsheetParser->headers, $nameIndex);
            }
        }, 5);

        return $importedRows;
    }
}
