<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;

trait OrganisationScopes
{
    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithHasOwner(Builder $query): Builder
    {
        return $query->selectSub(
            'select count(*) > 0 from user_roles where user_roles.organisation_id = organisations.id limit 1',
            'has_owner'
        );
    }
}
