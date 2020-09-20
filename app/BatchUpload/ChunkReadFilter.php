<?php

namespace App\BatchUpload;

use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class ChunkReadFilter implements IReadFilter
{
    /**
     * Start row
     *
     * @var Integer
     **/
    private $startRow = 0;

    /**
     * End row
     *
     * @var Integer
     **/
    private $endRow = 0;

    /**
     * Set the start and end rows
     *
     * @param Integer $startRow
     * @param Integer $endRow
     * @return null
     **/
    public function setRows($startRow, $chunkSize)
    {
        $this->startRow = $startRow;
        $this->endRow = $startRow + $chunkSize;
    }

    /**
     * Should the cell be read
     *
     * @param String $column
     * @param Integer $row
     * @param String $worksheetName
     * @return Boolean
     **/
    public function readCell($column, $row, $worksheetName = '')
    {
        /**
         * Only read the first (header) row, or rows within the chunksize
         */
        return ($row == 1) || ($row >= $this->startRow && $row < $this->endRow);
    }
}
