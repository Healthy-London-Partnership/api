<?php

namespace App\Generators;

use App\Models\OrganisationAdminInvite;

class AdminUrlGenerator
{
    /**
     * @var string
     */
    protected $adminUrl;

    /**
     * AdminUrlGenerator constructor.
     *
     * @param string $adminUrl
     */
    public function __construct(string $adminUrl)
    {
        $this->adminUrl = $adminUrl;
    }

    /**
     * @param \App\Models\OrganisationAdminInvite $organisationAdminInvite
     * @return string
     */
    public function generateOrganisationAdminInviteUrl(OrganisationAdminInvite $organisationAdminInvite): string
    {
        return "{$this->adminUrl}/organisation-admin-invites/{$organisationAdminInvite->id}";
    }
}
