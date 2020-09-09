<?php

namespace Tests\Unit\Rules;

use App\Rules\VideoEmbed;
use Tests\TestCase;

class VideoEmbedTest extends TestCase
{
    /**
     * @test
     */
    public function it_passes_video_hosting_urls()
    {
        $bad_urls = [
            'https://youtube.com',
            'https://player.vimeo.com',
            'https://badmovie.com',
        ];

        $good_urls = [
            'https://www.youtube.com',
            'https://vimeo.com',
        ];

        $video_embed_rule = new VideoEmbed();

        foreach ($bad_urls as $bad_url) {
            $this->assertFalse($video_embed_rule->passes('video_embed', $bad_url));
        }

        foreach ($good_urls as $good_url) {
            $this->assertTrue($video_embed_rule->passes('video_embed', $good_url));
        }
    }
}
