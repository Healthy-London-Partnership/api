<?php

namespace Tests\Feature;

use App\Events\EndpointHit;
use App\Models\Audit;
use App\Models\PendingOrganisationAdmin;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class PendingOrganisationAdminsTest extends TestCase
{
    /*
     * Confirm a pending organisation admin.
     */

    public function test_guest_can_confirm()
    {
        $pendingOrganisationAdmin = factory(PendingOrganisationAdmin::class)->create();

        $this->assertEquals(1, PendingOrganisationAdmin::query()->count());
        $this->assertEquals(0, User::query()->count());

        $response = $this->postJson("/core/v1/pending-organisation-admins/{$pendingOrganisationAdmin->id}/confirm");

        $response->assertStatus(Response::HTTP_CREATED);
        $this->assertEquals(0, PendingOrganisationAdmin::query()->count());
        $this->assertEquals(1, User::query()->count());
    }

    public function test_confirm_created_audit()
    {
        $this->fakeEvents();

        $pendingOrganisationAdmin = factory(PendingOrganisationAdmin::class)->create();

        $this->postJson("/core/v1/pending-organisation-admins/{$pendingOrganisationAdmin->id}/confirm");

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) {
            return ($event->getAction() === Audit::ACTION_CREATE);
        });
    }
}
