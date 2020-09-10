<?php

use App\Models\SocialMedia;
use Faker\Generator as Faker;

$factory->define(SocialMedia::class, function (Faker $faker) {
    return [
        'type' => SocialMedia::TYPE_FACEBOOK,
        'url' => 'https://facebook.com/' . $faker->domainWord,
    ];
});

$factory->state(SocialMedia::class, 'twitter', function (Faker $faker) {
    return [
        'type' => SocialMedia::TYPE_TWITTER,
        'url' => 'https://twitter.com/' . $faker->domainWord,
    ];
});

$factory->state(SocialMedia::class, 'instagram', function (Faker $faker) {
    return [
        'type' => SocialMedia::TYPE_INSTAGRAM,
        'url' => 'https://www.instagram.com/' . $faker->domainWord,
    ];
});

$factory->state(SocialMedia::class, 'youtube', function (Faker $faker) {
    return [
        'type' => SocialMedia::TYPE_YOUTUBE,
        'url' => 'https://www.youtube.com/' . $faker->domainWord,
    ];
});

$factory->state(SocialMedia::class, 'service', function (Faker $faker) {
    return [
        'relatable_id' => function () {
            return factory(\App\Models\Service::class)->create()->id;
        },
        'relatable_type' => 'App\Models\Service',
    ];
});

$factory->state(SocialMedia::class, 'organisation', function (Faker $faker) {
    return [
        'relatable_id' => function () {
            return factory(\App\Models\Organisation::class)->create()->id;
        },
        'relatable_type' => 'App\Models\Organisation',
    ];
});
