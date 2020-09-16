<?php

namespace App\BatchUpload;

use Rap2hpoutre\FastExcel\FastExcel;

class SpreadsheetHandler
{
    /**
     * Description
     *
     * @var Rap2hpoutre\FastExcel\FastExcel
     **/
    protected $handler;

    /**
     * Constructor
     *
     **/
    public function __construct()
    {
        $this->handler = new FastExcel();
    }
}
