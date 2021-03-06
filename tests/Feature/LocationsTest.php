<?php

namespace Tests\Feature;

use App\Events\EndpointHit;
use App\Models\Audit;
use App\Models\File;
use App\Models\Location;
use App\Models\Organisation;
use App\Models\Service;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;
use Tests\TestCase;

class LocationsTest extends TestCase
{
    /*
     * List all the locations.
     */

    public function test_guest_can_list_them()
    {
        $location = factory(Location::class)->create();

        $response = $this->json('GET', '/core/v1/locations');

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCollection([
            'id',
            'has_image',
            'address_line_1',
            'address_line_2',
            'address_line_3',
            'city',
            'county',
            'postcode',
            'country',
            'lat',
            'lon',
            'accessibility_info',
            'has_wheelchair_access',
            'has_induction_loop',
            'created_at',
            'updated_at',
        ]);
        $response->assertJsonFragment([
            'id' => $location->id,
            'has_image' => $location->hasImage(),
            'address_line_1' => $location->address_line_1,
            'address_line_2' => $location->address_line_2,
            'address_line_3' => $location->address_line_3,
            'city' => $location->city,
            'county' => $location->county,
            'postcode' => $location->postcode,
            'country' => $location->country,
            'lat' => $location->lat,
            'lon' => $location->lon,
            'accessibility_info' => $location->accessibility_info,
            'has_wheelchair_access' => $location->has_wheelchair_access,
            'has_induction_loop' => $location->has_induction_loop,
            'created_at' => $location->created_at->format(CarbonImmutable::ISO8601),
            'updated_at' => $location->updated_at->format(CarbonImmutable::ISO8601),
        ]);
    }

