<?php

declare(strict_types=1);

namespace App\Search\Elasticsearch;

use App\Http\Resources\ServiceResource;
use App\Models\SearchHistory;
use App\Models\Service;
use App\Models\ServiceLocation;
use App\Support\Coordinate;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

class EloquentMapper
{
    public function paginate(array $esQuery): AnonymousResourceCollection
    {
        $response = Service::searchRaw($esQuery);

        $this->logMetrics($esQuery, $response);

        // Extract the hits from the array.
        $hits = $response['hits']['hits'];

        // Get all of the ID's for the services from the hits.
        $serviceIds = collect($hits)->map->_id->toArray();

        // Implode the service ID's so we can sort by them in database.
        $serviceIdsImploded = implode("','", $serviceIds);
        $serviceIdsImploded = "'$serviceIdsImploded'";

        // Check if the query has been ordered by distance.
        $isOrderedByDistance = isset($esQuery['sort']);

        // Create the query to get the services, and keep ordering from Elasticsearch.
        $services = Service::query()
            ->with('serviceLocations.location')
            ->whereIn('id', $serviceIds)
            ->orderByRaw("FIELD(id,$serviceIdsImploded)")
            ->get();

        // Order the fetched service locations by distance.
        // TODO: Potential solution to the order nested locations in Elasticsearch: https://stackoverflow.com/a/43440405
        if ($isOrderedByDistance) {
            $services = $this->orderServicesByLocation($esQuery, $services);
        }

        // If paginated, then create a new pagination instance.
        $services = new LengthAwarePaginator(
            $services,
            $response['hits']['total'],
            $esQuery['size'],
            ($esQuery['from'] / $esQuery['size']) + 1,
            ['path' => Paginator::resolveCurrentPath()]
        );

        return ServiceResource::collection($services);
    }

    protected function logMetrics(array $esQuery, array $response): void
    {
        SearchHistory::create([
            'query' => $esQuery,
            'count' => $response['hits']['total'],
        ]);
    }

    protected function orderServicesByLocation(array $esQuery, Collection $services): Collection
    {
        return $services->each(function (Service $service) use ($esQuery) {
            $service->serviceLocations = $service->serviceLocations->sortBy(
                function (ServiceLocation $serviceLocation) use ($esQuery) {
                    $location = $esQuery['sort'][0]['_geo_distance']['service_locations.location'];
                    $location = new Coordinate($location['lat'], $location['lon']);

                    return $location->distanceFrom($serviceLocation->location->toCoordinate());
                }
            );
        });
    }
}
