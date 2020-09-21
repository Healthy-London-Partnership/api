<?php

namespace App\Http\Controllers\Core\V1\Organisation;

use App\BatchUpload\SpreadsheetHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organisation\ImportRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use League\Flysystem\FileNotFoundException;
use Symfony\Component\Mime\MimeTypes;

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
        Log::info($request->input('spreadsheet'));
        $filePath = $this->storeBase64FileString($request->input('spreadsheet'), 'batch-upload');
        Log::info($filePath);

        if (!Storage::disk('local')->exists($filePath) || !is_readable(Storage::disk('local')->path($filePath))) {
            throw new FileNotFoundException($filePath);
        }

        $rejectedRows = $this->validateSpreadsheet($filePath);
        $importedRows = 0;

        if (!count($rejectedRows)) {
            $importedRows = $this->importSpreadsheet($filePath);
        }

        Storage::disk('local')->delete($filePath);

        $responseStatus = 201;
        $response = ['imported_row_count' => $importedRows];

        if (count($rejectedRows)) {
            $responseStatus = 422;
            $response = ['errors' => ['spreadsheet' => $rejectedRows]];
        }
        return response()->json([
            'data' => $response,
        ], $responseStatus);
    }

    /**
     * Store a binary file blob and update the models properties
     *
     * @param String $blob
     * @param String $path
     * @param String $mime_type
     * @param String $ext
     * @return String
     **/
    public function storeBinaryUpload(string $blob, string $path, $mime_type = null, $ext = null)
    {
        $path = empty($path) ? '' : trim($path, '/') . '/';
        $mime_type = $mime_type ?? $this->getFileStringMimeType($blob);
        $ext = $ext ?? $this->guessFileExtension($mime_type);
        $filename = md5($blob) . '.' . $ext;
        Storage::disk('local')->put($path . $filename, $blob);
        return $path . $filename;
    }

    /**
     * Store a Base 64 encoded data string
     *
     * @param string $file_data
     * @param string $path
     * @return String
     **/
    public function storeBase64FileString(string $file_data, string $path)
    {
        preg_match('/^data:(application\/vnd[a-z\-\.]+);base64,(.*)/', $file_data, $matches);
        if (count($matches) < 3) {
            throw new ValidationException('Invalid Base64 Excel data');
        }
        if (!$file_blob = base64_decode(trim($matches[2]), true)) {
            throw new ValidationException('Invalid Base64 Excel data');
        }
        return $this->storeBinaryUpload($file_blob, $path, $matches[1]);
    }

    /**
     * Get the mime type of a binary file string
     *
     * @var String $file_str
     * @return String mime type
     **/
    public function getFileStringMimeType(string $file_str)
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_buffer($finfo, $file_str);
        finfo_close($finfo);
        return $mime_type;
    }

    /**
     * Guess the extension for a file from it's mime-type
     *
     * @param String $mime_type
     * @return String
     **/
    public function guessFileExtension(string $mime_type)
    {
        return (new MimeTypes)->getExtensions($mime_type)[0] ?? null;
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

        $rejectedRows = [];

        foreach ($spreadsheetHandler->readRows() as $i => $row) {
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

            if ($validator->fails()) {
                $row['index'] = $i;
                $rejectedRows[] = ['row' => $row, 'errors' => $validator->errors()];
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

        DB::transaction(function () use ($spreadsheetHandler, &$importedRows) {
            $rowBatch = [];
            foreach ($spreadsheetHandler->readRows() as $row) {
                $row['id'] = (string) Str::uuid();
                $row['slug'] = Str::slug($row['name'] . ' ' . uniqid(), '-');
                $rowBatch[] = $row;

                if (count($rowBatch) === self::ROW_IMPORT_BATCH_SIZE) {
                    DB::table('organisations')->insert($rowBatch);
                    $importedRows += self::ROW_IMPORT_BATCH_SIZE;
                    $rowBatch = [];
                }
            }

            if (count($rowBatch) && count($rowBatch) !== self::ROW_IMPORT_BATCH_SIZE) {
                DB::table('organisations')->insert($rowBatch);
                $importedRows += count($rowBatch);
            }
        }, 5);

        return $importedRows;
    }
}
