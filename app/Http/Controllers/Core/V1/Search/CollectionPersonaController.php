<?php

namespace App\Http\Controllers\Core\V1\Search;

use App\Http\Controllers\Controller;
use App\Http\Requests\Search\Collection\PersonaRequest;
use App\Search\CriteriaQuery;
use App\Search\Elasticsearch\CollectionPersonaQueryBuilder;
use App\Search\Elasticsearch\EloquentMapper;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CollectionPersonaController extends Controller
{
    /**
     * @param \App\Http\Requests\Search\Collection\PersonaRequest $request
     * @param \App\Search\CriteriaQuery $criteria
     * @param \App\Search\Elasticsearch\CollectionPersonaQueryBuilder $builder
     * @param \App\Search\Elasticsearch\EloquentMapper $mapper
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function __invoke(
        PersonaRequest $request,
        CriteriaQuery $criteria,
        CollectionPersonaQueryBuilder $builder,
        EloquentMapper $mapper
    ): AnonymousResourceCollection {
        $criteria->setPersonas([$request->input('persona')]);

        $query = $builder->build(
            $criteria,
            $request->input('page'),
            $request->input('per_page')
        );

        return $mapper->paginate($query);
    }
}
