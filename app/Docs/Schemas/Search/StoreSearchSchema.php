<?php

namespace App\Docs\Schemas\Search;

use App\Contracts\Search;
use App\Models\Service;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class StoreSearchSchema extends Schema
{
    /**
     * @param string|null $objectId
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     * @return static
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->type(static::TYPE_OBJECT)
            ->properties(
                Schema::integer('page'),
                Schema::integer('per_page')
                    ->default(config('hlp.pagination_results')),
                Schema::string('query'),
                Schema::string('type')->enum(
                    Service::TYPE_SERVICE,
                    Service::TYPE_ACTIVITY,
                    Service::TYPE_CLUB,
                    Service::TYPE_GROUP,
                    Service::TYPE_HELPLINE,
                    Service::TYPE_INFORMATION,
                    Service::TYPE_APP,
                    Service::TYPE_ADVICE
                ),
                Schema::string('category'),
                Schema::string('persona'),
                Schema::string('wait_time')
                    ->enum(
                        Service::WAIT_TIME_ONE_WEEK,
                        Service::WAIT_TIME_TWO_WEEKS,
                        Service::WAIT_TIME_THREE_WEEKS,
                        Service::WAIT_TIME_MONTH,
                        Service::WAIT_TIME_LONGER
                    ),
                Schema::boolean('is_free'),
                Schema::boolean('is_national'),
                Schema::string('order')
                    ->enum(Search::ORDER_RELEVANCE, Search::ORDER_DISTANCE)
                    ->default('relevance'),
                Schema::object('location')
                    ->required('lat', 'lon')
                    ->properties(
                        Schema::number('lat')
                            ->type(Schema::FORMAT_FLOAT),
                        Schema::number('lon')
                            ->type(Schema::FORMAT_FLOAT)
                    ),
                Schema::integer('distance')
                    ->default(config('hlp.search_distance'))
            );
    }
}
