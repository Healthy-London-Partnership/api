<?php

namespace App\Docs\Schemas\User;

use App\Docs\Schemas\LocalAuthority\LocalAuthoritySchema;
use App\Docs\Schemas\Location\LocationSchema;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class UserSchema extends Schema
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
                Schema::string('id')
                    ->format(Schema::FORMAT_UUID),
                Schema::string('first_name'),
                Schema::string('last_name'),
                Schema::string('email'),
                Schema::string('phone')
                    ->nullable(),
                Schema::array('roles')
                    ->items(RoleSchema::create()),
                LocationSchema::create('address')
                    ->nullable(),
                LocalAuthoritySchema::create('local_authority')
                    ->nullable(),
                Schema::string('created_at')
                    ->format(Schema::FORMAT_DATE_TIME)
                    ->nullable(),
                Schema::string('updated_at')
                    ->format(Schema::FORMAT_DATE_TIME)
                    ->nullable()
            );
    }
}
