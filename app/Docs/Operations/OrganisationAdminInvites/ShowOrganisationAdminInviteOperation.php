<?php

namespace App\Docs\Operations\OrganisationAdminInvites;

use App\Docs\Schemas\OrganisationAdminInvite\OrganisationAdminInviteSchema;
use App\Docs\Schemas\ResourceSchema;
use App\Docs\Tags\OrganisationAdminInvitesTag;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Operation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;

class ShowOrganisationAdminInviteOperation extends Operation
{
    /**
     * @inheritDoc
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->action(static::ACTION_GET)
            ->tags(OrganisationAdminInvitesTag::create())
            ->summary('Get a specific organisation admin invite')
            ->description('**Permission:** `Open`')
            ->noSecurity()
            ->responses(
                Response::ok()->content(
                    MediaType::json()->schema(
                        ResourceSchema::create(null, OrganisationAdminInviteSchema::create())
                    )
                )
            );
    }
}
