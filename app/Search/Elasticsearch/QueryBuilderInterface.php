<?php

declare(strict_types=1);

namespace App\Search\Elasticsearch;

use App\Search\CriteriaQuery;

interface QueryBuilderInterface
{
    const ORDER_RELEVANCE = 'relevance';
    const ORDER_DISTANCE = 'distance';

    public function build(CriteriaQuery $query, int $page = null, int $perPage = null): array;
}
