<?php

namespace App\Docs\Schemas\OrganisationAdminInvite;

use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class StoreOrganisationAdminInviteSchema extends Schema
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
                'organisation_ids',
                'email'
            )
            ->properties(
                Schema::array('organisation_ids')->items(
                    Schema::string()->format(Schema::FORMAT_UUID)
                ),
                Schema::string('email')->nullable()
            );
    }
}
