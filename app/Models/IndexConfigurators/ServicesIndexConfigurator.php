<?php

namespace App\Models\IndexConfigurators;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use ScoutElastic\IndexConfigurator;
use ScoutElastic\Migratable;

class ServicesIndexConfigurator extends IndexConfigurator
{
    use Migratable;

    /**
     * @return array
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function getSettings(): array
    {
        return [
            'analysis' => [
                'analyzer' => [
                    'default' => [
                        'tokenizer' => 'standard',
                        'filter' => ['lowercase', 'synonym', 'stopwords'],
                    ],
                ],
                'filter' => [
                    'synonym' => [
                        'type' => 'synonym',
                        'synonyms' => $this->getThesaurus(),
                    ],
                    'stopwords' => [
                        'type' => 'stop',
                        'stopwords' => $this->getStopWords(),
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function getStopWords(): array
    {
        $json = Storage::disk('local')->get('elasticsearch/stopwords.json');
        $stopWords = json_decode($json, true);

        return $stopWords;
    }

    /**
     * @return array
     */
    protected function getThesaurus(): array
    {
        try {
            $content = Storage::cloud()->get('elasticsearch/thesaurus.csv');
        } catch (FileNotFoundException $exception) {
            return [];
        }
        $thesaurus = csv_to_array($content);

        $thesaurus = collect($thesaurus)->map(function (array $synonyms) {
            return collect($synonyms);
        });

        $thesaurus = $thesaurus
            ->map(function (Collection $synonyms) {
                return $synonyms
                    ->reject(function (string $term) {
                        // Filter out any empty strings.
                        return $term === '';
                    })
                    ->map(function (string $term) {
                        // Convert each term to lower case.
                        return strtolower($term);
                    })
                    ->implode(', ');
            })
            ->toArray();

        return $thesaurus;
    }
}
