<?php

use App\Models\Service;
use App\Models\SocialMedia;
use Faker\Generator as Faker;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;

$factory->define(Service::class, function (Faker $faker) {
    $name = $faker->unique()->company;

    return [
        'organisation_id' => function () {
            return factory(\App\Models\Organisation::class)->create()->id;
        },
        'slug' => Str::slug($name) . '-' . mt_rand(1, 1000),
        'name' => $name,
        'type' => Service::TYPE_SERVICE,
        'status' => Service::STATUS_ACTIVE,
        'is_national' => true,
        'intro' => $faker->sentence,
        'description' => $faker->sentence,
        'is_free' => true,
        'url' => $faker->url,
        'contact_name' => $faker->name,
        'contact_phone' => random_uk_phone(),
        'contact_email' => $faker->safeEmail,
        'show_referral_disclaimer' => false,
        'referral_method' => Service::REFERRAL_METHOD_NONE,
        'last_modified_at' => Date::now(),
    ];
});

$factory->afterCreating(Service::class, function (Service $service, Faker $faker) {
    \App\Models\ServiceCriterion::create([
        'service_id' => $service->id,
        'age_group' => null,
        'disability' => null,
        'employment' => null,
        'gender' => null,
        'housing' => null,
        'income' => null,
        'language' => null,
        'other' => null,
    ]);
});

$factory->state(Service::class, 'logo', function (Faker $faker) {
    return [
        'logo_file_id' => function () {
            return factory(\App\Models\File::class)->create()->id;
        },
    ];
});

$factory->state(Service::class, 'social', []);
$factory->afterCreatingState(Service::class, 'social', function (Service $service, Faker $faker) {
    $service->socialMedias()->saveMany([
        factory(SocialMedia::class)->create(),
        factory(SocialMedia::class)->states('twitter')->create(),
        factory(SocialMedia::class)->states('instagram')->create(),
    ]);
});
