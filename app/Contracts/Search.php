<?php

namespace App\Contracts;

use App\Search\CriteriaQuery;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

interface Search
{
    const ORDER_RELEVANCE = 'relevance';
    const ORDER_DISTANCE = 'distance';

    /**
     * @param \App\Search\CriteriaQuery $query
     * @param int|null $page
     * @param int|null $perPage
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function search(CriteriaQuery $query, int $page = null, int $perPage = null): AnonymousResourceCollection;
}
