<?php

namespace App\Http\Controllers\Core\V1;

use App\Contracts\Search;
use App\Http\Controllers\Controller;
use App\Http\Requests\Search\Request;
use App\Search\CriteriaQuery;
use App\Search\Elasticsearch\EloquentMapper;
use App\Search\Elasticsearch\WebsiteQueryBuilder;
use App\Support\Coordinate;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SearchController extends Controller
{
    /**
     * @param \App\Http\Requests\Search\Request $request
     * @param \App\Search\CriteriaQuery $criteria
     * @param \App\Search\Elasticsearch\WebsiteQueryBuilder $builder
     * @param \App\Search\Elasticsearch\EloquentMapper $mapper
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function __invoke(
        Request $request,
        CriteriaQuery $criteria,
        WebsiteQueryBuilder $builder,
        EloquentMapper $mapper
    ): AnonymousResourceCollection {
        if ($request->has('query')) {
            $criteria->setQuery($request->input('query'));
        }

        if ($request->has('category')) {
            $criteria->setCategories(explode(',', $request->input('category')));
        }

        if ($request->has('persona')) {
            $criteria->setPersonas(explode(',', $request->input('persona')));
        }

        if ($request->has('is_free')) {
            $criteria->setIsFree($request->input('is_free'));
        }

        if ($request->has('location')) {
            $criteria->setLocation(
                new Coordinate(
                    $request->input('location.lat'),
                    $request->input('location.lon')
                )
            );

            if ($request->has('radius')) {
                $criteria->setRadius($request->input('radius'));
            }
        }

        if ($request->has('order')) {
            $criteria->setOrder($request->input('order'));
        }

        $query = $builder->build(
            $criteria,
            $request->input('page'),
            $request->input('per_page')
        );

        return $mapper->paginate($query);
    }
}
