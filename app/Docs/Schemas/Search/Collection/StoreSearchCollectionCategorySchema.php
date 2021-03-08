<?php

namespace App\Docs\Schemas\Search\Collection;

use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class StoreSearchCollectionCategorySchema extends Schema
{
    /**
     * @param string|null $objectId
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
                Schema::string('category')
            );
    }
}
