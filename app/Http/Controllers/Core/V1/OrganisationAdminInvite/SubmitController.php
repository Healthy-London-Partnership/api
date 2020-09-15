<?php

namespace App\Http\Controllers\Core\V1\OrganisationAdminInvite;

use App\Events\EndpointHit;
use App\Http\Controllers\Controller;
use App\Http\Requests\OrganisationAdminInvite\SubmitRequest;
use App\Models\OrganisationAdminInvite;
use App\Models\PendingOrganisationAdmin;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SubmitController extends Controller
{
    /**
     * @param \App\Models\OrganisationAdminInvite $organisationAdminInvite
     * @param \App\Http\Requests\OrganisationAdminInvite\SubmitRequest $request
     * @return mixed
     */
    public function store(OrganisationAdminInvite $organisationAdminInvite, SubmitRequest $request)
    {
        return DB::transaction(function () use ($organisationAdminInvite, $request) {
            event(EndpointHit::onRead(
                $request,
                "Submitted organisation admin invite [{$organisationAdminInvite->id}]",
                $organisationAdminInvite
            ));

            PendingOrganisationAdmin::create([
                'organisation_id' => $organisationAdminInvite->organisation_id,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
            ]);

            $organisationAdminInvite->delete();

            return response()->json([
                'message' => 'A confirmation email will be sent shortly.',
            ], Response::HTTP_CREATED);
        });
    }
}
