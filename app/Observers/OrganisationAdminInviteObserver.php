<?php

namespace App\Observers;

use App\Emails\OrganisationAdminInviteInitial\NotifyInviteeEmail;
use App\Generators\AdminUrlGenerator;
use App\Models\Notification;
use App\Models\OrganisationAdminInvite;

class OrganisationAdminInviteObserver
{
    /**
     * @var \App\Generators\AdminUrlGenerator
     */
    protected $adminUrlGenerator;

    /**
     * OrganisationAdminInviteObserver constructor.
     *
     * @param \App\Generators\AdminUrlGenerator $adminUrlGenerator
     */
    public function __construct(AdminUrlGenerator $adminUrlGenerator)
    {
        $this->adminUrlGenerator = $adminUrlGenerator;
    }

    /**
     * Handle the organisation admin invite "created" event.
     *
     * @param \App\Models\OrganisationAdminInvite $organisationAdminInvite
     */
    public function created(OrganisationAdminInvite $organisationAdminInvite)
    {
        // Send notification to the invitee.
        if ($organisationAdminInvite->email !== null) {
            Notification::sendEmail(new NotifyInviteeEmail(
                $organisationAdminInvite->email,
                [
                    'ORGANISATION_NAME' => $organisationAdminInvite->organisation->name,
                    'ORGANISATION_ADDRESS' => 'N/A', // TODO
                    'ORGANISATION_URL' => $organisationAdminInvite->organisation->url ?: 'N/A',
                    'ORGANISATION_EMAIL' => $organisationAdminInvite->organisation->email ?: 'N/A',
                    'ORGANISATION_PHONE' => $organisationAdminInvite->organisation->phone ?: 'N/A',
                    'ORGANISATION_SOCIAL_MEDIA' => 'N/A', // TODO
                    'ORGANISATION_DESCRIPTION' => $organisationAdminInvite->organisation->description,
                    'INVITE_URL' => $this->adminUrlGenerator->generateOrganisationAdminInviteUrl(
                        $organisationAdminInvite
                    ),
                ]
            ));
        }
    }
}
