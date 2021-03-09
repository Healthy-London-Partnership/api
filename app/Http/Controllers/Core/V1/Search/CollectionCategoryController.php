<?php

namespace App\Http\Controllers\Core\V1\Search;

use App\Http\Controllers\Controller;
use App\Http\Requests\Search\Collection\CategoryRequest;
use App\Search\CriteriaQuery;
use App\Search\Elasticsearch\CollectionCategoryQueryBuilder;
use App\Search\Elasticsearch\EloquentMapper;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CollectionCategoryController extends Controller
{
    /**
     * @param \App\Http\Requests\Search\Collection\CategoryRequest $request
     * @param \App\Search\CriteriaQuery $criteria
     * @param \App\Search\Elasticsearch\CollectionCategoryQueryBuilder $builder
     * @param \App\Search\Elasticsearch\EloquentMapper $mapper
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function __invoke(
        CategoryRequest $request,
        CriteriaQuery $criteria,
        CollectionCategoryQueryBuilder $builder,
        EloquentMapper $mapper
    ): AnonymousResourceCollection {
        $criteria->setCategories([$request->input('category')]);

        $query = $builder->build(
            $criteria,
            $request->input('page'),
            $request->input('per_page')
        );

        return $mapper->paginate($query);
    }
}
