<?php

declare(strict_types=1);

namespace App\Search\Elasticsearch;

use App\Models\Collection;
use App\Models\Service;
use App\Search\CriteriaQuery;
use App\Support\Coordinate;

class StandardQueryBuilder implements QueryBuilderInterface
{
    /**
     * @var array
     */
    protected $esQuery;

    public function __construct()
    {
        $this->esQuery = [
            'query' => [
                'function_score' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                'bool' => [
                                    'should' => [],
                                ],
                            ],
                            'filter' => [
                                'bool' => [
                                    'must' => [],
                                ],
                            ],
                        ],
                    ],
                    'functions' => [
                        [
                            'field_value_factor' => [
                                'field' => 'score',
                                'missing' => 1,
                                'modifier' => 'ln1p',
                            ],
                        ],
                        [
                            'filter' => [
                                'term' => [
                                    'is_national' => false,
                                ],
                            ],
                            'weight' => 1.2,
                        ],
                    ],
                    'boost_mode' => 'multiply',
                ],
            ],
        ];
    }

    public function build(CriteriaQuery $query, int $page = null, int $perPage = null): array
    {
        $page = page($page);
        $perPage = per_page($perPage);

        $this->applyFrom($page, $perPage);
        $this->applySize($perPage);
        $this->applyStatus(Service::STATUS_ACTIVE);

        if ($query->hasQuery()) {
            $this->applyQuery($query->getQuery());
        }

        if ($query->hasCategories()) {
            $this->applyCategories($query->getCategories());
        }

        if ($query->hasPersonas()) {
            $this->applyPersonas($query->getPersonas());
        }

        if ($query->hasIsFree()) {
            $this->applyIsFree($query->getIsFree());
        }

        if ($query->hasIsNational()) {
            $this->applyIsNational($query->getIsNational());
        }

        if ($query->hasLocation()) {
            $this->applyLocation($query->getLocation());

            if ($query->hasOrder()) {
                $this->applyOrder($query->getOrder(), $query->getLocation());
            }
        }

        return $this->esQuery;
    }

    protected function applyFrom(int $page, int $perPage): void
    {
        $this->esQuery['from'] = ($page - 1) * $perPage;
    }

    protected function applySize(int $perPage): void
    {
        $this->esQuery['size'] = $perPage;
    }

    protected function applyStatus(string $status): void
    {
        $this->esQuery['query']['function_score']['query']['bool']['filter']['bool']['must'][] = [
            'term' => [
                'status' => $status,
            ],
        ];
    }

    protected function applyQuery(string $query): void
    {
        $this->esQuery['query']['function_score']['query']['bool']['must']['bool']['should'][] = [
            'match_phrase' => [
                'name' => [
                    'query' => $query,
                    'boost' => 3,
                ],
            ],
        ];
        $this->esQuery['query']['function_score']['query']['bool']['must']['bool']['should'][] = [
            'match_phrase' => [
                'intro' => [
                    'query' => $query,
                    'boost' => 2,
                ],
            ],
        ];
        $this->esQuery['query']['function_score']['query']['bool']['must']['bool']['should'][] = [
            'match_phrase' => [
                'description' => [
                    'query' => $query,
                    'boost' => 2,
                ],
            ],
        ];
        $this->esQuery['query']['function_score']['query']['bool']['must']['bool']['should'][] = [
            'match_phrase' => [
                'taxonomy_categories' => [
                    'query' => $query,
                    'boost' => 5,
                ],
            ],
        ];
        $this->esQuery['query']['function_score']['query']['bool']['must']['bool']['should'][] = [
            'match_phrase' => [
                'organisation_name' => [
                    'query' => $query,
                ],
            ],
        ];
    }

    protected function applyCategories(array $categorySlugs): void
    {
        $categoryNames = Collection::query()
            ->whereIn('slug', $categorySlugs)
            ->pluck('name')
            ->all();

        $this->esQuery['query']['function_score']['query']['bool']['filter']['bool']['must'][] = [
            'terms' => [
                'collection_categories' => $categoryNames,
            ],
        ];
    }

    protected function applyPersonas(array $personaSlugs): void
    {
        $personaNames = Collection::query()
            ->whereIn('slug', $personaSlugs)
            ->pluck('name')
            ->all();

        $this->esQuery['query']['function_score']['query']['bool']['filter']['bool']['must'][] = [
            'terms' => [
                'collection_personas' => $personaNames,
            ],
        ];
    }

    protected function applyIsFree(bool $isFree): void
    {
        $this->esQuery['query']['function_score']['query']['bool']['filter']['bool']['must'][] = [
            'term' => [
                'is_free' => $isFree,
            ],
        ];
    }

    protected function applyIsNational(bool $isNational): void
    {
        $this->esQuery['query']['function_score']['query']['bool']['filter']['bool']['must'][] = [
            'term' => [
                'is_national' => $isNational,
            ],
        ];
    }

    protected function applyLocation(Coordinate $coordinate): void
    {
        // Add filter for listings within a 15 miles radius, or national.
        $this->esQuery['query']['function_score']['query']['bool']['filter']['bool']['must'][] = [
            'bool' => [
                'should' => [
                    [
                        'nested' => [
                            'path' => 'service_locations',
                            'query' => [
                                'geo_distance' => [
                                    'distance' => config('hlp.search_distance') . 'mi',
                                    'service_locations.location' => $coordinate->toArray(),
                                ],
                            ],
                        ],
                    ],
                    [
                        'term' => [
                            'is_national' => true,
                        ],
                    ],
                ],
            ],
        ];

        // Apply scoring for favouring results closer to the coordinate.
        $this->esQuery['query']['function_score']['functions'][] = [
            'gauss' => [
                'service_locations.location' => [
                    'origin' => $coordinate->toArray(),
                    'scale' => '1mi',
                ],
            ],
        ];
    }

    protected function applyOrder(string $order, Coordinate $coordinate): void
    {
        if ($order === static::ORDER_DISTANCE) {
            // Filter out national services.
            $this->esQuery['query']['function_score']['query']['bool']['filter']['bool']['must'][] = [
                'term' => [
                    'is_national' => false,
                ],
            ];

            $this->esQuery['sort'] = [
                [
                    '_geo_distance' => [
                        'service_locations.location' => $coordinate->toArray(),
                        'nested_path' => 'service_locations',
                    ],
                ],
            ];
        }
    }
}
