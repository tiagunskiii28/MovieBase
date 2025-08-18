<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class MovieMapper
{
    /**
     * Transforma el payload de TMDB a un documento listo para ES.
     * Espera el detalle de película con append: credits, keywords, release_dates.
     */
    public function map(array $m): array
    {
        $genres   = $this->genreNames($m);
        $cast     = $this->topCastNames($m, 10);
        $director = $this->directorName($m);

        return [
            // Claves y strings
            'tmdb_id'        => (string) Arr::get($m, 'id'),
            'title'          => $this->title($m),
            'original_title' => $this->originalTitle($m),
            'overview'       => trim((string) Arr::get($m, 'overview', '')),

            // Keywords / listas
            'genres'         => $genres,

            // Fechas y números
            'release_date'   => $this->dateOrNull(Arr::get($m, 'release_date')),
            'popularity'     => $this->floatOrZero(Arr::get($m, 'popularity')),
            'vote_average'   => $this->floatOrZero(Arr::get($m, 'vote_average')),
            'vote_count'     => $this->intOrZero(Arr::get($m, 'vote_count')),

            // Texto analizable y exactos
            'cast'           => $cast,
            'director'       => $director,

            // Medios
            'poster_path'    => Arr::get($m, 'poster_path') ?: null,
            // Si quieres guardar la URL completa (opcional):
            // 'poster_url'  => $this->posterUrl(Arr::get($m, 'poster_path')),
        ];
    }

    /* -------------------- Helpers de campos -------------------- */

    private function title(array $m): string
    {
        // Para películas usa 'title'; para series TMDB usa 'name'
        return (string) (Arr::get($m, 'title') ?? Arr::get($m, 'name') ?? '');
    }

    private function originalTitle(array $m): string
    {
        return (string) (Arr::get($m, 'original_title') ?? Arr::get($m, 'original_name') ?? '');
    }

    /** @return array<string> */
    private function genreNames(array $m): array
    {
        $genres = Arr::get($m, 'genres', []);
        if (!is_array($genres)) return [];
        return array_values(array_filter(array_map(
            fn ($g) => is_array($g) ? (string) ($g['name'] ?? '') : '',
            $genres
        )));
    }

    /** @return array<string> */
    private function topCastNames(array $m, int $limit = 10): array
    {
        $cast = Arr::get($m, 'credits.cast', []);
        if (!is_array($cast)) return [];
        $names = array_map(
            fn ($c) => is_array($c) ? (string) ($c['name'] ?? '') : '',
            $cast
        );
        $names = array_values(array_filter($names));
        return array_slice($names, 0, max(0, $limit));
    }

    private function directorName(array $m): ?string
    {
        $crew = Arr::get($m, 'credits.crew', []);
        if (!is_array($crew)) return null;

        // Prioriza Job "Director"; si no, busca en departamento "Directing".
        foreach ($crew as $person) {
            if (is_array($person) && Str::lower((string) ($person['job'] ?? '')) === 'director') {
                return (string) ($person['name'] ?? null);
            }
        }
        foreach ($crew as $person) {
            if (is_array($person) && Str::lower((string) ($person['department'] ?? '')) === 'directing') {
                return (string) ($person['name'] ?? null);
            }
        }
        return null;
    }

    /* -------------------- Helpers de tipos -------------------- */

    private function dateOrNull($v): ?string
    {
        // TMDB suele devolver 'YYYY-MM-DD' o ''.
        $v = is_string($v) ? trim($v) : '';
        return $v !== '' ? $v : null; // ES acepta ISO8601; aquí dejamos el formato TMDB.
    }

    private function floatOrZero($v): float
    {
        return is_numeric($v) ? (float) $v : 0.0;
    }

    private function intOrZero($v): int
    {
        return is_numeric($v) ? (int) $v : 0;
    }

    /* -------------------- Opcional: URLs de imágenes -------------------- */

    private function posterUrl(?string $posterPath): ?string
    {
        if (!$posterPath) return null;
        // Configurable por .env si quieres otra resolución
        $base = env('TMDB_IMG_BASE', 'https://image.tmdb.org/t/p/w500');
        return rtrim($base, '/') . '/' . ltrim($posterPath, '/');
    }
}
