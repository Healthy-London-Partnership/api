<?php

namespace Tests\Feature;

use App\Events\EndpointHit;
use App\Models\Audit;
use App\Models\Organisation;
use App\Models\Service;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Laravel\Passport\Passport;
use Tests\TestCase;

class AuditsTest extends TestCase
{
    /*
     * List all the audits.
     */

    public function test_guest_cannot_list_them()
    {
        $response = $this->json('GET', '/core/v1/audits');

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_service_worker_cannot_list_them()
    {
        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = factory(Service::class)->create();
        $user = factory(User::class)->create();
        $this->makeServiceWorker($user, $service);

        Passport::actingAs($user);

        $response = $this->json('GET', '/core/v1/audits');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_service_admin_cannot_list_them()
    {
        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = factory(Service::class)->create();
        $user = factory(User::class)->create();
        $this->makeServiceAdmin($user, $service);

        Passport::actingAs($user);

        $response = $this->json('GET', '/core/v1/audits');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_organisation_admin_cannot_list_them()
    {
        /**
         * @var \App\Models\Organisation $organisation
         * @var \App\Models\User $user
         */
        $organisation = factory(Organisation::class)->create();
        $user = factory(User::class)->create();
        $this->makeOrganisationAdmin($user, $organisation);

        Passport::actingAs($user);

        $response = $this->json('GET', '/core/v1/audits');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_global_admin_can_list_them()
    {
        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $this->makeGlobalAdmin($user);
        $audit = Audit::create([
            'action' => Audit::ACTION_READ,
            'description' => 'Someone viewed a resource',
            'ip_address' => '127.0.0.1',
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        Passport::actingAs($user);

        $response = $this->json('GET', '/core/v1/audits');

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            [
                'id' => $audit->id,
                'user_id' => null,
                'oauth_client' => null,
                'action' => Audit::ACTION_READ,
                'description' => 'Someone viewed a resource',
                'ip_address' => '127.0.0.1',
                'user_agent' => null,
                'created_at' => $this->now->format(CarbonImmutable::ISO8601),
                'updated_at' => $this->now->format(CarbonImmutable::ISO8601),
            ],
        ]);
    }

    public function test_global_admin_can_list_them_for_a_specific_user()
    {
        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $this->makeGlobalAdmin($user);
        $audit = Audit::create([
            'user_id' => $user->id,
            'action' => Audit::ACTION_READ,
            'description' => 'Someone viewed a resource',
            'ip_address' => '127.0.0.1',
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);
        $anotherAudit = factory(Audit::class)->create();

        Passport::actingAs($user);

        $response = $this->json('GET', "/core/v1/audits?filter[user_id]={$user->id}");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            [
                'id' => $audit->id,
                'user_id' => $user->id,
                'oauth_client' => null,
                'action' => Audit::ACTION_READ,
                'description' => 'Someone viewed a resource',
                'ip_address' => '127.0.0.1',
                'user_agent' => null,
                'created_at' => $this->now->format(CarbonImmutable::ISO8601),
                'updated_at' => $this->now->format(CarbonImmutable::ISO8601),
            ],
        ]);
        $response->assertJsonMissing([
            [
                'id' => $anotherAudit->id,
                'user_id' => $anotherAudit->user_id,
                'oauth_client' => null,
                'action' => $anotherAudit->action,
                'description' => $anotherAudit->description,
                'ip_address' => $anotherAudit->ip_address,
                'user_agent' => $anotherAudit->user_agent,
                'created_at' => $anotherAudit->created_at->format(CarbonImmutable::ISO8601),
                'updated_at' => $anotherAudit->updated_at->format(CarbonImmutable::ISO8601),
            ],
        ]);
    }

    public function test_audit_created_when_listed()
    {
        $this->fakeEvents();

        $user = factory(User::class)->create();
        $this->makeGlobalAdmin($user);
        Passport::actingAs($user);

        $this->json('GET', '/core/v1/audits');

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user) {
            return ($event->getAction() === Audit::ACTION_READ) &&
                ($event->getUser()->id === $user->id);
        });
    }

    /*
     * Get a specific audit.
     */

    public function test_guest_cannot_view_one()
    {
        $audit = factory(Audit::class)->create();

        $response = $this->json('GET', "/core/v1/audits/{$audit->id}");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_service_worker_cannot_view_one()
    {
        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = factory(Service::class)->create();
        $user = factory(User::class)->create();
        $this->makeServiceWorker($user, $service);
        $audit = factory(Audit::class)->create();

        Passport::actingAs($user);

        $response = $this->json('GET', "/core/v1/audits/{$audit->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_service_admin_cannot_view_one()
    {
        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = factory(Service::class)->create();
        $user = factory(User::class)->create();
        $this->makeServiceAdmin($user, $service);
        $audit = factory(Audit::class)->create();

        Passport::actingAs($user);

        $response = $this->json('GET', "/core/v1/audits/{$audit->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_organisation_admin_cannot_view_one()
    {
        /**
         * @var \App\Models\Organisation $organisation
         * @var \App\Models\User $user
         */
        $organisation = factory(Organisation::class)->create();
        $user = factory(User::class)->create();
        $this->makeOrganisationAdmin($user, $organisation);
        $audit = factory(Audit::class)->create();

        Passport::actingAs($user);

        $response = $this->json('GET', "/core/v1/audits/{$audit->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_global_admin_can_view_one()
    {
        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $this->makeGlobalAdmin($user);
        $audit = Audit::create([
            'action' => Audit::ACTION_READ,
            'description' => 'Someone viewed a resource',
            'ip_address' => '127.0.0.1',
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        Passport::actingAs($user);

        $response = $this->json('GET', "/core/v1/audits/{$audit->id}");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            [
                'id' => $audit->id,
                'user_id' => null,
                'oauth_client' => null,
                'action' => Audit::ACTION_READ,
                'description' => 'Someone viewed a resource',
                'ip_address' => '127.0.0.1',
                'user_agent' => null,
                'created_at' => $this->now->format(CarbonImmutable::ISO8601),
                'updated_at' => $this->now->format(CarbonImmutable::ISO8601),
            ],
        ]);
    }

    public function test_audit_created_when_viewed()
    {
        $this->fakeEvents();

        $user = factory(User::class)->create();
        $this->makeGlobalAdmin($user);
        Passport::actingAs($user);

        $audit = Audit::create([
            'action' => Audit::ACTION_READ,
            'description' => 'Someone viewed a resource',
            'ip_address' => '127.0.0.1',
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        Event::fake();
        $this->json('GET', "/core/v1/audits/{$audit->id}");

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user, $audit) {
            return ($event->getAction() === Audit::ACTION_READ) &&
                ($event->getUser()->id === $user->id) &&
                ($event->getModel()->id === $audit->id);
        });
    }
}
