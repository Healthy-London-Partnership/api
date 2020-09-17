<?php

namespace App\BatchUpload;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use League\Flysystem\FileNotFoundException;
use PhpOffice\PhpSpreadsheet\IOFactory;

class SpreadsheetHandler
{
    /**
     * The spreadsheet import / export library
     *
     * @var \PhpOffice\PhpSpreadsheet\Reader\Xlsx | \PhpOffice\PhpSpreadsheet\Reader\Xls
     **/
    protected $reader;

    /**
     * Path to the spreadsheet file
     *
     * @var String
     **/
    protected $spreadsheetPath;

    /**
     * Reader filter to break file into chunks
     *
     * @var ChunkReadFilter
     **/
    protected $chunkFilter;

    /**
     * The spreadsheet reader chunk size
     *
     * @var Integer
     **/
    protected $chunkSize = 2048;

    /**
     * The imported header row
     *
     * @var \Array
     **/
    public $headers = [];

    /**
     * The imported rows
     *
     * @var \Illuminate\Support\Collection
     **/
    public $rows = [];

    /**
     * Rows which failed to validate
     *
     * @var \Illuminate\Support\Collection
     **/
    public $errors;

    /**
     * Constructor
     *
     **/
    public function __construct($chunkSize = 2048)
    {
        $this->chunkFilter = new ChunkReadFilter();
        $this->chunkSize = $chunkSize;

        return $this;
    }

    /**
     * Import the Spreadsheet
     *
     * @return null
     **/
    public function import(String $spreadsheetPath)
    {
        if (!Storage::disk('local')->exists($spreadsheetPath)) {
            throw new FileNotFoundException($spreadsheetPath);
        }

        $this->spreadsheetPath = 'app/' . $spreadsheetPath;
        $fileType = IOFactory::identify(storage_path($this->spreadsheetPath));
        $this->reader = IOFactory::createReader($fileType);
        $this->reader->setReadDataOnly(true);
        $this->reader->setReadFilter($this->chunkFilter);

        return $this;
    }

    /**
     * Read the spreadsheet headers
     *
     * @param type name
     * @return null
     * @author
     **/
    public function readHeaders()
    {
        $this->chunkFilter->setRows(1, 0);
        $spreadsheet = $this->reader->load(storage_path($this->spreadsheetPath));
        $worksheet = $spreadsheet->getActiveSheet();
        $headerRow = $worksheet->getRowIterator(1, 1)->current();
        foreach ($headerRow->getCellIterator() as $cell) {
            $this->headers[$cell->getColumn()] = $cell->getValue();
        }
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
    }

    /**
     * Read the spreadsheet in chunks
     *
     * @param type name
     * @return null
     * @author
     **/
    public function readRows()
    {
        for ($startRow = 2; $startRow <= 65536; $startRow += $this->chunkSize) {
            $this->chunkFilter->setRows($startRow, $this->chunkSize);
            $spreadsheet = $this->reader->load(storage_path($this->spreadsheetPath));
            $worksheet = $spreadsheet->getActiveSheet();
            if ($worksheet->getHighestDataRow() > 1) {
                foreach ($worksheet->getRowIterator(2) as $rowIterator) {
                    $row = [];
                    $cellIterator = $rowIterator->getCellIterator();
                    $cellIterator->setIterateOnlyExistingCells(false);
                    foreach ($cellIterator as $cell) {
                        $row[$this->headers[$cell->getColumn()]] = $cell->getValue();
                    }
                    yield $row;
                }
            }
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        }
    }

    /**
     * Validate the imported rows against provided rules
     *
     * @param Array $rules
     * @return null
     **/
    public function validate(array $rules)
    {
        foreach ($this->rows->all() as $i => $row) {
            $validator = Validator::make($row, $rules);

            if ($validator->fails()) {
                $this->errors->push($row);
            }
        }

        return !$this->errors->count();
    }
}
