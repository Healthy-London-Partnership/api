<?php

namespace Tests\Feature;

use App\Models\Collection;
use App\Models\Location;
use App\Models\Organisation;
use App\Models\Service;
use App\Models\ServiceLocation;
use App\Models\Taxonomy;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\UsesElasticsearch;

class SearchTest extends TestCase implements UsesElasticsearch
{
    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->truncateTaxonomies();
        $this->truncateCollectionCategories();
        $this->truncateCollectionPersonas();
    }

    /*
     * Perform a search for services.
     */

    public function test_guest_can_search()
    {
        $response = $this->json('POST', '/core/v1/search', [
            'query' => 'test',
        ]);

        $response->assertStatus(Response::HTTP_OK);
    }

    public function test_query_matches_service_name()
    {
        $service = factory(Service::class)->create();

        $response = $this->json('POST', '/core/v1/search', [
            'query' => $service->name,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'id' => $service->id,
        ]);
    }

    public function test_query_matches_service_description()
    {
        $service = factory(Service::class)->create();

        $response = $this->json('POST', '/core/v1/search', [
            'query' => $service->description,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'id' => $service->id,
        ]);
    }

    public function test_query_matches_taxonomy_name()
    {
        $service = factory(Service::class)->create();
        $taxonomy = Taxonomy::category()->children()->create([
            'slug' => 'phpunit-taxonomy',
            'name' => 'PHPUnit Taxonomy',
            'order' => 1,
        ]);
        $service->serviceTaxonomies()->create(['taxonomy_id' => $taxonomy->id]);

        $response = $this->json('POST', '/core/v1/search', [
            'query' => $taxonomy->name,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $service->id]);
    }

    public function test_query_matches_partial_taxonomy_name()
    {
        $service = factory(Service::class)->create();
        $taxonomy = Taxonomy::category()->children()->create([
            'slug' => 'phpunit-taxonomy',
            'name' => 'PHPUnit Taxonomy',
            'order' => 1,
        ]);
        $service->serviceTaxonomies()->create(['taxonomy_id' => $taxonomy->id]);

        $response = $this->json('POST', '/core/v1/search', [
            'query' => 'PHPUnit',
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $service->id]);
    }

    public function test_query_matches_organisation_name()
    {
        $service = factory(Service::class)->create();

        $response = $this->json('POST', '/core/v1/search', [
            'query' => $service->organisation->name,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'id' => $service->id,
        ]);
    }

    public function test_query_ranks_service_name_above_organisation_name()
    {
        $organisation = factory(Organisation::class)->create(['name' => 'Test Name']);
        $serviceWithRelevantOrganisationName = factory(Service::class)->create(['organisation_id' => $organisation->id]);
        $serviceWithRelevantServiceName = factory(Service::class)->create(['name' => 'Test Name']);

        $response = $this->json('POST', '/core/v1/search', [
            'query' => 'Test Name',
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $results = json_decode($response->getContent(), true)['data'];
        $this->assertEquals($serviceWithRelevantServiceName->id, $results[0]['id']);
        $this->assertEquals($serviceWithRelevantOrganisationName->id, $results[1]['id']);
    }

    public function test_query_matches_service_intro()
    {
        $service = factory(Service::class)->create([
            'intro' => 'This is a service that helps to homeless find temporary housing.',
        ]);

        $response = $this->json('POST', '/core/v1/search', [
            'query' => 'housing',
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'id' => $service->id,
        ]);
    }

    public function test_query_matches_single_word_from_service_description()
    {
        $service = factory(Service::class)->create([
            'description' => 'This is a service that helps to homeless find temporary housing.',
        ]);

        $response = $this->json('POST', '/core/v1/search', [
            'query' => 'homeless',
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $service->id]);
    }

    public function test_query_matches_multiple_words_from_service_description()
    {
        $service = factory(Service::class)->create([
            'description' => 'This is a service that helps to homeless find temporary housing.',
        ]);

        $response = $this->json('POST', '/core/v1/search', [
            'query' => 'temporary housing',
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $service->id]);
    }

    public function test_filter_by_categories_works()
    {
        $service1 = factory(Service::class)->create();
        $service2 = factory(Service::class)->create();
        $collection1 = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'self-help',
            'name' => 'Self Help',
            'meta' => [],
            'order' => 1,
        ]);
        $collection2 = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'addiction',
            'name' => 'Addiction',
            'meta' => [],
            'order' => 2,
        ]);
        $taxonomy1 = Taxonomy::category()->children()->create([
            'slug' => 'test-taxonomy-1',
            'name' => 'Test Taxonomy 1',
            'order' => 1,
        ]);
        $taxonomy2 = Taxonomy::category()->children()->create([
            'slug' => 'test-taxonomy-2',
            'name' => 'Test Taxonomy 2',
            'order' => 2,
        ]);
        $collection1->collectionTaxonomies()->create(['taxonomy_id' => $taxonomy1->id]);
        $service1->serviceTaxonomies()->create(['taxonomy_id' => $taxonomy1->id]);
        $service1->save();

        $collection2->collectionTaxonomies()->create(['taxonomy_id' => $taxonomy2->id]);
        $service2->serviceTaxonomies()->create(['taxonomy_id' => $taxonomy2->id]);
        $service2->save();

        $response = $this->json('POST', '/core/v1/search', [
            'category' => $collection1->slug,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $service1->id]);
        $response->assertJsonMissing(['id' => $service2->id]);

        $response = $this->json('POST', '/core/v1/search', [
            'category' => implode(',', [$collection1->slug, $collection2->slug]),
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $service1->id]);
        $response->assertJsonFragment(['id' => $service2->id]);
    }

    public function test_filter_by_personas_works()
    {
        $service1 = factory(Service::class)->create();
        $service2 = factory(Service::class)->create();
        $collection1 = Collection::create([
            'type' => Collection::TYPE_PERSONA,
            'slug' => 'refugees',
            'name' => 'Refugees',
            'meta' => [],
            'order' => 1,
        ]);
        $collection2 = Collection::create([
            'type' => Collection::TYPE_PERSONA,
            'slug' => 'homeless',
            'name' => 'Homeless',
            'meta' => [],
            'order' => 2,
        ]);
        $taxonomy1 = Taxonomy::category()->children()->create([
            'slug' => 'test-taxonomy-1',
            'name' => 'Test Taxonomy 1',
            'order' => 1,
        ]);
        $taxonomy2 = Taxonomy::category()->children()->create([
            'slug' => 'test-taxonomy-2',
            'name' => 'Test Taxonomy 2',
            'order' => 2,
        ]);
        $collection1->collectionTaxonomies()->create(['taxonomy_id' => $taxonomy1->id]);
        $service1->serviceTaxonomies()->create(['taxonomy_id' => $taxonomy1->id]);
        $service1->save();

        $collection2->collectionTaxonomies()->create(['taxonomy_id' => $taxonomy2->id]);
        $service2->serviceTaxonomies()->create(['taxonomy_id' => $taxonomy2->id]);
        $service2->save();

        $response = $this->json('POST', '/core/v1/search', [
            'persona' => $collection1->slug,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $service1->id]);
        $response->assertJsonMissing(['id' => $service2->id]);

        $response = $this->json('POST', '/core/v1/search', [
            'persona' => implode(',', [$collection1->slug, $collection2->slug]),
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $service1->id]);
        $response->assertJsonFragment(['id' => $service2->id]);
    }

    public function test_filter_by_is_free_works()
    {
        $paidService = factory(Service::class)->create(['is_free' => false]);
        $freeService = factory(Service::class)->create(['is_free' => true]);

        $response = $this->json('POST', '/core/v1/search', [
            'is_free' => true,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $freeService->id]);
        $response->assertJsonMissing(['id' => $paidService->id]);
    }

    public function test_filter_by_is_national_works()
    {
        $localService = factory(Service::class)->create(['is_national' => false]);
        $nationalService = factory(Service::class)->create(['is_national' => true]);

        $response = $this->json('POST', '/core/v1/search', [
            'is_national' => false,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $localService->id]);
        $response->assertJsonMissing(['id' => $nationalService->id]);
    }

    public function test_order_by_location_works()
    {
        $service = factory(Service::class)->create(['is_national' => false]);
        $serviceLocation = factory(ServiceLocation::class)->create(['service_id' => $service->id]);
        DB::table('locations')->where('id', $serviceLocation->location->id)->update(['lat' => 19.9, 'lon' => 19.9]);
        $service->save();

        $service2 = factory(Service::class)->create(['is_national' => false]);
        $serviceLocation2 = factory(ServiceLocation::class)->create(['service_id' => $service2->id]);
        DB::table('locations')->where('id', $serviceLocation2->location->id)->update(['lat' => 20, 'lon' => 20]);
        $service2->save();

        $service3 = factory(Service::class)->create(['is_national' => false]);
        $serviceLocation3 = factory(ServiceLocation::class)->create(['service_id' => $service3->id]);
        DB::table('locations')->where('id', $serviceLocation3->location->id)->update(['lat' => 20.15, 'lon' => 20.15]);
        $service3->save();

        $response = $this->json('POST', '/core/v1/search', [
            'order' => 'distance',
            'location' => [
                'lat' => 20,
                'lon' => 20,
            ],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $service2->id]);
        $hits = json_decode($response->getContent(), true)['data'];
        $this->assertEquals($service2->id, $hits[0]['id']);
        $this->assertEquals($service->id, $hits[1]['id']);
        $this->assertEquals($service3->id, $hits[2]['id']);
    }

    public function test_query_and_filter_works()
    {
        $service = factory(Service::class)->create(['name' => 'Ayup Digital']);
        $collection = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'self-help',
            'name' => 'Self Help',
            'meta' => [],
            'order' => 1,
        ]);
        $taxonomy = Taxonomy::category()->children()->create([
            'slug' => 'collection',
            'name' => 'Collection',
            'order' => 1,
        ]);
        $collectionTaxonomy = $collection->collectionTaxonomies()->create(['taxonomy_id' => $taxonomy->id]);
        $service->serviceTaxonomies()->create(['taxonomy_id' => $taxonomy->id]);
        $service->save();

        $differentService = factory(Service::class)->create(['name' => 'Ayup Digital']);
        $differentCollection = Collection::create([
            'type' => Collection::TYPE_PERSONA,
            'slug' => 'refugees',
            'name' => 'Refugees',
            'meta' => [],
            'order' => 1,
        ]);
        $differentTaxonomy = Taxonomy::category()->children()->create([
            'slug' => 'persona',
            'name' => 'Persona',
            'order' => 2,
        ]);
        $differentCollection->collectionTaxonomies()->create(['taxonomy_id' => $differentTaxonomy->id]);
        $differentService->serviceTaxonomies()->create(['taxonomy_id' => $differentTaxonomy->id]);
        $differentService->save();

        $response = $this->json('POST', '/core/v1/search', [
            'query' => 'Ayup Digital',
            'category' => $collectionTaxonomy->collection->slug,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $service->id]);
        $response->assertJsonMissing(['id' => $differentService->id]);
    }

    public function test_query_and_filter_works_when_query_does_not_match()
    {
        $service = factory(Service::class)->create(['name' => 'Ayup Digital']);
        $collection = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'self-help',
            'name' => 'Self Help',
            'meta' => [],
            'order' => 1,
        ]);
        $taxonomy = Taxonomy::category()->children()->create([
            'slug' => 'collection',
            'name' => 'Collection',
            'order' => 1,
        ]);
        $collectionTaxonomy = $collection->collectionTaxonomies()->create(['taxonomy_id' => $taxonomy->id]);
        $service->serviceTaxonomies()->create(['taxonomy_id' => $taxonomy->id]);
        $service->save();

        $differentService = factory(Service::class)->create(['name' => 'Ayup Digital']);
        $differentCollection = Collection::create([
            'type' => Collection::TYPE_PERSONA,
            'slug' => 'refugees',
            'name' => 'Refugees',
            'meta' => [],
            'order' => 1,
        ]);
        $differentTaxonomy = Taxonomy::category()->children()->create([
            'slug' => 'persona',
            'name' => 'Persona',
            'order' => 2,
        ]);
        $differentCollection->collectionTaxonomies()->create(['taxonomy_id' => $differentTaxonomy->id]);
        $differentService->serviceTaxonomies()->create(['taxonomy_id' => $differentTaxonomy->id]);
        $differentService->save();

        $response = $this->json('POST', '/core/v1/search', [
            'query' => 'asfkjbadsflksbdafklhasdbflkbs',
            'category' => $collectionTaxonomy->collection->slug,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(0, 'data');
    }

    public function test_only_active_services_returned()
    {
        $activeService = factory(Service::class)->create([
            'name' => 'Testing Service',
            'status' => Service::STATUS_ACTIVE,
        ]);
        $inactiveService = factory(Service::class)->create([
            'name' => 'Testing Service',
            'status' => Service::STATUS_INACTIVE,
        ]);

        $response = $this->json('POST', '/core/v1/search', [
            'query' => 'Testing Service',
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $activeService->id]);
        $response->assertJsonMissing(['id' => $inactiveService->id]);
    }

    public function test_national_service_not_returned_in_location_search_ordered_by_distance()
    {
        $nationalService = factory(Service::class)->create([
            'name' => 'Testing Service',
            'is_national' => true,
        ]);

        $localService = factory(Service::class)->create([
            'is_national' => false,
        ]);
        $localServiceLocation = factory(ServiceLocation::class)->create(['service_id' => $localService->id]);
        DB::table('locations')->where('id', $localServiceLocation->location->id)->update(['lat' => 45, 'lon' => 90]);
        $localService->save();

        $response = $this->json('POST', '/core/v1/search', [
            'order' => 'distance',
            'location' => [
                'lat' => 45,
                'lon' => 90,
            ],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $localService->id]);
        $response->assertJsonMissing(['id' => $nationalService->id]);
    }

    public function test_order_by_location_return_services_less_than_limited_miles_away()
    {
        $service1 = factory(Service::class)->create(['is_national' => false]);
        $serviceLocation = factory(ServiceLocation::class)->create(['service_id' => $service1->id]);
        DB::table('locations')->where('id', $serviceLocation->location->id)->update(['lat' => 0, 'lon' => 0]);
        $service1->save();

        $service2 = factory(Service::class)->create(['is_national' => false]);
        $serviceLocation2 = factory(ServiceLocation::class)->create(['service_id' => $service2->id]);
        DB::table('locations')->where('id', $serviceLocation2->location->id)->update(['lat' => 45, 'lon' => 90]);
        $service2->save();

        $service3 = factory(Service::class)->create(['is_national' => false]);
        $serviceLocation3 = factory(ServiceLocation::class)->create(['service_id' => $service3->id]);
        DB::table('locations')->where('id', $serviceLocation3->location->id)->update(['lat' => 90, 'lon' => 180]);
        $service3->save();

        $response = $this->json('POST', '/core/v1/search', [
            'order' => 'distance',
            'location' => [
                'lat' => 45,
                'lon' => 90,
            ],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $service2->id]);
        $response->assertJsonMissing(['id' => $service1->id]);
        $response->assertJsonMissing(['id' => $service3->id]);
    }

    public function test_order_by_relevance_with_location_return_services_less_than_limited_miles_away()
    {
        $service1 = factory(Service::class)->create();
        $serviceLocation = factory(ServiceLocation::class)->create(['service_id' => $service1->id]);
        DB::table('locations')->where('id', $serviceLocation->location->id)->update(['lat' => 0, 'lon' => 0]);
        $service1->save();

        $service2 = factory(Service::class)->create(['name' => 'Test Name']);
        $serviceLocation2 = factory(ServiceLocation::class)->create(['service_id' => $service2->id]);
        DB::table('locations')->where('id', $serviceLocation2->location->id)->update(['lat' => 45.01, 'lon' => 90.01]);
        $service2->save();

        $organisation3 = factory(Organisation::class)->create(['name' => 'Test Name']);
        $service3 = factory(Service::class)->create(['organisation_id' => $organisation3->id]);
        $serviceLocation3 = factory(ServiceLocation::class)->create(['service_id' => $service3->id]);
        DB::table('locations')->where('id', $serviceLocation3->location->id)->update(['lat' => 45, 'lon' => 90]);
        $service3->save();

        $service4 = factory(Service::class)->create();
        $serviceLocation4 = factory(ServiceLocation::class)->create(['service_id' => $service4->id]);
        DB::table('locations')->where('id', $serviceLocation4->location->id)->update(['lat' => 90, 'lon' => 180]);
        $service4->save();

        $response = $this->json('POST', '/core/v1/search', [
            'query' => 'Test Name',
            'order' => 'relevance',
            'location' => [
                'lat' => 45,
                'lon' => 90,
            ],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $service2->id]);
        $response->assertJsonFragment(['id' => $service3->id]);
        $response->assertJsonMissing(['id' => $service1->id]);
        $response->assertJsonMissing(['id' => $service4->id]);

        $data = $this->getResponseContent($response)['data'];
        $this->assertEquals(2, count($data));
        $this->assertEquals($service2->id, $data[0]['id']);
        $this->assertEquals($service3->id, $data[1]['id']);
    }

    public function test_searches_are_carried_out_in_specified_collections()
    {
        $collection1 = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'self-help',
            'name' => 'Self Help',
            'meta' => [],
            'order' => 1,
        ]);
        $taxonomy1 = Taxonomy::category()->children()->create([
            'slug' => 'test-taxonomy-1',
            'name' => 'Test Taxonomy 1',
            'order' => 1,
        ]);
        $collection1->collectionTaxonomies()->create(['taxonomy_id' => $taxonomy1->id]);

        $collection2 = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'addiction',
            'name' => 'Addiction',
            'meta' => [],
            'order' => 2,
        ]);
        $taxonomy2 = Taxonomy::category()->children()->create([
            'slug' => 'test-taxonomy-2',
            'name' => 'Test Taxonomy 2',
            'order' => 2,
        ]);
        $collection2->collectionTaxonomies()->create(['taxonomy_id' => $taxonomy2->id]);

        $collection3 = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'refugees',
            'name' => 'Refugees',
            'meta' => [],
            'order' => 2,
        ]);
        $taxonomy3 = Taxonomy::category()->children()->create([
            'slug' => 'test-taxonomy-3',
            'name' => 'Test Taxonomy 3',
            'order' => 2,
        ]);
        $collection3->collectionTaxonomies()->create(['taxonomy_id' => $taxonomy3->id]);

        // Service 1 is in Collection 1
        $service1 = factory(Service::class)->create(['name' => 'Foo Bar']);
        $service1->serviceTaxonomies()->create(['taxonomy_id' => $taxonomy1->id]);
        $service1->save();

        // Service 2 is in Collection 2
        $service2 = factory(Service::class)->create(['name' => 'Foo Bim']);
        $service2->serviceTaxonomies()->create(['taxonomy_id' => $taxonomy2->id]);
        $service2->save();

        // Service 3 is in Collection 2
        $service3 = factory(Service::class)->create(['name' => 'Foo Foo']);
        $service3->serviceTaxonomies()->create(['taxonomy_id' => $taxonomy2->id]);
        $service3->save();

        // Service 4 is in Collection 3
        $service4 = factory(Service::class)->create(['name' => 'Foo Baz']);
        $service4->serviceTaxonomies()->create(['taxonomy_id' => $taxonomy3->id]);
        $service4->save();

        $response = $this->json('POST', '/core/v1/search', [
            'query' => 'Foo',
            'category' => implode(',', [$collection2->slug, $collection3->slug]),
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $service2->id]);
        $response->assertJsonFragment(['id' => $service3->id]);
        $response->assertJsonFragment(['id' => $service4->id]);
        $response->assertJsonMissing(['id' => $service1->id]);
    }

    public function test_location_searches_are_carried_out_in_specified_collections()
    {
        $collection1 = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'self-help',
            'name' => 'Self Help',
            'meta' => [],
            'order' => 1,
        ]);
        $taxonomy1 = Taxonomy::category()->children()->create([
            'slug' => 'test-taxonomy-1',
            'name' => 'Test Taxonomy 1',
            'order' => 1,
        ]);
        $collection1->collectionTaxonomies()->create(['taxonomy_id' => $taxonomy1->id]);

        $collection2 = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'addiction',
            'name' => 'Addiction',
            'meta' => [],
            'order' => 2,
        ]);
        $taxonomy2 = Taxonomy::category()->children()->create([
            'slug' => 'test-taxonomy-2',
            'name' => 'Test Taxonomy 2',
            'order' => 2,
        ]);
        $collection2->collectionTaxonomies()->create(['taxonomy_id' => $taxonomy2->id]);

        // Service 1 is in Collection 1
        $service1 = factory(Service::class)->create(['name' => 'Bar', 'is_national' => false]);
        $service1->serviceTaxonomies()->create(['taxonomy_id' => $taxonomy1->id]);
        $serviceLocation1 = factory(ServiceLocation::class)->create(['service_id' => $service1->id]);
        DB::table('locations')->where('id', $serviceLocation1->location->id)->update(['lat' => 041.9374814, 'lon' => -8.8643883]);
        $service1->save();

        // Service 2 is in Collection 2
        $service2 = factory(Service::class)->create(['name' => 'Bim', 'is_national' => false]);
        $service2->serviceTaxonomies()->create(['taxonomy_id' => $taxonomy2->id]);
        $serviceLocation2 = factory(ServiceLocation::class)->create(['service_id' => $service2->id]);
        DB::table('locations')->where('id', $serviceLocation2->location->id)->update(['lat' => 041.9374814, 'lon' => -8.8643883]);
        $service2->save();

        // Service 3 is in Collection 2
        $service3 = factory(Service::class)->create(['name' => 'Foo', 'is_national' => false]);
        $service3->serviceTaxonomies()->create(['taxonomy_id' => $taxonomy2->id]);
        $serviceLocation3 = factory(ServiceLocation::class)->create(['service_id' => $service3->id]);
        DB::table('locations')->where('id', $serviceLocation3->location->id)->update(['lat' => 90, 'lon' => 90]);
        $service3->save();

        $response = $this->json('POST', '/core/v1/search', [
            'order' => 'distance',
            'category' => implode(',', [$collection2->slug]),
            'location' => [
                'lat' => 041.9374814,
                'lon' => -8.8643883,
            ],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $service2->id]);
        $response->assertJsonMissing(['id' => $service1->id]);
        $response->assertJsonMissing(['id' => $service3->id]);
    }

    public function test_location_searches_and_queries_are_carried_out_in_specified_collections()
    {
        $collection1 = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'self-help',
            'name' => 'Self Help',
            'meta' => [],
            'order' => 1,
        ]);
        $taxonomy1 = Taxonomy::category()->children()->create([
            'slug' => 'test-taxonomy-1',
            'name' => 'Test Taxonomy 1',
            'order' => 1,
        ]);
        $collection1->collectionTaxonomies()->create(['taxonomy_id' => $taxonomy1->id]);

        $collection2 = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'addiction',
            'name' => 'Addiction',
            'meta' => [],
            'order' => 2,
        ]);
        $taxonomy2 = Taxonomy::category()->children()->create([
            'slug' => 'test-taxonomy-2',
            'name' => 'Test Taxonomy 2',
            'order' => 2,
        ]);
        $collection2->collectionTaxonomies()->create(['taxonomy_id' => $taxonomy2->id]);

        $collection3 = Collection::create([
            'type' => Collection::TYPE_PERSONA,
            'slug' => 'refugees',
            'name' => 'Refugees',
            'meta' => [],
            'order' => 1,
        ]);
        $taxonomy3 = Taxonomy::category()->children()->create([
            'slug' => 'test-taxonomy-3',
            'name' => 'Test Taxonomy 3',
            'order' => 2,
        ]);
        $collection3->collectionTaxonomies()->create(['taxonomy_id' => $taxonomy3->id]);

        // Service 1 is in Collection 1
        $service1 = factory(Service::class)->create(['name' => 'Baz Bar', 'is_national' => false]);
        $service1->serviceTaxonomies()->create(['taxonomy_id' => $taxonomy1->id]);
        $serviceLocation1 = factory(ServiceLocation::class)->create(['service_id' => $service1->id]);
        DB::table('locations')->where('id', $serviceLocation1->location->id)->update(['lat' => 041.9374814, 'lon' => -8.8643883]);
        $service1->save();

        // Service 2 is in Collection 2
        $service2 = factory(Service::class)->create(['name' => 'Baz Bim', 'is_national' => false]);
        $service2->serviceTaxonomies()->create(['taxonomy_id' => $taxonomy2->id]);
        $service2->serviceTaxonomies()->create(['taxonomy_id' => $taxonomy3->id]);
        $serviceLocation2 = factory(ServiceLocation::class)->create(['service_id' => $service2->id]);
        DB::table('locations')->where('id', $serviceLocation2->location->id)->update(['lat' => 041.9374814, 'lon' => -8.8643883]);
        $service2->save();

        // Service 3 is in Collection 2
        $service3 = factory(Service::class)->create(['name' => 'Baz Foo', 'is_national' => false]);
        $service3->serviceTaxonomies()->create(['taxonomy_id' => $taxonomy2->id]);
        $serviceLocation3 = factory(ServiceLocation::class)->create(['service_id' => $service3->id]);
        DB::table('locations')->where('id', $serviceLocation3->location->id)->update(['lat' => 90, 'lon' => 90]);
        $service3->save();

        // Service 4 is in Collection 3
        $service4 = factory(Service::class)->create(['name' => 'Baz Baz', 'is_national' => false]);
        $service4->serviceTaxonomies()->create(['taxonomy_id' => $taxonomy3->id]);
        $serviceLocation4 = factory(ServiceLocation::class)->create(['service_id' => $service4->id]);
        DB::table('locations')->where('id', $serviceLocation4->location->id)->update(['lat' => 041.9374814, 'lon' => -8.8643883]);
        $service4->save();

        $response = $this->json('POST', '/core/v1/search', [
            'query' => 'Baz',
            'persona' => implode(',', [$collection3->slug]),
            'location' => [
                'lat' => 041.9374814,
                'lon' => -8.8643883,
            ],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment(['id' => $service2->id]);
        $response->assertJsonFragment(['id' => $service4->id]);
        $response->assertJsonMissing(['id' => $service1->id]);
        $response->assertJsonMissing(['id' => $service3->id]);
    }

    public function test_services_with_a_higher_score_are_more_relevant()
    {
        $organisation = factory(\App\Models\Organisation::class)->create();
        $serviceParams = [
            'organisation_id' => $organisation->id,
            'name' => 'Testing Service',
            'intro' => 'Service Intro',
            'description' => 'Service description',
        ];
        $service5 = factory(Service::class)->create(array_merge($serviceParams, ['score' => 5]));
        $service3 = factory(Service::class)->create(array_merge($serviceParams, ['score' => 3]));
        $service0 = factory(Service::class)->create(array_merge($serviceParams, ['score' => 0]));

        $response = $this->json('POST', '/core/v1/search', [
            'query' => 'Testing Service',
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $data = $this->getResponseContent($response)['data'];

        $this->assertEquals($service5->id, $data[0]['id']);
        $this->assertEquals($service3->id, $data[1]['id']);
        $this->assertEquals($service0->id, $data[2]['id']);

    }

    public function test_service_scores_are_secondary_to_distance()
    {
        $organisation = factory(\App\Models\Organisation::class)->create();
        $serviceParams = [
            'organisation_id' => $organisation->id,
            'name' => 'Testing Service',
            'intro' => 'Service Intro',
            'description' => 'Service description',
            'is_national' => false,
        ];

        $service5 = factory(Service::class)->create(array_merge($serviceParams, ['score' => 5]));
        $serviceLocation = factory(ServiceLocation::class)->create(['service_id' => $service5->id]);
        DB::table('locations')->where('id', $serviceLocation->location->id)->update(['lat' => 45.01, 'lon' => 90.01]);
        $service5->save();

        $service0 = factory(Service::class)->create(array_merge($serviceParams, ['score' => 0]));
        $serviceLocation3 = factory(ServiceLocation::class)->create(['service_id' => $service0->id]);
        DB::table('locations')->where('id', $serviceLocation3->location->id)->update(['lat' => 45, 'lon' => 90]);
        $service0->save();

        $response = $this->json('POST', '/core/v1/search', [
            'query' => 'Testing Service',
            'order' => 'distance',
            'location' => [
                'lat' => 45,
                'lon' => 90,
            ],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $data = $this->getResponseContent($response)['data'];

        $this->assertEquals($service0->id, $data[0]['id']);
        $this->assertEquals($service5->id, $data[1]['id']);
    }

    public function test_score_and_national_results_ordered_correctly()
    {
        // TODO
        $this->markTestSkipped('Logic not yet working as expected.');

        $nationalService5 = factory(Service::class)->create([
            'name' => 'abcdefghijklmnopqrstuvwxyz',
            'is_national' => true,
            'score' => 5,
        ]);
        $localService5 = factory(Service::class)->create([
            'name' => 'abcdefghijklmnopqrstuvwxyz',
            'is_national' => false,
            'score' => 5,
        ]);
        $nationalService4 = factory(Service::class)->create([
            'name' => 'abcdefghijklmnopqrstuvwxyz',
            'is_national' => true,
            'score' => 4,
        ]);
        $localService4 = factory(Service::class)->create([
            'name' => 'abcdefghijklmnopqrstuvwxyz',
            'is_national' => false,
            'score' => 4,
        ]);

        $location = factory(Location::class)->create([
            'lat' => 45,
            'lon' => 90,
        ]);

        factory(ServiceLocation::class)->create([
            'service_id' => $localService5->id,
            'location_id' => $location->id,
        ]);
        factory(ServiceLocation::class)->create([
            'service_id' => $localService4->id,
            'location_id' => $location->id,
        ]);

        $response = $this->json('POST', '/core/v1/search', [
            'query' => 'abcdefghijklmnopqrstuvwxyz',
            'location' => [
                'lat' => 45,
                'lon' => 90,
            ],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(4, 'data');
        $this->assertEquals(
            $localService5->id,
            $this->getResponseContent($response, 'data.0.id')
        );
        $this->assertEquals(
            $nationalService5->id,
            $this->getResponseContent($response, 'data.1.id')
        );
        $this->assertEquals(
            $localService4->id,
            $this->getResponseContent($response, 'data.2.id')
        );
        $this->assertEquals(
            $nationalService4->id,
            $this->getResponseContent($response, 'data.3.id')
        );
    }
}
