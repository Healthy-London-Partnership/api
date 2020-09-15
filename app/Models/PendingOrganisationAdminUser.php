<?php

namespace App\Models;

use App\Models\Mutators\PendingOrganisationAdminUserMutators;
use App\Models\Relationships\PendingOrganisationAdminUserRelationships;
use App\Models\Scopes\PendingOrganisationAdminUserScopes;

class PendingOrganisationAdminUser extends Model
{
    use PendingOrganisationAdminUserMutators;
    use PendingOrganisationAdminUserRelationships;
    use PendingOrganisationAdminUserScopes;
}
