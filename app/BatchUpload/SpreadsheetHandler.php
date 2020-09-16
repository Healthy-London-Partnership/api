<?php

namespace App\BatchUpload;

use Illuminate\Support\Facades\Validator;
use Rap2hpoutre\FastExcel\FastExcel;

class SpreadsheetHandler
{
    /**
     * The spreadsheet import / export library
     *
     * @var Rap2hpoutre\FastExcel\FastExcel
     **/
    protected $handler;

    /**
     * Path to the spreadsheet file
     *
     * @var String
     **/
    protected $spreadsheetPath;

    /**
     * The imported rows
     *
     * @var \Illuminate\Support\Collection
     **/
    protected $rows;

    /**
     * Rows which failed to validate
     *
     * @var \Illuminate\Support\Collection
     **/
    protected $errors;

    /**
     * Constructor
     *
     **/
    public function __construct(String $spreadsheet)
    {
        $this->handler = new FastExcel();
        $this->spreadsheetPath = $spreadsheet;
        $this->rows = collect([]);
        $this->errors = collect([]);

        return $this;
    }

    /**
     * Rows Getter
     *
     * @return null
     **/
    public function rows()
    {
        return $this->rows;
    }

    /**
     * Errors Getter
     *
     * @return null
     **/
    public function errors()
    {
        return $this->errors;
    }

    /**
     * Import the Spreadsheet
     *
     * @return null
     **/
    public function import()
    {
        $this->rows = $this->handler->import($this->spreadsheetPath);

        return $this;
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
