<?php

namespace Tests\Feature;

use App\Events\EndpointHit;
use App\Models\Audit;
use App\Models\Organisation;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Laravel\Passport\Passport;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    /*
     * List all the settings.
     */

    public function test_guest_can_list_them()
    {
        $response = $this->getJson('/core/v1/settings');

        $response->assertStatus(Response::HTTP_OK);
    }

    public function test_structure_correct_when_listed()
    {
        $response = $this->getJson('/core/v1/settings');

        $response->assertJsonStructure([
            'data' => [
                'cms' => [
                    'frontend' => [
                        'global' => [
                            'footer_title',
                            'footer_content',
                            'contact_phone',
                            'contact_email',
                            'facebook_handle',
                            'twitter_handle',
                        ],
                        'home' => [
                            'search_title',
                            'categories_title',
                            'personas_title',
                            'personas_content',
                        ],
                        'terms_and_conditions' => [
                            'title',
                            'content',
                        ],
                        'privacy_policy' => [
                            'title',
                            'content',
                        ],
                        'about' => [
                            'title',
                            'content',
                        ],
                        'providers' => [
                            'title',
                            'content',
                        ],
                        'supporters' => [
                            'title',
                            'content',
                        ],
                        'funders' => [
                            'title',
                            'content',
                        ],
                        'contact' => [
                            'title',
                            'content',
                        ],
                        'favourites' => [
                            'title',
                            'content',
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function test_values_correct_when_listed()
    {
        $response = $this->getJson('/core/v1/settings');

        $response->assertJson([
            'data' => [
                'cms' => [
                    'frontend' => [
                        'global' => [
                            'footer_title' => 'Footer title',
                            'footer_content' => 'Footer content',
                            'contact_phone' => 'Contact phone',
                            'contact_email' => 'Contact email',
                            'facebook_handle' => 'Facebook handle',
                            'twitter_handle' => 'Twitter handle',
                        ],
                        'home' => [
                            'search_title' => 'Search title',
                            'categories_title' => 'Categories title',
                            'personas_title' => 'Personas title',
                            'personas_content' => 'Personas content',
                        ],
                        'terms_and_conditions' => [
                            'title' => 'Title',
                            'content' => 'Content',
                        ],
                        'privacy_policy' => [
                            'title' => 'Title',
                            'content' => 'Content',
                        ],
                        'about' => [
                            'title' => 'Title',
                            'content' => 'Content',
                        ],
                        'providers' => [
                            'title' => 'Title',
                            'content' => 'Content',
                        ],
                        'supporters' => [
                            'title' => 'Title',
                            'content' => 'Content',
                        ],
                        'funders' => [
                            'title' => 'Title',
                            'content' => 'Content',
                        ],
                        'contact' => [
                            'title' => 'Title',
                            'content' => 'Content',
                        ],
                        'favourites' => [
                            'title' => 'Title',
                            'content' => 'Content',
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function test_audit_created_when_listed()
    {
        $this->fakeEvents();

        $this->getJson('/core/v1/settings');

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) {
            return ($event->getAction() === Audit::ACTION_READ);
        });
    }

    /*
     * Update the settings.
     */

    public function test_guest_cannot_update_them()
    {
        $response = $this->putJson('/core/v1/settings');

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_service_worker_cannot_update_them()
    {
        Passport::actingAs(
            $user = $this->makeServiceWorker(factory(User::class)->create(), factory(Service::class)->create())
        );

        $response = $this->putJson('/core/v1/settings');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_service_admin_cannot_update_them()
    {
        Passport::actingAs(
            $this->makeServiceAdmin(
                factory(User::class)->create(),
                factory(Service::class)->create()
            )
        );

        $response = $this->putJson('/core/v1/settings');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_organisation_admin_update_them()
    {
        Passport::actingAs(
            $user = $this->makeOrganisationAdmin(factory(User::class)->create(), factory(Organisation::class)->create())
        );

        $response = $this->putJson('/core/v1/settings');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_global_admin_can_update_them()
    {
        Passport::actingAs(
            $user = $this->makeGlobalAdmin(factory(User::class)->create())
        );

        $response = $this->putJson('/core/v1/settings', [
            'cms' => [
                'frontend' => [
                    'global' => [
                        'footer_title' => 'data/cms/frontend/global/footer_title',
                        'footer_content' => 'data/cms/frontend/global/footer_content',
                        'contact_phone' => 'data/cms/frontend/global/contact_phone',
                        'contact_email' => 'example@example.com',
                        'facebook_handle' => 'data/cms/frontend/global/facebook_handle',
                        'twitter_handle' => 'data/cms/frontend/global/twitter_handle',
                    ],
                    'home' => [
                        'search_title' => 'data/cms/frontend/home/search_title',
                        'categories_title' => 'data/cms/frontend/home/categories_title',
                        'personas_title' => 'data/cms/frontend/home/personas_title',
                        'personas_content' => 'data/cms/frontend/home/personas_content',
                    ],
                    'terms_and_conditions' => [
                        'title' => 'data/cms/frontend/terms_and_conditions/title',
                        'content' => 'data/cms/frontend/terms_and_conditions/content',
                    ],
                    'privacy_policy' => [
                        'title' => 'data/cms/frontend/privacy_policy/title',
                        'content' => 'data/cms/frontend/privacy_policy/content',
                    ],
                    'about' => [
                        'title' => 'data/cms/frontend/about/title',
                        'content' => 'data/cms/frontend/about/content',
                    ],
                    'providers' => [
                        'title' => 'data/cms/frontend/get_involved/title',
                        'content' => 'data/cms/frontend/get_involved/content',
                    ],
                    'supporters' => [
                        'title' => 'data/cms/frontend/get_involved/title',
                        'content' => 'data/cms/frontend/get_involved/content',
                    ],
                    'funders' => [
                        'title' => 'data/cms/frontend/get_involved/title',
                        'content' => 'data/cms/frontend/get_involved/content',
                    ],
                    'contact' => [
                        'title' => 'data/cms/frontend/contact/title',
                        'content' => 'data/cms/frontend/contact/content',
                    ],
                    'favourites' => [
                        'title' => 'data/cms/frontend/favourites/title',
                        'content' => 'data/cms/frontend/favourites/content',
                    ],
                ],
            ],
        ]);

        $response->assertStatus(Response::HTTP_OK);
    }

    public function test_structure_correct_when_updated()
    {
        Passport::actingAs(
            $user = $this->makeGlobalAdmin(factory(User::class)->create())
        );

        $response = $this->putJson('/core/v1/settings', [
            'cms' => [
                'frontend' => [
                    'global' => [
                        'footer_title' => 'data/cms/frontend/global/footer_title',
                        'footer_content' => 'data/cms/frontend/global/footer_content',
                        'contact_phone' => 'data/cms/frontend/global/contact_phone',
                        'contact_email' => 'example@example.com',
                        'facebook_handle' => 'data/cms/frontend/global/facebook_handle',
                        'twitter_handle' => 'data/cms/frontend/global/twitter_handle',
                    ],
                    'home' => [
                        'search_title' => 'data/cms/frontend/home/search_title',
                        'categories_title' => 'data/cms/frontend/home/categories_title',
                        'personas_title' => 'data/cms/frontend/home/personas_title',
                        'personas_content' => 'data/cms/frontend/home/personas_content',
                    ],
                    'terms_and_conditions' => [
                        'title' => 'data/cms/frontend/terms_and_conditions/title',
                        'content' => 'data/cms/frontend/terms_and_conditions/content',
                    ],
                    'privacy_policy' => [
                        'title' => 'data/cms/frontend/privacy_policy/title',
                        'content' => 'data/cms/frontend/privacy_policy/content',
                    ],
                    'about' => [
                        'title' => 'data/cms/frontend/about/title',
                        'content' => 'data/cms/frontend/about/content',
                    ],
                    'providers' => [
                        'title' => 'data/cms/frontend/providers/title',
                        'content' => 'data/cms/frontend/providers/content',
                    ],
                    'supporters' => [
                        'title' => 'data/cms/frontend/supporters/title',
                        'content' => 'data/cms/frontend/supporters/content',
                    ],
                    'funders' => [
                        'title' => 'data/cms/frontend/funders/title',
                        'content' => 'data/cms/frontend/funders/content',
                    ],
                    'contact' => [
                        'title' => 'data/cms/frontend/contact/title',
                        'content' => 'data/cms/frontend/contact/content',
                    ],
                    'favourites' => [
                        'title' => 'data/cms/frontend/favourites/title',
                        'content' => 'data/cms/frontend/favourites/content',
                    ],
                ],
            ],
        ]);

        $response->assertJsonStructure([
            'data' => [
                'cms' => [
                    'frontend' => [
                        'global' => [
                            'footer_title',
                            'footer_content',
                            'contact_phone',
                            'contact_email',
                            'facebook_handle',
                            'twitter_handle',
                        ],
                        'home' => [
                            'search_title',
                            'categories_title',
                            'personas_title',
                            'personas_content',
                        ],
                        'terms_and_conditions' => [
                            'title',
                            'content',
                        ],
                        'privacy_policy' => [
                            'title',
                            'content',
                        ],
                        'about' => [
                            'title',
                            'content',
                        ],
                        'providers' => [
                            'title',
                            'content',
                        ],
                        'supporters' => [
                            'title',
                            'content',
                        ],
                        'funders' => [
                            'title',
                            'content',
                        ],
                        'contact' => [
                            'title',
                            'content',
                        ],
                        'favourites' => [
                            'title',
                            'content',
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function test_values_correct_when_updated()
    {
        Passport::actingAs(
            $user = $this->makeGlobalAdmin(factory(User::class)->create())
        );

        $response = $this->putJson('/core/v1/settings', [
            'cms' => [
                'frontend' => [
                    'global' => [
                        'footer_title' => 'data/cms/frontend/global/footer_title',
                        'footer_content' => 'data/cms/frontend/global/footer_content',
                        'contact_phone' => 'data/cms/frontend/global/contact_phone',
                        'contact_email' => 'example@example.com',
                        'facebook_handle' => 'data/cms/frontend/global/facebook_handle',
                        'twitter_handle' => 'data/cms/frontend/global/twitter_handle',
                    ],
                    'home' => [
                        'search_title' => 'data/cms/frontend/home/search_title',
                        'categories_title' => 'data/cms/frontend/home/categories_title',
                        'personas_title' => 'data/cms/frontend/home/personas_title',
                        'personas_content' => 'data/cms/frontend/home/personas_content',
                    ],
                    'terms_and_conditions' => [
                        'title' => 'data/cms/frontend/terms_and_conditions/title',
                        'content' => 'data/cms/frontend/terms_and_conditions/content',
                    ],
                    'privacy_policy' => [
                        'title' => 'data/cms/frontend/privacy_policy/title',
                        'content' => 'data/cms/frontend/privacy_policy/content',
                    ],
                    'about' => [
                        'title' => 'data/cms/frontend/about/title',
                        'content' => 'data/cms/frontend/about/content',
                    ],
                    'providers' => [
                        'title' => 'data/cms/frontend/get_involved/title',
                        'content' => 'data/cms/frontend/get_involved/content',
                    ],
                    'supporters' => [
                        'title' => 'data/cms/frontend/get_involved/title',
                        'content' => 'data/cms/frontend/get_involved/content',
                    ],
                    'funders' => [
                        'title' => 'data/cms/frontend/get_involved/title',
                        'content' => 'data/cms/frontend/get_involved/content',
                    ],
                    'contact' => [
                        'title' => 'data/cms/frontend/contact/title',
                        'content' => 'data/cms/frontend/contact/content',
                    ],
                    'favourites' => [
                        'title' => 'data/cms/frontend/favourites/title',
                        'content' => 'data/cms/frontend/favourites/content',
                    ],
                ],
            ],
        ]);

        $response->assertJson([
            'data' => [
                'cms' => [
                    'frontend' => [
                        'global' => [
                            'footer_title' => 'data/cms/frontend/global/footer_title',
                            'footer_content' => 'data/cms/frontend/global/footer_content',
                            'contact_phone' => 'data/cms/frontend/global/contact_phone',
                            'contact_email' => 'example@example.com',
                            'facebook_handle' => 'data/cms/frontend/global/facebook_handle',
                            'twitter_handle' => 'data/cms/frontend/global/twitter_handle',
                        ],
                        'home' => [
                            'search_title' => 'data/cms/frontend/home/search_title',
                            'categories_title' => 'data/cms/frontend/home/categories_title',
                            'personas_title' => 'data/cms/frontend/home/personas_title',
                            'personas_content' => 'data/cms/frontend/home/personas_content',
                        ],
                        'terms_and_conditions' => [
                            'title' => 'data/cms/frontend/terms_and_conditions/title',
                            'content' => 'data/cms/frontend/terms_and_conditions/content',
                        ],
                        'privacy_policy' => [
                            'title' => 'data/cms/frontend/privacy_policy/title',
                            'content' => 'data/cms/frontend/privacy_policy/content',
                        ],
                        'about' => [
                            'title' => 'data/cms/frontend/about/title',
                            'content' => 'data/cms/frontend/about/content',
                        ],
                        'providers' => [
                            'title' => 'data/cms/frontend/get_involved/title',
                            'content' => 'data/cms/frontend/get_involved/content',
                        ],
                        'supporters' => [
                            'title' => 'data/cms/frontend/get_involved/title',
                            'content' => 'data/cms/frontend/get_involved/content',
                        ],
                        'funders' => [
                            'title' => 'data/cms/frontend/get_involved/title',
                            'content' => 'data/cms/frontend/get_involved/content',
                        ],
                        'contact' => [
                            'title' => 'data/cms/frontend/contact/title',
                            'content' => 'data/cms/frontend/contact/content',
                        ],
                        'favourites' => [
                            'title' => 'data/cms/frontend/favourites/title',
                            'content' => 'data/cms/frontend/favourites/content',
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function test_audit_created_when_updated()
    {
        $this->fakeEvents();

        Passport::actingAs(
            $user = $this->makeGlobalAdmin(factory(User::class)->create())
        );

        $response = $this->putJson('/core/v1/settings', [
            'cms' => [
                'frontend' => [
                    'global' => [
                        'footer_title' => 'data/cms/frontend/global/footer_title',
                        'footer_content' => 'data/cms/frontend/global/footer_content',
                        'contact_phone' => 'data/cms/frontend/global/contact_phone',
                        'contact_email' => 'example@example.com',
                        'facebook_handle' => 'data/cms/frontend/global/facebook_handle',
                        'twitter_handle' => 'data/cms/frontend/global/twitter_handle',
                    ],
                    'home' => [
                        'search_title' => 'data/cms/frontend/home/search_title',
                        'categories_title' => 'data/cms/frontend/home/categories_title',
                        'personas_title' => 'data/cms/frontend/home/personas_title',
                        'personas_content' => 'data/cms/frontend/home/personas_content',
                    ],
                    'terms_and_conditions' => [
                        'title' => 'data/cms/frontend/terms_and_conditions/title',
                        'content' => 'data/cms/frontend/terms_and_conditions/content',
                    ],
                    'privacy_policy' => [
                        'title' => 'data/cms/frontend/privacy_policy/title',
                        'content' => 'data/cms/frontend/privacy_policy/content',
                    ],
                    'about' => [
                        'title' => 'data/cms/frontend/about/title',
                        'content' => 'data/cms/frontend/about/content',
                    ],
                    'providers' => [
                        'title' => 'data/cms/frontend/get_involved/title',
                        'content' => 'data/cms/frontend/get_involved/content',
                    ],
                    'supporters' => [
                        'title' => 'data/cms/frontend/get_involved/title',
                        'content' => 'data/cms/frontend/get_involved/content',
                    ],
                    'funders' => [
                        'title' => 'data/cms/frontend/get_involved/title',
                        'content' => 'data/cms/frontend/get_involved/content',
                    ],
                    'contact' => [
                        'title' => 'data/cms/frontend/contact/title',
                        'content' => 'data/cms/frontend/contact/content',
                    ],
                    'favourites' => [
                        'title' => 'data/cms/frontend/favourites/title',
                        'content' => 'data/cms/frontend/favourites/content',
                    ],
                ],
            ],
        ]);

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) {
            return ($event->getAction() === Audit::ACTION_UPDATE);
        });
    }
}
