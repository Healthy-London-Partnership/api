<?php

namespace Tests\Unit\Models;

use App\Models\SocialMedia;
use Tests\TestCase;

class SocialMediaTest extends TestCase
{

    /**
     * @test
     */
    public function it_should_have_a_type_property()
    {
        $social_media = factory(SocialMedia::class)->create();
        $this->assertEquals(SocialMedia::TYPE_FACEBOOK, $social_media->type);
    }

    /**
     * @test
     */
    public function it_should_have_a_url_property()
    {
        $social_media = factory(SocialMedia::class)->create();
        $this->assertNotEmpty($social_media->url);
    }

    /**
     * @test
     */
    public function it_should_have_a_sociable_method()
    {
        $social_media = factory(SocialMedia::class)->create();
        $this->assertTrue(method_exists($social_media, 'sociable'));
    }
}
