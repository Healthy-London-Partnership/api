<?php

namespace Tests\Feature;

use App\Models\Organisation;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Response;
use Laravel\Passport\Passport;
use Tests\TestCase;

class OrganisationAdminInvitesTest extends TestCase
{
    /*
     * Create an organisation admin invite.
     */

    public function test_can_create_single_invite()
    {
        $organisation = factory(Organisation::class)->create();

        Passport::actingAs(
            $user = factory(User::class)->create()->makeSuperAdmin()
        );

        $response = $this->postJson('/core/v1/organisation-admin-invites', [
            'organisations' => [
                [
                    'organisation_id' => $organisation->id,
                    'use_email' => false,
                ],
            ],
        ]);

        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment([
            'organisation_id' => $organisation->id,
            'email' => null,
        ]);
    }

    public function test_can_create_single_invite_with_email()
    {
        $organisation = factory(Organisation::class)->create([
            'email' => 'john.doe@example.com',
        ]);

        Passport::actingAs(
            $user = factory(User::class)->create()->makeSuperAdmin()
        );

        $response = $this->postJson('/core/v1/organisation-admin-invites', [
            'organisations' => [
                [
                    'organisation_id' => $organisation->id,
                    'use_email' => true,
                ],
            ],
        ]);

        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment([
            'organisation_id' => $organisation->id,
            'email' => 'john.doe@example.com',
        ]);

        // TODO: Assert email job queued.
    }

    public function test_can_create_multiple_invites()
    {
        $organisationOne = factory(Organisation::class)->create();
        $organisationTwo = factory(Organisation::class)->create();

        Passport::actingAs(
            $user = factory(User::class)->create()->makeSuperAdmin()
        );

        $response = $this->postJson('/core/v1/organisation-admin-invites', [
            'organisations' => [
                [
                    'organisation_id' => $organisationOne->id,
                    'use_email' => false,
                ],
                [
                    'organisation_id' => $organisationTwo->id,
                    'use_email' => false,
                ],
            ],
        ]);

        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment([
            'organisation_id' => $organisationOne->id,
            'email' => null,
        ]);
        $response->assertJsonFragment([
            'organisation_id' => $organisationTwo->id,
            'email' => null,
        ]);
    }

    public function test_can_create_multiple_invites_some_with_email()
    {
        $organisationOne = factory(Organisation::class)->create();
        $organisationTwo = factory(Organisation::class)->create([
            'email' => 'john.doe@example.com',
        ]);

        Passport::actingAs(
            $user = factory(User::class)->create()->makeSuperAdmin()
        );

        $response = $this->postJson('/core/v1/organisation-admin-invites', [
            'organisations' => [
                [
                    'organisation_id' => $organisationOne->id,
                    'use_email' => false,
                ],
                [
                    'organisation_id' => $organisationTwo->id,
                    'use_email' => true,
                ],
            ],
        ]);

        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment([
            'organisation_id' => $organisationOne->id,
            'email' => null,
        ]);
        $response->assertJsonFragment([
            'organisation_id' => $organisationTwo->id,
            'email' => 'john.doe@example.com',
        ]);
    }

    public function test_create_with_email_for_organisations_missing_email_are_ignored()
    {
        $organisation = factory(Organisation::class)->create([
            'email' => null,
        ]);

        Passport::actingAs(
            $user = factory(User::class)->create()->makeSuperAdmin()
        );

        $response = $this->postJson('/core/v1/organisation-admin-invites', [
            'organisations' => [
                [
                    'organisation_id' => $organisation->id,
                    'use_email' => true,
                ],
            ],
        ]);

        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJsonCount(0, 'data');

        // TODO: Assert email job not queued.
    }

    public function test_can_create_no_invites()
    {
        Passport::actingAs(
            $user = factory(User::class)->create()->makeSuperAdmin()
        );

        $response = $this->postJson('/core/v1/organisation-admin-invites', [
            'organisations' => [],
        ]);

        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJsonCount(0, 'data');
    }

    public function test_guest_cannot_create_invite()
    {
        $response = $this->postJson('/core/v1/organisation-admin-invites', [
            'organisations' => [],
        ]);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_service_worker_cannot_create_invite()
    {
        $service = factory(Service::class)->create();

        Passport::actingAs(
            $user = factory(User::class)->create()->makeServiceWorker($service)
        );

        $response = $this->postJson('/core/v1/organisation-admin-invites', [
            'organisations' => [],
        ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_service_admin_cannot_create_invite()
    {
        $service = factory(Service::class)->create();

        Passport::actingAs(
            $user = factory(User::class)->create()->makeServiceAdmin($service)
        );

        $response = $this->postJson('/core/v1/organisation-admin-invites', [
            'organisations' => [],
        ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_organisation_admin_cannot_create_invite()
    {
        $organisation = factory(Organisation::class)->create();

        Passport::actingAs(
            $user = factory(User::class)->create()->makeOrganisationAdmin($organisation)
        );

        $response = $this->postJson('/core/v1/organisation-admin-invites', [
            'organisations' => [],
        ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_global_admin_cannot_create_invite()
    {
        Passport::actingAs(
            $user = factory(User::class)->create()->makeGlobalAdmin()
        );

        $response = $this->postJson('/core/v1/organisation-admin-invites', [
            'organisations' => [],
        ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }
}