    public function test_audit_created_when_listed()
    {
        $this->fakeEvents();

        $this->json('GET', '/core/v1/locations');

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) {
            return ($event->getAction() === Audit::ACTION_READ);
        });
    }

    /*
     * Create a location.
     */

    public function test_guest_cannot_create_one()
    {
        $response = $this->json('POST', '/core/v1/locations');

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_service_worker_cannot_create_one()
    {
        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = factory(Service::class)->create();
        $user = factory(User::class)->create();
        $this->makeServiceWorker($user, $service);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/locations');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_service_admin_can_create_one()
    {
        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = factory(Service::class)->create();
        $user = factory(User::class)->create();
        $this->makeServiceAdmin($user, $service);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/locations', [
            'address_line_1' => '30-34 Aire St',
            'address_line_2' => null,
            'address_line_3' => null,
            'city' => 'Leeds',
            'county' => 'West Yorkshire',
            'postcode' => 'LS1 4HT',
            'country' => 'England',
            'accessibility_info' => null,
            'has_wheelchair_access' => false,
            'has_induction_loop' => false,
        ]);

        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJsonFragment([
            'has_image' => false,
            'address_line_1' => '30-34 Aire St',
            'address_line_2' => null,
            'address_line_3' => null,
            'city' => 'Leeds',
            'county' => 'West Yorkshire',
            'postcode' => 'LS1 4HT',
            'country' => 'England',
            'accessibility_info' => null,
            'has_wheelchair_access' => false,
            'has_induction_loop' => false,
        ]);
    }

    public function test_audit_created_when_created()
    {
        $this->fakeEvents();

        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = factory(Service::class)->create();
        $user = factory(User::class)->create();
        $this->makeServiceAdmin($user, $service);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/locations', [
            'address_line_1' => '30-34 Aire St',
            'address_line_2' => null,
            'address_line_3' => null,
            'city' => 'Leeds',
            'county' => 'West Yorkshire',
            'postcode' => 'LS1 4HT',
            'country' => 'England',
            'accessibility_info' => null,
            'has_wheelchair_access' => false,
            'has_induction_loop' => false,
        ]);

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user, $response) {
            return ($event->getAction() === Audit::ACTION_CREATE) &&
                ($event->getUser()->id === $user->id) &&
                ($event->getModel()->id === $this->getResponseContent($response)['data']['id']);
        });
    }

    /*
     * Get a specific location.
     */

    public function test_guest_can_view_one()
    {
        $location = factory(Location::class)->create();

        $response = $this->json('GET', "/core/v1/locations/{$location->id}");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'id' => $location->id,
            'has_image' => $location->hasImage(),
            'address_line_1' => $location->address_line_1,
            'address_line_2' => $location->address_line_2,
            'address_line_3' => $location->address_line_3,
            'city' => $location->city,
            'county' => $location->county,
            'postcode' => $location->postcode,
            'country' => $location->country,
            'lat' => $location->lat,
            'lon' => $location->lon,
            'accessibility_info' => $location->accessibility_info,
            'has_wheelchair_access' => $location->has_wheelchair_access,
            'has_induction_loop' => $location->has_induction_loop,
            'created_at' => $location->created_at->format(CarbonImmutable::ISO8601),
            'updated_at' => $location->updated_at->format(CarbonImmutable::ISO8601),
        ]);
    }

    public function test_audit_created_when_viewed()
    {
        $this->fakeEvents();

        $location = factory(Location::class)->create();

        $this->json('GET', "/core/v1/locations/{$location->id}");

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($location) {
            return ($event->getAction() === Audit::ACTION_READ) &&
                ($event->getModel()->id === $location->id);
        });
    }

    /*
     * Update a specific location.
     */

    public function test_guest_cannot_update_one()
    {
        $location = factory(Location::class)->create();

        $response = $this->json('PUT', "/core/v1/locations/{$location->id}");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_service_worker_cannot_update_one()
    {
        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = factory(Service::class)->create();
        $user = factory(User::class)->create();
        $this->makeServiceWorker($user, $service);
        $location = factory(Location::class)->create();

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/locations/{$location->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_service_admin_can_update_one()
    {
        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = factory(Service::class)->create();
        $user = factory(User::class)->create();
        $this->makeServiceAdmin($user, $service);
        $location = factory(Location::class)->create();

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/locations/{$location->id}", [
            'address_line_1' => '30-34 Aire St',
            'address_line_2' => null,
            'address_line_3' => null,
            'city' => 'Leeds',
            'county' => 'West Yorkshire',
            'postcode' => 'LS1 4HT',
            'country' => 'England',
            'accessibility_info' => null,
            'has_wheelchair_access' => false,
            'has_induction_loop' => false,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'address_line_1' => '30-34 Aire St',
            'address_line_2' => null,
            'address_line_3' => null,
            'city' => 'Leeds',
            'county' => 'West Yorkshire',
            'postcode' => 'LS1 4HT',
            'country' => 'England',
            'accessibility_info' => null,
            'has_wheelchair_access' => false,
            'has_induction_loop' => false,
        ]);
    }

    public function test_audit_created_when_updated()
    {
        $this->fakeEvents();

        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = factory(Service::class)->create();
        $user = factory(User::class)->create();
        $this->makeServiceAdmin($user, $service);
        $location = factory(Location::class)->create();

        Passport::actingAs($user);

        $this->json('PUT', "/core/v1/locations/{$location->id}", [
            'address_line_1' => '30-34 Aire St',
            'address_line_2' => null,
            'address_line_3' => null,
            'city' => 'Leeds',
            'county' => 'West Yorkshire',
            'postcode' => 'LS1 4HT',
            'country' => 'England',
            'accessibility_info' => null,
            'has_wheelchair_access' => false,
            'has_induction_loop' => false,
        ]);

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user, $location) {
            return ($event->getAction() === Audit::ACTION_UPDATE) &&
                ($event->getUser()->id === $user->id) &&
                ($event->getModel()->id === $location->id);
        });
    }

    public function test_only_partial_fields_can_be_updated()
    {
        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = factory(Service::class)->create();
        $user = factory(User::class)->create();
        $this->makeServiceAdmin($user, $service);
        $location = factory(Location::class)->create();

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/locations/{$location->id}", [
            'address_line_1' => '30-34 Aire St',
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'address_line_1' => '30-34 Aire St',
        ]);
    }

    /*
     * Delete a specific location.
     */

    public function test_guest_cannot_delete_one()
    {
        $location = factory(Location::class)->create();

        $response = $this->json('DELETE', "/core/v1/locations/{$location->id}");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_service_worker_cannot_delete_one()
    {
        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = factory(Service::class)->create();
        $user = factory(User::class)->create();
        $this->makeServiceWorker($user, $service);
        $location = factory(Location::class)->create();

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/locations/{$location->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_service_admin_cannot_delete_one()
    {
        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = factory(Service::class)->create();
        $user = factory(User::class)->create();
        $this->makeServiceAdmin($user, $service);
        $location = factory(Location::class)->create();

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/locations/{$location->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_organisation_admin_cannot_delete_one()
    {
        /**
         * @var \App\Models\Organisation $organisation
         * @var \App\Models\User $user
         */
        $organisation = factory(Organisation::class)->create();
        $user = factory(User::class)->create();
        $this->makeOrganisationAdmin($user, $organisation);
        $location = factory(Location::class)->create();

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/locations/{$location->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_global_admin_can_delete_one()
    {
        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $this->makeGlobalAdmin($user);
        $location = factory(Location::class)->create();

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/locations/{$location->id}");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseMissing((new Location())->getTable(), ['id' => $location->id]);
    }

    public function test_audit_created_when_deleted()
    {
        $this->fakeEvents();

        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $this->makeGlobalAdmin($user);
        $location = factory(Location::class)->create();

        Passport::actingAs($user);

        $this->json('DELETE', "/core/v1/locations/{$location->id}");

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user, $location) {
            return ($event->getAction() === Audit::ACTION_DELETE) &&
                ($event->getUser()->id === $user->id) &&
                ($event->getModel()->id === $location->id);
        });
    }

    /*
     * Get a specific location's image.
     */

    public function test_guest_can_view_image()
    {
        $location = factory(Location::class)->create();

        $response = $this->get("/core/v1/locations/{$location->id}/image.png");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('Content-Type', 'image/png');
    }

    public function test_audit_created_when_image_viewed()
    {
        $this->fakeEvents();

        $location = factory(Location::class)->create();

        $this->get("/core/v1/locations/{$location->id}/image.png");

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($location) {
            return ($event->getAction() === Audit::ACTION_READ) &&
                ($event->getModel()->id === $location->id);
        });
    }

    /*
     * Upload a specific location's image.
     */


    public function test_organisation_admin_can_upload_image()
    {
        /** @var \App\Models\User $user */
        $user = factory(User::class)->create();
        $this->makeGlobalAdmin($user);
        $image = Storage::disk('local')->get('/test-data/image.png');

        Passport::actingAs($user);

        $imageResponse = $this->json('POST', '/core/v1/files', [
            'is_private' => false,
            'mime_type' => 'image/png',
            'file' => 'data:image/png;base64,' . base64_encode($image),
        ]);

        $response = $this->json('POST', '/core/v1/locations', [
            'address_line_1' => '30-34 Aire St',
            'address_line_2' => null,
            'address_line_3' => null,
            'city' => 'Leeds',
            'county' => 'West Yorkshire',
            'postcode' => 'LS1 4HT',
            'country' => 'England',
            'accessibility_info' => null,
            'has_wheelchair_access' => false,
            'has_induction_loop' => false,
            'image_file_id' => $this->getResponseContent($imageResponse, 'data.id'),
        ]);
        $locationId = $this->getResponseContent($response, 'data.id');

        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJsonFragment([
            'id' => $locationId,
            'address_line_1' => '30-34 Aire St',
            'address_line_2' => null,
            'address_line_3' => null,
            'city' => 'Leeds',
            'county' => 'West Yorkshire',
            'postcode' => 'LS1 4HT',
            'country' => 'England',
            'accessibility_info' => null,
            'has_wheelchair_access' => false,
            'has_induction_loop' => false,
            'has_image' => true,
        ]);
    }

    /*
     * Delete a specific location's image.
     */

    public function test_organisation_admin_can_delete_image()
    {
        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $this->makeGlobalAdmin($user);
        $location = factory(Location::class)->create([
            'image_file_id' => factory(File::class)->create()->id,
        ]);
        $payload = [
            'address_line_1' => $location->address_line_1,
            'address_line_2' => $location->address_line_2,
            'address_line_3' => $location->address_line_3,
            'city' => $location->city,
            'county' => $location->county,
            'postcode' => $location->postcode,
            'country' => $location->country,
            'accessibility_info' => $location->accessibility_info,
            'has_wheelchair_access' => $location->has_wheelchair_access,
            'has_induction_loop' => $location->has_induction_loop,
            'image_file_id' => null,
        ];

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/locations/{$location->id}", $payload);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'id' => $location->id,
            'address_line_1' => $location->address_line_1,
            'address_line_2' => $location->address_line_2,
            'address_line_3' => $location->address_line_3,
            'city' => $location->city,
            'county' => $location->county,
            'postcode' => $location->postcode,
            'country' => $location->country,
            'accessibility_info' => $location->accessibility_info,
            'has_wheelchair_access' => $location->has_wheelchair_access,
            'has_induction_loop' => $location->has_induction_loop,
            'has_image' => false,
        ]);
    }
}
