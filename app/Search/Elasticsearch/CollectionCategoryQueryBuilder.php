<?php

declare(strict_types=1);

namespace App\Search\Elasticsearch;

use App\Models\Collection;
use App\Models\Service;
use App\Models\Taxonomy;
use App\Search\CriteriaQuery;

class CollectionCategoryQueryBuilder implements QueryBuilderInterface
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
                            'should' => [],
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
        $this->applyIsNational(true);
        $this->applyCategory($query->getCategories()[0]);

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

    protected function applyCategory(string $categorySlug): void
    {
        $category = Collection::query()
            ->with('taxonomies')
            ->where('slug', '=', $categorySlug)
            ->first();

        $this->esQuery['query']['function_score']['query']['bool']['filter']['bool']['must'][] = [
            'term' => [
                'collection_categories' => $category->getAttribute('name'),
            ],
        ];

        $category->taxonomies->each(function (Taxonomy $taxonomy): void {
            $this->esQuery['query']['function_score']['query']['bool']['should'][] = [
                'term' => [
                    'taxonomy_categories' => $taxonomy->getAttribute('name'),
                ],
            ];
        });
    }

    protected function applyIsNational(bool $isNational): void
    {
        $this->esQuery['query']['function_score']['query']['bool']['filter']['bool']['must'][] = [
            'term' => [
                'is_national' => $isNational,
            ],
        ];
    }
}
