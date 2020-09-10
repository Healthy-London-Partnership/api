<?php

use App\Models\Organisation;
use Faker\Generator as Faker;
use Illuminate\Support\Str;

$factory->define(Organisation::class, function (Faker $faker) {
    $name = $faker->unique()->company;

    return [
        'slug' => Str::slug($name) . '-' . mt_rand(1, 1000),
        'name' => $name,
        'description' => 'This organisation provides x service.',
    ];
});

$factory->state(Organisation::class, 'web', function (Faker $faker) {
    return [
        'url' => $faker->url,
    ];
});

$factory->state(Organisation::class, 'email', function (Faker $faker) {
    return [
        'email' => $faker->safeEmail,
    ];
});

$factory->state(Organisation::class, 'phone', function (Faker $faker) {
    return [
        'phone' => random_uk_phone(),
    ];
});

$factory->state(Organisation::class, 'location', function (Faker $faker) {
    return [
        'location_id' => function () {
            return factory(\App\Models\Location::class)->create()->id;
        },
    ];
});

$factory->state(Organisation::class, 'logo', function (Faker $faker) {
    return [
        'logo_file_id' => function () {
            return factory(\App\Models\File::class)->create()->id;
        },
    ];
});

$factory->state(Organisation::class, 'social', function (Faker $faker) use ($factory) {
    $factory->afterCreating(Organisation::class, function (Organisation $organisation, Faker $faker) {
        \App\Models\SocialMedia::create([
            'relatable_id' => $organisation->id,
            'relatable_type' => 'App\Models\Organisation',
        ]);
        \App\Models\SocialMedia::states('twitter')->create([
            'relatable_id' => $organisation->id,
            'relatable_type' => 'App\Models\Organisation',
        ]);
        \App\Models\SocialMedia::states('instagram')->create([
            'relatable_id' => $organisation->id,
            'relatable_type' => 'App\Models\Organisation',
        ]);
    });
});
