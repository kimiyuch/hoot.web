<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * The streaming services in config('hoot.services'), matched against TVmaze channel names and TMDB providers.
 */
class StreamingServices
{
    public function __construct(private Tmdb $tmdb) {}

    /** @return array<string, array{name: string, tmdb: list<int>, tvmaze: list<string>}> */
    public static function all(): array
    {
        return config('hoot.services');
    }

    public static function name(?string $key): ?string
    {
        return self::all()[$key]['name'] ?? null;
    }

    /** The service a TVmaze channel belongs to, if it's one of ours. */
    public static function forChannel(?string $channel): ?string
    {
        foreach (self::all() as $key => $service) {
            if (in_array($channel, $service['tvmaze'], true)) {
                return $key;
            }
        }

        return null;
    }

    /**
     * The services that stream a movie or show anywhere, from TMDB's watch/providers.
     *
     * @return list<string>
     */
    public static function fromWatchProviders(array $watchProviders): array
    {
        $ids = [];
        foreach ($watchProviders['results'] ?? [] as $country) {
            foreach (['flatrate', 'free', 'ads'] as $kind) {
                foreach ($country[$kind] ?? [] as $provider) {
                    $ids[$provider['provider_id']] = true;
                }
            }
        }

        return array_keys(array_filter(self::all(), fn (array $service) => array_intersect($service['tmdb'], array_keys($ids)) !== []));
    }

    /**
     * Logo URLs by service key, from TMDB (cached for a week). Empty without a TMDB key.
     *
     * @return array<string, string>
     */
    public function logos(): array
    {
        if (! $this->tmdb->configured()) {
            return [];
        }

        return Cache::remember('service-logos', now()->addWeek(), function () {
            try {
                $providers = collect($this->tmdb->providers('movie'))->concat($this->tmdb->providers('tv'))->keyBy('provider_id');
            } catch (Throwable $exception) {
                report($exception);

                return [];
            }

            return collect(self::all())
                ->map(fn (array $service) => $providers->get($service['tmdb'][0])['logo_path'] ?? null)
                ->filter()
                ->map(fn (string $path) => 'https://image.tmdb.org/t/p/w92'.$path)
                ->all();
        });
    }
}
