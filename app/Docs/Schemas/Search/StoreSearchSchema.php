<?php

namespace App\Docs\Schemas\Search;

use App\Search\CriteriaQuery;
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
                Schema::string('category'),
                Schema::string('persona'),
                Schema::boolean('is_free'),
                Schema::boolean('is_national'),
                Schema::string('order')
                    ->enum(CriteriaQuery::ORDER_RELEVANCE, CriteriaQuery::ORDER_DISTANCE)
                    ->default('relevance'),
                Schema::object('location')
                    ->required('lat', 'lon')
                    ->properties(
                        Schema::number('lat')
                            ->type(Schema::FORMAT_FLOAT),
                        Schema::number('lon')
                            ->type(Schema::FORMAT_FLOAT)
                    )
            );
    }
}
