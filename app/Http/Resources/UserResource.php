<?php

namespace App\Http\Resources;

use Carbon\CarbonImmutable;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'employer_name' => $this->employer_name,
            'created_at' => $this->created_at->format(CarbonImmutable::ISO8601),
            'updated_at' => $this->updated_at->format(CarbonImmutable::ISO8601),

            // Relationships.
            'roles' => UserRoleResource::collection($this->whenLoaded('userRoles')),
            'address' => $this->location_id ? new LocationResource($this->location) : null,
            'local_authority' => $this->local_authority_id ? new LocalAuthorityResource($this->localAuthority) : null,
        ];
    }
}
