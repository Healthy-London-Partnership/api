<?php

namespace App\Http\Controllers\Core\V1;

use App\Events\EndpointHit;
use App\Generators\UniqueSlugGenerator;
use App\Http\Controllers\Controller;
use App\Http\Requests\Collection\Persona\DestroyRequest;
use App\Http\Requests\Collection\Persona\IndexRequest;
use App\Http\Requests\Collection\Persona\ShowRequest;
use App\Http\Requests\Collection\Persona\StoreRequest;
use App\Http\Requests\Collection\Persona\UpdateRequest;
use App\Http\Resources\CollectionPersonaResource;
use App\Http\Responses\ResourceDeleted;
use App\Models\Collection;
use App\Models\File;
use App\Models\Taxonomy;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\Filter;
use Spatie\QueryBuilder\QueryBuilder;

class CollectionPersonaController extends Controller
{
    /**
     * CollectionPersonaController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:api')->except('index', 'show');
    }

    /**
     * Display a listing of the resource.
     *
     * @param \App\Http\Requests\Collection\Persona\IndexRequest $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(IndexRequest $request)
    {
        $baseQuery = Collection::personas()
            ->orderBy('order');

        $personas = QueryBuilder::for($baseQuery)
            ->allowedFilters([
                Filter::exact('id'),
            ])
            ->with('taxonomies')
            ->paginate(per_page($request->per_page));

        event(EndpointHit::onRead($request, 'Viewed all collection personas'));

        return CollectionPersonaResource::collection($personas);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \App\Http\Requests\Collection\Persona\StoreRequest $request
     * @param \App\Generators\UniqueSlugGenerator $slugGenerator
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRequest $request, UniqueSlugGenerator $slugGenerator)
    {
        return DB::transaction(function () use ($request, $slugGenerator) {
            // Parse the sideboxes.
            $sideboxes = array_map(function (array $sidebox): array {
                return [
                    'title' => $sidebox['title'],
                    'content' => sanitize_markdown($sidebox['content']),
                ];
            }, $request->sideboxes ?? []);

            // Create the collection record.
            $persona = Collection::create([
                'type' => Collection::TYPE_PERSONA,
                'slug' => $slugGenerator->generate($request->name, table(Collection::class)),
                'name' => $request->name,
                'meta' => [
                    'intro' => $request->intro,
                    'subtitle' => $request->subtitle,
                    'image_file_id' => $request->image_file_id,
                    'sideboxes' => $sideboxes,
                ],
                'order' => $request->order,
            ]);

            if ($request->filled('image_file_id')) {
                File::findOrFail($request->image_file_id)->assigned();
            }

            // Create all of the pivot records.
            $taxonomies = Taxonomy::whereIn('id', $request->category_taxonomies)->get();
            $persona->syncCollectionTaxonomies($taxonomies);

            // Reload the newly created pivot records.
            $persona->load('taxonomies');

            event(EndpointHit::onCreate($request, "Created collection persona [{$persona->id}]", $persona));

            return new CollectionPersonaResource($persona);
        });
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Http\Requests\Collection\Persona\ShowRequest $request
     * @param \App\Models\Collection $collection
     * @return \App\Http\Resources\CollectionPersonaResource
     */
    public function show(ShowRequest $request, Collection $collection)
    {
        $baseQuery = Collection::query()
            ->where('id', $collection->id);

        $collection = QueryBuilder::for($baseQuery)
            ->firstOrFail();

        event(EndpointHit::onRead($request, "Viewed collection persona [{$collection->id}]", $collection));

        return new CollectionPersonaResource($collection);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \App\Http\Requests\Collection\Persona\UpdateRequest $request
     * @param \App\Generators\UniqueSlugGenerator $slugGenerator
     * @param \App\Models\Collection $collection
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateRequest $request, UniqueSlugGenerator $slugGenerator, Collection $collection)
    {
        return DB::transaction(function () use ($request, $slugGenerator, $collection) {
            // Parse the sideboxes.
            $sideboxes = array_map(function (array $sidebox): array {
                return [
                    'title' => $sidebox['title'],
                    'content' => sanitize_markdown($sidebox['content']),
                ];
            }, $request->sideboxes ?? []);

            // Update the collection record.
            $collection->update([
                'slug' => $slugGenerator->compareEquals($request->name, $collection->slug)
                    ? $collection->slug
                    : $slugGenerator->generate($request->name, table(Collection::class)),
                'name' => $request->name,
                'meta' => [
                    'intro' => $request->intro,
                    'subtitle' => $request->subtitle,
                    'image_file_id' => $request->has('image_file_id')
                        ? $request->image_file_id
                        : $collection->meta['image_file_id'],
                    'sideboxes' => $sideboxes,
                ],
                'order' => $request->order,
            ]);

            if ($request->filled('image_file_id')) {
                File::findOrFail($request->image_file_id)->assigned();
            }

            // Update or create all of the pivot records.
            $taxonomies = Taxonomy::whereIn('id', $request->category_taxonomies)->get();
            $collection->syncCollectionTaxonomies($taxonomies);

            event(EndpointHit::onUpdate($request, "Updated collection persona [{$collection->id}]", $collection));

            return new CollectionPersonaResource($collection);
        });
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Http\Requests\Collection\Persona\DestroyRequest $request
     * @param \App\Models\Collection $collection
     * @return \Illuminate\Http\Response
     */
    public function destroy(DestroyRequest $request, Collection $collection)
    {
        return DB::transaction(function () use ($request, $collection) {
            event(EndpointHit::onDelete($request, "Deleted collection persona [{$collection->id}]", $collection));

            $collection->delete();

            return new ResourceDeleted('collection persona');
        });
    }
}
