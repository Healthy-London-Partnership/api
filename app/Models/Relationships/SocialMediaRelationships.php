<?php

namespace App\Models\Relationships;

use App\Models\Organisation;
use App\Models\Service;

trait SocialMediaRelationships
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function sociable()
    {
        return $this->morphTo();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function service()
    {
        return $this->sociable()->where('sociable_type', Service::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function organisation()
    {
        return $this->sociable()->where('sociable_type', Organisation::class);
    }
}
