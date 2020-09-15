<?php

namespace App\Models\Relationships;

use App\Models\Organisation;

trait PendingOrganisationAdminUserRelationships
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function organisation()
    {
        return $this->belongsTo(Organisation::class);
    }
}
