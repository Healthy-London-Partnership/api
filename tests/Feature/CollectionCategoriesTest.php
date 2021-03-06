<?php

namespace Tests\Feature;

use App\Events\EndpointHit;
use App\Models\Audit;
use App\Models\Collection;
use App\Models\CollectionTaxonomy;
use App\Models\File;
use App\Models\Organisation;
use App\Models\Service;
use App\Models\Taxonomy;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;
use Tests\TestCase;

class CollectionCategoriesTest extends TestCase
{
    /*
     * List all the category collections.
     */

    public function test_guest_can_list_them()
    {
        $response = $this->json('GET', '/core/v1/collections/categories');

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCollection([
            'id',
            'slug',
            'name',
            'intro',
            'order',
            'sideboxes' => [
                '*' => [
                    'title',
                    'content',
                ],
            ],
            'category_taxonomies' => [
                '*' => [
                    'id',
                    'parent_id',
                    'name',
                    'created_at',
                    'updated_at',
                ],
            ],
            'created_at',
            'updated_at',
        ]);
    }

    public function test_audit_created_when_listed()
    {
        $this->fakeEvents();

        $this->json('GET', '/core/v1/collections/categories');

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) {
            return ($event->getAction() === Audit::ACTION_READ);
        });
    }

    /*
     * Create a collection category.
     */

    public function test_guest_cannot_create_one()
    {
        $response = $this->json('POST', '/core/v1/collections/categories');

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

        $response = $this->json('POST', '/core/v1/collections/categories');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_service_admin_cannot_create_one()
    {
        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = factory(Service::class)->create();
        $user = factory(User::class)->create();
        $this->makeServiceAdmin($user, $service);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/categories');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_organisation_admin_cannot_create_one()
    {
        /**
         * @var \App\Models\Organisation $organisation
         * @var \App\Models\User $user
         */
        $organisation = factory(Organisation::class)->create();
        $user = factory(User::class)->create();
        $this->makeOrganisationAdmin($user, $organisation);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/categories');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_global_admin_cannot_create_one()
    {
        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $this->makeGlobalAdmin($user);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/categories');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_super_admin_can_create_one()
    {
        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $this->makeSuperAdmin($user);
        $randomCategory = Taxonomy::category()->children()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/categories', [
            'name' => 'Test Category',
            'intro' => 'Lorem ipsum',
            'order' => 1,
            'sideboxes' => [
                [
                    'title' => 'Sidebox title',
                    'content' => 'Sidebox content',
                ],
            ],
            'category_taxonomies' => [$randomCategory->id],
        ]);

        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJsonResource([
            'id',
            'slug',
            'name',
            'intro',
            'order',
            'sideboxes' => [
                '*' => [
                    'title',
                    'content',
                ],
            ],
            'category_taxonomies' => [
                '*' => [
                    'id',
                    'parent_id',
                    'name',
                    'created_at',
                    'updated_at',
                ],
            ],
            'created_at',
            'updated_at',
        ]);
        $response->assertJsonFragment([
            'name' => 'Test Category',
            'intro' => 'Lorem ipsum',
            'order' => 1,
            'sideboxes' => [
                [
                    'title' => 'Sidebox title',
                    'content' => 'Sidebox content',
                ],
            ],
        ]);
        $response->assertJsonFragment([
            'id' => $randomCategory->id,
        ]);
    }

    public function test_order_is_updated_when_created_at_beginning()
    {
        // Delete the existing seeded categories.
        $this->truncateCollectionCategories();

        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $this->makeSuperAdmin($user);
        $first = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'first',
            'name' => 'First',
            'order' => 1,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);
        $second = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'seconds',
            'name' => 'Second',
            'order' => 2,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);
        $third = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'third',
            'name' => 'Third',
            'order' => 3,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/categories', [
            'name' => 'Test Category',
            'intro' => 'Lorem ipsum',
            'order' => 1,
            'sideboxes' => [],
            'category_taxonomies' => [],
        ]);

        $response->assertStatus(Response::HTTP_CREATED);
        $this->assertDatabaseHas((new Collection())->getTable(), ['order' => 1]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $first->id, 'order' => 2]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $second->id, 'order' => 3]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $third->id, 'order' => 4]);
    }

    public function test_order_is_updated_when_created_at_middle()
    {
        // Delete the existing seeded categories.
        $this->truncateCollectionCategories();

        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $this->makeSuperAdmin($user);
        $first = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'first',
            'name' => 'First',
            'order' => 1,
            'meta' => [
                'intro' => 'Lorem ipsum',

                'sideboxes' => [],
            ],
        ]);
        $second = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'second',
            'name' => 'Second',
            'order' => 2,
            'meta' => [
                'intro' => 'Lorem ipsum',

                'sideboxes' => [],
            ],
        ]);
        $third = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'third',
            'name' => 'Third',
            'order' => 3,
            'meta' => [
                'intro' => 'Lorem ipsum',

                'sideboxes' => [],
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/categories', [
            'name' => 'Test Category',
            'intro' => 'Lorem ipsum',
            'order' => 2,
            'sideboxes' => [],
            'category_taxonomies' => [],
        ]);

        $response->assertStatus(Response::HTTP_CREATED);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $first->id, 'order' => 1]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['order' => 2]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $second->id, 'order' => 3]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $third->id, 'order' => 4]);
    }

    public function test_order_is_updated_when_created_at_end()
    {
        // Delete the existing seeded categories.
        $this->truncateCollectionCategories();

        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $this->makeSuperAdmin($user);
        $first = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'first',
            'name' => 'First',
            'order' => 1,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);
        $second = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'second',
            'name' => 'Second',
            'order' => 2,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);
        $third = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'third',
            'name' => 'Third',
            'order' => 3,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/categories', [
            'name' => 'Test Category',
            'intro' => 'Lorem ipsum',
            'order' => 4,
            'sideboxes' => [],
            'category_taxonomies' => [],
        ]);

        $response->assertStatus(Response::HTTP_CREATED);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $first->id, 'order' => 1]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $second->id, 'order' => 2]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $third->id, 'order' => 3]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['order' => 4]);
    }

    public function test_order_cannot_be_less_than_1_when_created()
    {
        // Delete the existing seeded categories.
        $this->truncateCollectionCategories();

        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $this->makeSuperAdmin($user);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/categories', [
            'name' => 'Test Category',
            'intro' => 'Lorem ipsum',
            'order' => 0,
            'sideboxes' => [],
            'category_taxonomies' => [],
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_order_cannot_be_greater_than_count_plus_1_when_created()
    {
        // Delete the existing seeded categories.
        $this->truncateCollectionCategories();

        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $this->makeSuperAdmin($user);
        Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'first',
            'name' => 'First',
            'order' => 1,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);
        Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'second',
            'name' => 'Second',
            'order' => 2,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/categories', [
            'name' => 'Test Category',
            'intro' => 'Lorem ipsum',
            'order' => 4,
            'sideboxes' => [],
            'category_taxonomies' => [],
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_audit_created_when_created()
    {
        $this->fakeEvents();

        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $this->makeSuperAdmin($user);
        $randomCategory = Taxonomy::category()->children()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/categories', [
            'name' => 'Test Category',
            'intro' => 'Lorem ipsum',
            'order' => 1,
            'sideboxes' => [],
            'category_taxonomies' => [$randomCategory->id],
        ]);

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user, $response) {
            return ($event->getAction() === Audit::ACTION_CREATE) &&
                ($event->getUser()->id === $user->id) &&
                ($event->getModel()->id === $this->getResponseContent($response)['data']['id']);
        });
    }

    /*
     * Get a specific category collection.
     */

    public function test_guest_can_view_one()
    {
        $collectionCategory = Collection::categories()->inRandomOrder()->firstOrFail();

        $response = $this->json('GET', "/core/v1/collections/categories/{$collectionCategory->id}");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonResource([
            'id',
            'slug',
            'name',
            'intro',
            'order',
            'sideboxes' => [
                '*' => [
                    'title',
                    'content',
                ],
            ],
            'category_taxonomies' => [
                '*' => [
                    'id',
                    'parent_id',
                    'name',
                    'created_at',
                    'updated_at',
                ],
            ],
            'created_at',
            'updated_at',
        ]);
        $response->assertJsonFragment([
            'id' => $collectionCategory->id,
            'slug' => $collectionCategory->slug,
            'name' => $collectionCategory->name,
            'intro' => $collectionCategory->meta['intro'],
            'order' => $collectionCategory->order,
            'sideboxes' => $collectionCategory->meta['sideboxes'],
            'created_at' => $collectionCategory->created_at->format(CarbonImmutable::ISO8601),
            'updated_at' => $collectionCategory->updated_at->format(CarbonImmutable::ISO8601),
        ]);
    }

    public function test_guest_can_view_one_by_slug()
    {
        $collectionCategory = Collection::categories()->inRandomOrder()->firstOrFail();

        $response = $this->json('GET', "/core/v1/collections/categories/{$collectionCategory->slug}");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonResource([
            'id',
            'slug',
            'name',
            'intro',
            'order',
            'sideboxes' => [
                '*' => [
                    'title',
                    'content',
                ],
            ],
            'category_taxonomies' => [
                '*' => [
                    'id',
                    'parent_id',
                    'name',
                    'created_at',
                    'updated_at',
                ],
            ],
            'created_at',
            'updated_at',
        ]);
        $response->assertJsonFragment([
            'id' => $collectionCategory->id,
            'slug' => $collectionCategory->slug,
            'name' => $collectionCategory->name,
            'intro' => $collectionCategory->meta['intro'],
            'order' => $collectionCategory->order,
            'sideboxes' => $collectionCategory->meta['sideboxes'],
            'created_at' => $collectionCategory->created_at->format(CarbonImmutable::ISO8601),
            'updated_at' => $collectionCategory->updated_at->format(CarbonImmutable::ISO8601),
        ]);
    }

    public function test_audit_created_when_viewed()
    {
        $this->fakeEvents();

        $collectionCategory = Collection::categories()->inRandomOrder()->firstOrFail();

        $response = $this->json('GET', "/core/v1/collections/categories/{$collectionCategory->id}");

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($response) {
            return ($event->getAction() === Audit::ACTION_READ) &&
                ($event->getModel()->id === $this->getResponseContent($response)['data']['id']);
        });
    }

    /*
     * Update a specific category collection.
     */

    public function test_guest_cannot_update_one()
    {
        $category = Collection::categories()->inRandomOrder()->firstOrFail();

        $response = $this->json('PUT', "/core/v1/collections/categories/{$category->id}");

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
        $category = Collection::categories()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/categories/{$category->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_service_admin_cannot_update_one()
    {
        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = factory(Service::class)->create();
        $user = factory(User::class)->create();
        $this->makeServiceAdmin($user, $service);
        $category = Collection::categories()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/categories/{$category->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_organisation_admin_cannot_update_one()
    {
        /**
         * @var \App\Models\Organisation $organisation
         * @var \App\Models\User $user
         */
        $organisation = factory(Organisation::class)->create();
        $user = factory(User::class)->create();
        $this->makeOrganisationAdmin($user, $organisation);
        $category = Collection::categories()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/categories/{$category->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_global_admin_can_update_one()
    {
        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $this->makeGlobalAdmin($user);
        $category = Collection::categories()->inRandomOrder()->firstOrFail();
        $taxonomy = Taxonomy::category()->children()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/categories/{$category->id}", [
            'name' => 'Test Category',
            'intro' => 'Lorem ipsum',
            'order' => 1,
            'sideboxes' => [],
            'category_taxonomies' => [$taxonomy->id],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonResource([
            'id',
            'slug',
            'name',
            'intro',
            'order',
            'sideboxes' => [
                '*' => [
                    'title',
                    'content',
                ],
            ],
            'category_taxonomies' => [
                '*' => [
                    'id',
                    'parent_id',
                    'name',
                    'created_at',
                    'updated_at',
                ],
            ],
            'created_at',
            'updated_at',
        ]);
        $response->assertJsonFragment([
            'name' => 'Test Category',
            'intro' => 'Lorem ipsum',
            'order' => 1,
            'sideboxes' => [],
        ]);
        $response->assertJsonFragment([
            'id' => $taxonomy->id,
        ]);
    }

    public function test_order_is_updated_when_updated_to_beginning()
    {
        // Delete the existing seeded categories.
        $this->truncateCollectionCategories();

        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $this->makeSuperAdmin($user);
        $first = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'first',
            'name' => 'First',
            'order' => 1,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);
        $second = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'second',
            'name' => 'Second',
            'order' => 2,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);
        $third = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'third',
            'name' => 'Third',
            'order' => 3,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/categories/{$third->id}", [
            'name' => 'Third',
            'intro' => 'Lorem ipsum',
            'order' => 1,
            'sideboxes' => [],
            'category_taxonomies' => [],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $first->id, 'order' => 2]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $second->id, 'order' => 3]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $third->id, 'order' => 1]);
    }

    public function test_order_is_updated_when_updated_to_middle()
    {
        // Delete the existing seeded categories.
        $this->truncateCollectionCategories();

        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $this->makeSuperAdmin($user);
        $first = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'first',
            'name' => 'First',
            'order' => 1,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);
        $second = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'second',
            'name' => 'Second',
            'order' => 2,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);
        $third = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'third',
            'name' => 'Third',
            'order' => 3,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/categories/{$first->id}", [
            'name' => 'First',
            'intro' => 'Lorem ipsum',
            'order' => 2,
            'sideboxes' => [],
            'category_taxonomies' => [],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $first->id, 'order' => 2]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $second->id, 'order' => 1]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $third->id, 'order' => 3]);
    }

    public function test_order_is_updated_when_updated_to_end()
    {
        // Delete the existing seeded categories.
        $this->truncateCollectionCategories();

        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $this->makeSuperAdmin($user);
        $first = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'first',
            'name' => 'First',
            'order' => 1,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);
        $second = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'second',
            'name' => 'Second',
            'order' => 2,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);
        $third = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'third',
            'name' => 'Third',
            'order' => 3,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/categories/{$first->id}", [
            'name' => 'First',
            'intro' => 'Lorem ipsum',
            'order' => 3,
            'sideboxes' => [],
            'category_taxonomies' => [],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $first->id, 'order' => 3]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $second->id, 'order' => 1]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $third->id, 'order' => 2]);
    }

    public function test_order_cannot_be_less_than_1_when_updated()
    {
        // Delete the existing seeded categories.
        $this->truncateCollectionCategories();

        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $this->makeSuperAdmin($user);
        $category = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'first',
            'name' => 'First',
            'order' => 1,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/categories/{$category->id}", [
            'name' => 'First',
            'intro' => 'Lorem ipsum',
            'order' => 0,
            'sideboxes' => [],
            'category_taxonomies' => [],
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_order_cannot_be_greater_than_count_plus_1_when_updated()
    {
        // Delete the existing seeded categories.
        $this->truncateCollectionCategories();

        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $this->makeSuperAdmin($user);
        $category = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'first',
            'name' => 'First',
            'order' => 1,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/categories/{$category->id}", [
            'name' => 'First',
            'intro' => 'Lorem ipsum',
            'order' => 2,
            'sideboxes' => [],
            'category_taxonomies' => [],
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_audit_created_when_updated()
    {
        $this->fakeEvents();

        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $this->makeSuperAdmin($user);
        $category = Collection::categories()->inRandomOrder()->firstOrFail();
        $taxonomy = Taxonomy::category()->children()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $this->json('PUT', "/core/v1/collections/categories/{$category->id}", [
            'name' => 'Test Category',
            'intro' => 'Lorem ipsum',
            'order' => 1,
            'sideboxes' => [],
            'category_taxonomies' => [$taxonomy->id],
        ]);

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user, $category) {
            return ($event->getAction() === Audit::ACTION_UPDATE) &&
                ($event->getUser()->id === $user->id) &&
                ($event->getModel()->id === $category->id);
        });
    }

    /*
     * Delete a specific category collection.
     */

    public function test_guest_cannot_delete_one()
    {
        $category = Collection::categories()->inRandomOrder()->firstOrFail();

        $response = $this->json('DELETE', "/core/v1/collections/categories/{$category->id}");

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
        $category = Collection::categories()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/collections/categories/{$category->id}");

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
        $category = Collection::categories()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/collections/categories/{$category->id}");

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
        $category = Collection::categories()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/collections/categories/{$category->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_global_admin_cannot_delete_one()
    {
        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $this->makeGlobalAdmin($user);
        $category = Collection::categories()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/collections/categories/{$category->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_super_admin_can_delete_one()
    {
        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $this->makeSuperAdmin($user);
        $category = Collection::categories()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/collections/categories/{$category->id}");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseMissing((new Collection())->getTable(), ['id' => $category->id]);
        $this->assertDatabaseMissing((new CollectionTaxonomy())->getTable(), ['collection_id' => $category->id]);
    }

    public function test_order_is_updated_when_deleted_at_beginning()
    {
        // Delete the existing seeded categories.
        $this->truncateCollectionCategories();

        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $this->makeSuperAdmin($user);
        $first = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'first',
            'name' => 'First',
            'order' => 1,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);
        $second = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'second',
            'name' => 'Second',
            'order' => 2,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);
        $third = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'third',
            'name' => 'Third',
            'order' => 3,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/collections/categories/{$first->id}");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseMissing((new Collection())->getTable(), ['id' => $first->id]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $second->id, 'order' => 1]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $third->id, 'order' => 2]);
    }

    public function test_order_is_updated_when_deleted_at_middle()
    {
        // Delete the existing seeded categories.
        $this->truncateCollectionCategories();

        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $this->makeSuperAdmin($user);
        $first = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'first',
            'name' => 'First',
            'order' => 1,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);
        $second = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'second',
            'name' => 'Second',
            'order' => 2,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);
        $third = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'third',
            'name' => 'Third',
            'order' => 3,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/collections/categories/{$second->id}");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseMissing((new Collection())->getTable(), ['id' => $second->id]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $first->id, 'order' => 1]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $third->id, 'order' => 2]);
    }

    public function test_order_is_updated_when_deleted_at_end()
    {
        // Delete the existing seeded categories.
        $this->truncateCollectionCategories();

        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $this->makeSuperAdmin($user);
        $first = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'first',
            'name' => 'First',
            'order' => 1,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);
        $second = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'second',
            'name' => 'Second',
            'order' => 2,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);
        $third = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'third',
            'name' => 'Third',
            'order' => 3,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/collections/categories/{$third->id}");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseMissing((new Collection())->getTable(), ['id' => $third->id]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $first->id, 'order' => 1]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $second->id, 'order' => 2]);
    }

    public function test_audit_created_when_deleted()
    {
        $this->fakeEvents();

        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $this->makeSuperAdmin($user);
        $category = Collection::categories()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $this->json('DELETE', "/core/v1/collections/categories/{$category->id}");

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user, $category) {
            return ($event->getAction() === Audit::ACTION_DELETE) &&
                ($event->getUser()->id === $user->id) &&
                ($event->getModel()->id === $category->id);
        });
    }

    /*
     * Get a specific category collection's image.
     */

    public function test_guest_can_view_image()
    {
        $category = Collection::categories()->inRandomOrder()->firstOrFail();

        $response = $this->get("/core/v1/collections/categories/{$category->id}/image.png");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('Content-Type', 'image/png');
    }

    public function test_audit_created_when_image_viewed()
    {
        $this->fakeEvents();

        $category = Collection::categories()->inRandomOrder()->firstOrFail();

        $this->get("/core/v1/collections/categories/{$category->id}/image.png");

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($category) {
            return ($event->getAction() === Audit::ACTION_READ) &&
                ($event->getModel()->id === $category->id);
        });
    }

    /*
     * Upload a specific category collection's image.
     */

    public function test_super_admin_can_upload_image()
    {
        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $this->makeSuperAdmin($user);
        $randomCategory = Taxonomy::category()->children()->inRandomOrder()->firstOrFail();
        $image = Storage::disk('local')->get('/test-data/image.png');

        Passport::actingAs($user);

        $imageResponse = $this->json('POST', '/core/v1/files', [
            'is_private' => false,
            'mime_type' => 'image/png',
            'file' => 'data:image/png;base64,' . base64_encode($image),
        ]);

        $response = $this->json('POST', '/core/v1/collections/categories', [
            'name' => 'Test Category',
            'intro' => 'Lorem ipsum',
            'order' => 1,
            'sideboxes' => [],
            'category_taxonomies' => [$randomCategory->id],
            'image_file_id' => $this->getResponseContent($imageResponse, 'data.id'),
        ]);

        $response->assertStatus(Response::HTTP_CREATED);
        $collectionArray = $this->getResponseContent($response)['data'];
        $content = $this->get("/core/v1/collections/categories/{$collectionArray['id']}/image.png")->content();
        $this->assertEquals($image, $content);
    }

    /*
     * Delete a specific category collection's image.
     */

    public function test_super_admin_can_delete_image()
    {
        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $this->makeSuperAdmin($user);
        $category = Collection::categories()->inRandomOrder()->firstOrFail();
        $meta = $category->meta;
        $meta['image_file_id'] = factory(File::class)->create()->id;
        $category->meta = $meta;
        $category->save();

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/categories/{$category->id}", [
            'name' => $category->name,
            'intro' => $category->meta['intro'],
            'order' => $category->order,
            'sideboxes' => [],
            'category_taxonomies' => $category->taxonomies()->pluck(table(Taxonomy::class, 'id')),
            'image_file_id' => null,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $category = $category->fresh();
        $this->assertEquals(null, $category->meta['image_file_id']);
    }
}
