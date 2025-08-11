<?php
namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class TmdbClient
{
    //Crea el cliente http para hacer la petición
    public function __construct(private ?Client $http = null)
    {
        $this->http ??= new Client([
            'base_uri' => config('services.tmdb.base', env('TMDB_BASE')),
            'timeout'  => 10,
        ]);
    }
    //Funcion que devuelve las peliculas
    private function get(string $path, array $query = []): array
    {
        $query = array_merge([
            'api_key' => env('TMDB_API_KEY'),
            'language' => env('TMDB_LANG', 'es-ES'),
        ], $query);

        $res = $this->http->get($path, ['query' => $query]);
        return json_decode($res->getBody()->getContents(), true);
    }
    //Se encarga de paginar y devolver las peliculas mas populares
    public function popularMovies(int $page = 1): array
    {
        return $this->get('/movie/popular', ['page' => $page]);
    }
    //Devuelve los creditos, las palabras clave l la fecha de salida de una pelicula especifica
    public function movie(int $id): array
    {
        return $this->get("/movie/{$id}", [
            'append_to_response' => 'credits,keywords,release_dates',
        ]);
    }
    //cambia la informacion de las peliculas las cuales han cambiado en un rango de tiempo (entre start date y end date)
    public function changedMovieIds(string $startDate, string $endDate, int $page = 1): array
    {
        return $this->get('/movie/changes', [
            'start_date' => $startDate,
            'end_date'   => $endDate,
            'page'       => $page,
        ]);
    }
}
