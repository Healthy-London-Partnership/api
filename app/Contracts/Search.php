<?php

namespace App\Contracts;

use App\Support\Coordinate;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

interface Search
{
    const ORDER_RELEVANCE = 'relevance';
    const ORDER_DISTANCE = 'distance';

    /**
     * @param string $term
     * @return \App\Contracts\Search
     */
    public function applyQuery(string $term): Search;

    /**
     * @param string $type
     * @return \App\Contracts\Search
     */
    public function applyType(string $type): Search;

    /**
     * @param string $category
     * @param string $type
     * @return \App\Contracts\Search
     */
    public function applyCollection(string $category, string $type): Search;

    /**
     * @param string $waitTime
     * @return \App\Contracts\Search
     */
    public function applyWaitTime(string $waitTime): Search;

    /**
     * @param bool $isFree
     * @return \App\Contracts\Search
     */
    public function applyIsFree(bool $isFree): Search;

    /**
     * @param bool $isNational
     * @return \App\Contracts\Search
     */
    public function applyIsNational(bool $isNational): Search;

    /**
     * @param string $order
     * @param \App\Support\Coordinate|null $location
     * @return \App\Contracts\Search
     */
    public function applyOrder(string $order, Coordinate $location = null): Search;

    /**
     * @param \App\Support\Coordinate $location
     * @param int $radius
     * @return \App\Contracts\Search
     */
    public function applyRadius(Coordinate $location, int $radius): Search;

    /**
     * Returns the underlying query. Only intended for use in testing.
     *
     * @return array
     */
    public function getQuery(): array;

    /**
     * @param int|null $page
     * @param int|null $perPage
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function paginate(int $page = null, int $perPage = null): AnonymousResourceCollection;

    /**
     * @param int|null $perPage
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function get(int $perPage = null): AnonymousResourceCollection;
}
