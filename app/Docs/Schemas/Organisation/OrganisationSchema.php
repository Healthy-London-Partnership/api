<?php

namespace App\Docs\Schemas\Organisation;

use App\Docs\Schemas\Location\LocationSchema;
use App\Docs\Schemas\Service\SocialMediaSchema;
use App\Models\Organisation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class OrganisationSchema extends Schema
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
                    ->format(Schema::TYPE_OBJECT),
                Schema::boolean('has_logo'),
                Schema::boolean('location_id')
                    ->format(Schema::FORMAT_UUID),
                Schema::string('name'),
                Schema::string('slug'),
                Schema::string('description'),
                Schema::string('url'),
                Schema::string('email')
                    ->nullable(),
                Schema::string('phone')
                    ->nullable(),
                Schema::array('social_medias')
                    ->items(SocialMediaSchema::create()),
                LocationSchema::create('location'),
                Schema::string('admin_invite_status')
                    ->enum(
                        Organisation::ADMIN_INVITE_STATUS_NONE,
                        Organisation::ADMIN_INVITE_STATUS_INVITED,
                        Organisation::ADMIN_INVITE_STATUS_PENDING,
                        Organisation::ADMIN_INVITE_STATUS_CONFIRMED
                    ),
                Schema::string('created_at')
                    ->format(Schema::FORMAT_DATE_TIME)
                    ->nullable(),
                Schema::string('updated_at')
                    ->format(Schema::FORMAT_DATE_TIME)
                    ->nullable()
            );
    }
}
