<?php

namespace App\BatchUpload;

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
        $this->spreadsheetPath = $spreadsheetPath;
        $fileType = IOFactory::identify($this->spreadsheetPath);
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
        $spreadsheet = $this->reader->load($this->spreadsheetPath);
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
            $spreadsheet = $this->reader->load($this->spreadsheetPath);
            $worksheet = $spreadsheet->getActiveSheet();
            if ($worksheet->getHighestDataRow() > 1) {
                foreach ($worksheet->getRowIterator(2) as $rowIterator) {
                    $row = [];
                    $cellIterator = $rowIterator->getCellIterator(array_key_first($this->headers), array_key_last($this->headers));
                    $cellIterator->setIterateOnlyExistingCells(false);
                    foreach ($cellIterator as $cell) {
                        if (isset($this->headers[$cell->getColumn()])) {
                            $row[$this->headers[$cell->getColumn()]] = $cell->getValue();
                        }
                    }
                    dump($row);
                    yield $row;
                }
            }
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        }
    }
}
