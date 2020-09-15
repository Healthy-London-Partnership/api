<?php

namespace App\Docs\Schemas\OrganisationAdminInvite;

use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class StoreOrganisationAdminInviteSchema extends Schema
{
    /**
     * @inheritDoc
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->type(static::TYPE_OBJECT)
            ->required(
                'organisation_id',
                'email'
            )
            ->properties(
                Schema::string('organisation_id')
                    ->format(Schema::FORMAT_UUID),
                Schema::string('email')
                    ->nullable()
            );
    }
}
