<?php

namespace Tests\Integration\Models;

use App\Models\Organisation;
use Tests\TestCase;

class OrganisationTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_persist_and_retrieve_records()
    {
        factory(Organisation::class, 10)->create();

        $organisations = Organisation::all();

        $this->assertCount(10, $organisations);
    }

    /**
     * @test
     */
    public function it_can_have_an_associated_logo()
    {
        $organisation = factory(\App\Models\Organisation::class)->states('logo')->create();

        $this->assertInstanceOf(\App\Models\File::class, $organisation->logo);
    }

    /**
     * @test
     */
    public function it_can_have_associated_social_media()
    {
        $organisation = factory(\App\Models\Organisation::class)->states('social')->create();

        $this->assertInstanceOf(\App\Models\SocialMedia::class, $organisation->social->first());
    }
}
