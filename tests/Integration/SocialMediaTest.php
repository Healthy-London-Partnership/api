<?php

namespace Tests\Integration;

use App\Models\Organisation;
use App\Models\Service;
use App\Models\SocialMedia;
use Tests\TestCase;

class SocialMediaTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_persist_and_retrieve_records()
    {
        factory(SocialMedia::class, 10)->create();

        $social_medias = SocialMedia::all();

        $this->assertCount(10, $social_medias);
    }

    /**
     * @test
     */
    public function it_can_have_an_associated_service()
    {
        $social_media = factory(SocialMedia::class)->states('service')->create();

        $this->assertInstanceOf(Service::class, $social_media->sociable);
    }

    /**
     * @test
     */
    public function it_can_have_an_associated_organisation()
    {
        $social_media = factory(SocialMedia::class)->states('organisation')->create();

        $this->assertInstanceOf(Organisation::class, $social_media->sociable);
    }
}
