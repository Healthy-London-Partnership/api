<?php

namespace App\Docs\Operations\OrganisationAdminInvites;

use App\Docs\Schemas\OrganisationAdminInvite\OrganisationAdminInviteSchema;
use App\Docs\Schemas\OrganisationAdminInvite\StoreOrganisationAdminInviteSchema;
use App\Docs\Schemas\OrganisationAdminInvite\SubmitOrganisationAdminInviteSchema;
use App\Docs\Schemas\ResourceSchema;
use App\Docs\Schemas\User\UserSchema;
use App\Docs\Tags\OrganisationAdminInvitesTag;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Operation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\RequestBody;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;

class SubmitOrganisationAdminInviteOperation extends Operation
{
    /**
     * @inheritDoc
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->action(static::ACTION_GET)
            ->tags(OrganisationAdminInvitesTag::create())
            ->summary('Submit an organisation admin invite')
            ->description('**Permission:** `Open`')
            ->requestBody(
                RequestBody::create()
                    ->required()
                    ->content(
                        MediaType::json()->schema(SubmitOrganisationAdminInviteSchema::create())
                    )
            )
            ->responses(
                Response::ok()->content(
                    MediaType::json()->schema(
                        ResourceSchema::create(null, UserSchema::create())
                    )
                )
            );
    }
}
