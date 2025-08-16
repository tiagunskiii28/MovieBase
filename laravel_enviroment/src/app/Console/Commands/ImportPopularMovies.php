<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TmdbClient;
use App\Services\MovieMapper;
use Elastic\Elasticsearch\Client;

//TODO CONFIGURAR SERVICIO DE MOVIEMAPPER
class ImportPopularMovies extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tmdb:import-popular';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importa películas populares de TMDB a Elasticsearch';

    /**
     * Execute the console command.
     */
    public function handle(TmdbClient $tmdb, MovieMapper $mapper, Client $es)
    {
        $page = 1;
        do {
            $data = $tmdb->popularMovies($page);
            $results = $data['results'] ?? [];
            if (!$results) {
                break;
            }
            $ops = [];
            foreach ($result as $item) {
                $full = $tmdb->movie($item['id']);
                $doc = $mapper->map($full);
                $ops[] = ['index' => ['_index' => 'movies', '_id' => $doc['tmdb_id']]];
                $ops[] = $doc;
            }
            if ($ops) {
                $es->bulk(['body' => $ops, 'refresh' => false]);
                $this->info("Pagina $page importada (" . count($results) . " películas).");
            }
            $page++;
        } while ($page <= ($data['total_pages'] ?? 1));
        $es->indices()->refresh(['index' => 'movies']);
        $this->info('Importación completada.');
    }
}
