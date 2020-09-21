<?php

namespace App\Docs\Schemas\File;

use App\Models\File;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class StoreSpreadsheetSchema extends Schema
{
    /**
     * @param string|null $objectId
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     * @return static
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->type(static::TYPE_OBJECT)
            ->required(
                'spreadsheet'
            )
            ->properties(
                Schema::string('spreadsheet')
                    ->format(static::FORMAT_BINARY)
                    ->description('Base64 encoded string of an Excel compatible spreadsheet')
            );
    }
}
