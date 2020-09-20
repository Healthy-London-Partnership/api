<?php

namespace App\Docs\Schemas\Organisation;

use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class ImportOrganisationSchema extends Schema
{
    /**
     * @param string|null $objectId
     * @return static
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->type(static::TYPE_OBJECT)
            ->properties(
                Schema::integer('imported_row_count'),
                Schema::array('invalid_rows')
                    ->items(Schema::object()->properties(
                        Schema::object('row')->properties(
                            Schema::string('name'),
                            Schema::string('description'),
                            Schema::string('url'),
                            Schema::string('email')
                        ),
                        Schema::object('errors')->properties(
                            Schema::string('name'),
                            Schema::string('description'),
                            Schema::string('url'),
                            Schema::string('email')
                        )
                    ))
            );
    }
}
