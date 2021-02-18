<?php

namespace App\Search;

use App\Contracts\Search;
use App\Http\Resources\ServiceResource;
use App\Models\Collection as CollectionModel;
use App\Models\SearchHistory;
use App\Models\Service;
use App\Models\ServiceLocation;
use App\Support\Coordinate;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Arr;
use InvalidArgumentException;

class ElasticsearchSearch implements Search
{
    const MILES = 'mi';
    const YARDS = 'yd';
    const FEET = 'ft';
    const INCHES = 'in';
    const KILOMETERS = 'km';
    const METERS = 'm';
    const CENTIMETERS = 'cm';
    const MILLIMETERS = 'mm';
    const NAUTICAL_MILES = 'nmi';

    /**
     * @var array
     */
    protected $query;

    /**
     * Search constructor.
     */
    public function __construct()
    {
        $this->query = [
            'from' => 0,
            'size' => config('hlp.pagination_results'),
            'query' => [
                'function_score' => [
                    'query' => [
                        'bool' => [
                            'filter' => [
                                'bool' => [
                                    'must' => [
                                        [
                                            'term' => [
                                                'status' => Service::STATUS_ACTIVE,
                                            ],
                                        ],
                                    ],
                                    'should' => [
                                        //
                                    ],
                                ],
                            ],
                            'must' => [
                                'bool' => [
                                    'should' => [
                                        //
                                    ],
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
                    'boost_mode' => 'sum',
                ],
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public function applyQuery(string $term): Search
    {
        $should = &$this->query['query']['function_score']['query']['bool']['must']['bool']['should'];

        $should[] = $this->matchPhrase('name', $term, 3);
        $should[] = $this->matchPhrase('intro', $term, 2);
        $should[] = $this->matchPhrase('description', $term, 2);
        $should[] = $this->matchPhrase('taxonomy_categories', $term);
        $should[] = $this->matchPhrase('organisation_name', $term);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function applyType(string $type): Search
    {
        $this->query['query']['function_score']['query']['bool']['filter']['bool']['must'][] = [
            'term' => [
                'type' => $type,
            ],
        ];

        return $this;
    }

    /**match
     * @param string $field
     * @param string $term
     * @param int $boost
     * @return array
     */
    protected function matchPhrase(string $field, string $term, int $boost = 1): array
    {
        return [
            'match_phrase' => [
                $field => [
                    'query' => $term,
                    'boost' => $boost,
                ],
            ],
        ];
    }

    /**match
     * @param string $field
     * @param string $term
     * @return array
     */
    protected function term(string $field, string $term): array
    {
        return [
            'term' => [
                $field => $term,
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public function applyCategory(string $category): Search
    {
        return $this->applyCollection($category, 'category');
    }

    /**
     * @inheritDoc
     */
    public function applyPersona(string $persona): Search
    {
        return $this->applyCollection($persona, 'persona');
    }

    /**
     * @inheritDoc
     */
    public function applyWaitTime(string $waitTime): Search
    {
        if (!Service::waitTimeIsValid($waitTime)) {
            throw new InvalidArgumentException("The wait time [$waitTime] is not valid");
        }

        $criteria = [];

        switch ($waitTime) {
            case Service::WAIT_TIME_ONE_WEEK:
                $criteria[] = ['term' => ['wait_time' => Service::WAIT_TIME_ONE_WEEK]];
                break;
            case Service::WAIT_TIME_TWO_WEEKS:
                $criteria[] = ['term' => ['wait_time' => Service::WAIT_TIME_ONE_WEEK]];
                $criteria[] = ['term' => ['wait_time' => Service::WAIT_TIME_TWO_WEEKS]];
                break;
            case Service::WAIT_TIME_THREE_WEEKS:
                $criteria[] = ['term' => ['wait_time' => Service::WAIT_TIME_ONE_WEEK]];
                $criteria[] = ['term' => ['wait_time' => Service::WAIT_TIME_TWO_WEEKS]];
                $criteria[] = ['term' => ['wait_time' => Service::WAIT_TIME_THREE_WEEKS]];
                break;
            case Service::WAIT_TIME_MONTH:
                $criteria[] = ['term' => ['wait_time' => Service::WAIT_TIME_ONE_WEEK]];
                $criteria[] = ['term' => ['wait_time' => Service::WAIT_TIME_TWO_WEEKS]];
                $criteria[] = ['term' => ['wait_time' => Service::WAIT_TIME_THREE_WEEKS]];
                $criteria[] = ['term' => ['wait_time' => Service::WAIT_TIME_MONTH]];
                break;
            case Service::WAIT_TIME_LONGER:
                $criteria[] = ['term' => ['wait_time' => Service::WAIT_TIME_ONE_WEEK]];
                $criteria[] = ['term' => ['wait_time' => Service::WAIT_TIME_TWO_WEEKS]];
                $criteria[] = ['term' => ['wait_time' => Service::WAIT_TIME_THREE_WEEKS]];
                $criteria[] = ['term' => ['wait_time' => Service::WAIT_TIME_MONTH]];
                $criteria[] = ['term' => ['wait_time' => Service::WAIT_TIME_LONGER]];
                break;
        }

        $this->query['query']['function_score']['query']['bool']['filter']['bool']['should'][] = $criteria;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function applyIsFree(bool $isFree): Search
    {
        $this->query['query']['function_score']['query']['bool']['filter']['bool']['must'][] = [
            'term' => [
                'is_free' => $isFree,
            ],
        ];

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function applyIsNational(bool $isNational): Search
    {
        $this->query['query']['function_score']['query']['bool']['filter']['bool']['must'][] = [
            'term' => [
                'is_national' => $isNational,
            ],
        ];

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function applyOrder(string $order, Coordinate $location = null): Search
    {
        if ($order === static::ORDER_DISTANCE) {
            $this->query['sort'] = [
                [
                    '_geo_distance' => [
                        'service_locations.location' => $location->toArray(),
                        'nested_path' => 'service_locations',
                    ],
                ],
            ];
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function applyRadius(Coordinate $location, int $radius): Search
    {
        $this->query['query']['function_score']['query']['bool']['filter']['bool']['must'][] = [
            'nested' => [
                'path' => 'service_locations',
                'query' => [
                    'geo_distance' => [
                        'distance' => $this->distance($radius),
                        'service_locations.location' => $location->toArray(),
                    ],
                ],
            ],
        ];

        $this->query['query']['function_score']['functions'][] = [
            'gauss' => [
                'service_locations.location' => [
                    'origin' => $location->toArray(),
                    'scale' => $this->distance($radius),
                ],
            ],
        ];

        return $this;
    }

    /**
     * @param int $distance
     * @param string $units
     * @return string
     */
    protected function distance(int $distance, string $units = self::MILES): string
    {
        return $distance . $units;
    }

    /**
     * @inheritDoc
     */
    public function getQuery(): array
    {
        return $this->query;
    }

    /**
     * @inheritDoc
     */
    public function paginate(int $page = null, int $perPage = null): AnonymousResourceCollection
    {
        $page = page($page);
        $perPage = per_page($perPage);

        $this->query['from'] = ($page - 1) * $perPage;
        $this->query['size'] = $perPage;

        $response = Service::searchRaw($this->query);

        $this->logMetrics($response);

        return $this->toResource($response, true, $page);
    }

    /**
     * @inheritDoc
     */
    public function get(int $perPage = null): AnonymousResourceCollection
    {
        $this->query['size'] = per_page($perPage);

        $response = Service::searchRaw($this->query);
        $this->logMetrics($response);

        return $this->toResource($response, false);
    }

    /**
     * @param array $response
     * @param bool $paginate
     * @param int|null $page
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    protected function toResource(array $response, bool $paginate = true, int $page = null)
    {
        // Extract the hits from the array.
        $hits = $response['hits']['hits'];

        // Get all of the ID's for the services from the hits.
        $serviceIds = collect($hits)->map->_id->toArray();

        // Implode the service ID's so we can sort by them in database.
        $serviceIdsImploded = implode("','", $serviceIds);
        $serviceIdsImploded = "'$serviceIdsImploded'";

        // Check if the query has been ordered by distance.
        $isOrderedByDistance = isset($this->query['sort']);

        // Create the query to get the services, and keep ordering from Elasticsearch.
        $services = Service::query()
            ->with('serviceLocations.location')
            ->whereIn('id', $serviceIds)
            ->orderByRaw("FIELD(id,$serviceIdsImploded)")
            ->get();

        // Order the fetched service locations by distance.
        // TODO: Potential solution to the order nested locations in Elasticsearch: https://stackoverflow.com/a/43440405
        if ($isOrderedByDistance) {
            $services = $this->orderServicesByLocation($services);
        }

        // If paginated, then create a new pagination instance.
        if ($paginate) {
            $services = new LengthAwarePaginator(
                $services,
                $response['hits']['total'],
                config('hlp.pagination_results'),
                $page,
                ['path' => Paginator::resolveCurrentPath()]
            );
        }

        return ServiceResource::collection($services);
    }

    /**
     * @param array $response
     * @return \App\Search\ElasticsearchSearch
     */
    protected function logMetrics(array $response): Search
    {
        SearchHistory::create([
            'query' => $this->query,
            'count' => $response['hits']['total'],
        ]);

        return $this;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Collection $services
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function orderServicesByLocation(Collection $services): Collection
    {
        return $services->each(function (Service $service) {
            $service->serviceLocations = $service->serviceLocations->sortBy(function (ServiceLocation $serviceLocation) {
                $location = $this->query['sort'][0]['_geo_distance']['service_locations.location'];
                $location = new Coordinate($location['lat'], $location['lon']);

                return $location->distanceFrom($serviceLocation->location->toCoordinate());
            });
        });
    }

    /**
     * @param string $slug
     * @param string $type
     * @throws \Exception
     * @return \App\Search\ElasticsearchSearch
     */
    protected function applyCollection(string $slug, string $type): Search
    {
        $query = CollectionModel::query()
            ->with('taxonomies')
            ->where('slug', $slug);

        if ($type === 'category') {
            $query->categories();
        } elseif ($type === 'persona') {
            $query->personas();
        } else {
            throw new \Exception('Invalid Collection Type');
        }

        $collectionModel = $query->firstOrFail();

        $term = $type === 'category' ? 'collection_categories' : 'collection_personas';

        $this->query['query']['function_score']['query']['bool']['must']['bool']['should'][] = [
            'terms' => [
                'taxonomy_categories.keyword' => $collectionModel->taxonomies->unique('name')->map->name->all(),
            ],
        ];

        foreach ($this->query['query']['function_score']['query']['bool']['filter']['bool']['must'] as &$filter) {
            if (Arr::get($filter, "terms.{$term}") !== null) {
                $filter['terms'][$term][] = $collectionModel->name;
                return $this;
            }
        }

        $this->query['query']['function_score']['query']['bool']['filter']['bool']['must'][] = [
            'terms' => [
                $term => [$collectionModel->name],
            ],
        ];

        return $this;
    }
}
