<?php

namespace App\Http\Resources;

use Carbon\CarbonImmutable;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganisationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'location_id' => $this->location_id,
            'has_logo' => $this->hasLogo(),
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'url' => $this->url,
            'email' => $this->email,
            'phone' => $this->phone,
            'social_medias' => SocialMediaResource::collection($this->socialMedias),
            'location' => $this->location_id ? new LocationResource($this->location) : null,
            'admin_invite_status' => $this->admin_invite_status,
            'created_at' => $this->created_at->format(CarbonImmutable::ISO8601),
            'updated_at' => $this->updated_at->format(CarbonImmutable::ISO8601),
        ];
    }
}
