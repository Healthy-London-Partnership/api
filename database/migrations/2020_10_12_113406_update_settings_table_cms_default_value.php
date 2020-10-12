<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class UpdateSettingsTableCmsDefaultValue extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        DB::table('settings')
            ->where('key', 'cms')
            ->update([
                'value' => json_encode([
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
                        'about_connect' => [
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
                ]),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        DB::table('settings')
            ->where('key', 'cms')
            ->update([
                'value' => json_encode([
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
                            'video_url' => 'Video URL',
                        ],
                        'contact' => [
                            'title' => 'Title',
                            'content' => 'Content',
                        ],
                        'get_involved' => [
                            'title' => 'Title',
                            'content' => 'Content',
                        ],
                        'favourites' => [
                            'title' => 'Title',
                            'content' => 'Content',
                        ],
                    ],
                ]),
            ]);
    }
}
