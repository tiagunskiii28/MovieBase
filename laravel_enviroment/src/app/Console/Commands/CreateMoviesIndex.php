<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CreateMoviesIndex extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-movies-index';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(Client $client)
    {
        $client->indices()->create([
            'index' => 'movies-v1',
            'body'  => [
                'settings' => [
                    'number_of_shards' => 1,
                    'analysis' => [
                        'analyzer' => [
                            'es_std' => ['type' => 'spanish'],
                            'autocomplete' => [
                                'type' => 'custom',
                                'tokenizer' => 'autocomplete_tok',
                                'filter' => ['lowercase']
                            ],
                            'autocomplete_search' => [
                                'type' => 'custom',
                                'tokenizer' => 'standard',
                                'filter' => ['lowercase']
                            ],
                        ],
                        'tokenizer' => [
                            'autocomplete_tok' => [
                                'type' => 'edge_ngram',
                                'min_gram' => 2,
                                'max_gram' => 20,
                                'token_chars' => ['letter','digit','whitespace']
                            ]
                        ]
                    ],
                ],
                'mappings' => [
                'properties' => [
                    'tmdb_id'       => ['type' => 'keyword'],
                    'title'         => ['type' => 'text', 'analyzer' => 'es_std', 'fields' => [
                                        'auto' => ['type' => 'text', 'analyzer' => 'autocomplete', 'search_analyzer' => 'autocomplete_search'],
                                        'raw'  => ['type' => 'keyword']
                                        ]],
                    'original_title' => ['type' => 'text', 'analyzer' => 'es_std'],
                    'overview'      => ['type' => 'text', 'analyzer' => 'es_std'],
                    'genres'        => ['type' => 'keyword'],
                    'release_date'  => ['type' => 'date'],
                    'popularity'    => ['type' => 'float'],
                    'vote_average'  => ['type' => 'float'],
                    'vote_count'    => ['type' => 'integer'],
                    'cast'          => ['type' => 'text', 'analyzer' => 'es_std'],
                    'director'      => ['type' => 'keyword'],
                    'poster_path'   => ['type' => 'keyword'],
                ]
                ]
            ]
        ]);

        $client->indices()->updateAliases([
        'body' => ['actions' => [
            ['remove' => ['index' => '*', 'alias' => 'movies', 'ignore_unavailable' => true]],
            ['add'    => ['index' => 'movies-v1', 'alias' => 'movies']],
        ]]
        ]);
    }
    public function mapMovie(array $m): array {
    // créditos y géneros pueden venir anidados
        $genres = array_map(fn($g) => $g['name'], $m['genres'] ?? []);
        $cast   = array_slice(array_map(fn($c) => $c['name'], $m['credits']['cast'] ?? []), 0, 10);
        $director = collect($m['credits']['crew'] ?? [])->firstWhere('job', 'Director')['name'] ?? null;

        return [
            'tmdb_id'       => (string)$m['id'],
            'title'         => $m['title'] ?? $m['name'] ?? '',
            'original_title' => $m['original_title'] ?? $m['original_name'] ?? '',
            'overview'      => $m['overview'] ?? '',
            'genres'        => $genres,
            'release_date'  => $m['release_date'] ?? null,
            'popularity'    => (float)($m['popularity'] ?? 0),
            'vote_average'  => (float)($m['vote_average'] ?? 0),
            'vote_count'    => (int)($m['vote_count'] ?? 0),
            'cast'          => $cast,
            'director'      => $director,
            'poster_path'   => $m['poster_path'] ?? null,
        ];
    }
}
