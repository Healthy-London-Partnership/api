<?php

namespace App\Observers;

use App\Models\OrganisationAdminInvite;

class OrganisationAdminInviteObserver
{
    /**
     * Handle the organisation admin invite "created" event.
     *
     * @param \App\Models\OrganisationAdminInvite $organisationAdminInvite
     */
    public function created(OrganisationAdminInvite $organisationAdminInvite)
    {
        // TODO: Send confirmation email
    }
}
