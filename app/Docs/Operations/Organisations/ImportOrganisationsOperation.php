<?php

namespace App\Docs\Operations\Organisations;

use App\Docs\Tags\OrganisationsTag;
use App\Docs\Schemas\ResourceSchema;
use App\Docs\Schemas\File\StoreSpreadsheetSchema;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Operation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\RequestBody;
use App\Docs\Schemas\Organisation\ImportOrganisationSchema;

class ImportOrganisationsOperation extends Operation
{
    /**
     * @param string|null $objectId
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     * @return static
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->action(static::ACTION_POST)
            ->tags(OrganisationsTag::create())
            ->summary('Import organisations')
            ->description('**Permission:** `Super Admin`')
            ->requestBody(
                RequestBody::create()
                    ->required()
                    ->content(
                        MediaType::json()->schema(StoreSpreadsheetSchema::create())
                    )
            )
            ->responses(
                Response::created()->content(
                    MediaType::json()->schema(
                        ResourceSchema::create(null, ImportOrganisationSchema::create())
                    )
                )
            );
    }
}
