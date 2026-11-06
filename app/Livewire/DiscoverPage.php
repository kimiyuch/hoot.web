<?php

namespace App\Livewire;

use App\Services\Browse;
use App\Services\Premieres;
use App\Services\StreamingServices;
use App\Services\Tmdb;
use App\Timezone;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use Livewire\Component;
use Throwable;

/**
 * Discover: premieres and the TMDB catalog, with filters that apply in place and keep the URL in step.
 * "When" picks the source: soon and new come from the premieres that hoot:refresh builds, "any" asks TMDB discover.
 * Empty values in the URL mean the default, so a plain /discover stays plain.
 */
class DiscoverPage extends Component
{
    #[Url(except: 'soon')]
    public string $when = 'soon';

    #[Url(except: 'tv')]
    public string $type = 'tv';

    #[Url(except: '')]
    public string $service = '';

    /** '' is the configured country (hoot.region). */
    #[Url(except: '')]
    public string $region = '';

    /** 0 is the configured start year, or this year. */
    #[Url(except: 0)]
    public int $from = 0;

    #[Url(except: 'popular')]
    public string $sort = 'popular';

    #[Url(as: 'min_rating', except: 0)]
    public int $minRating = 0;

    #[Url(except: 1)]
    public int $page = 1;

    public function mount(): void
    {
        $this->normalize();
    }

    /** A filter link or select. Any change starts again on page 1; a new type or source also drops the service. */
    public function choose(string $filter, string $value): void
    {
        match ($filter) {
            'type' => [$this->type, $this->service] = [$value, ''],
            'when' => [$this->when, $this->service] = [$value, ''],
            'service' => $this->service = $value,
            'region' => $this->region = $value,
            'from' => $this->from = (int) $value,
            'sort' => $this->sort = $value,
            'min_rating' => $this->minRating = (int) $value,
            default => null,
        };

        $this->page = 1;
        $this->normalize();
    }

    public function goToPage(int $page): void
    {
        $this->page = $page;
        $this->normalize();
        $this->js('window.scrollTo({ top: 0, behavior: "smooth" })');
    }

    public function render(Premieres $premieres, Browse $browse, Tmdb $tmdb, StreamingServices $services): View
    {
        $user = auth()->user();
        $filters = $this->filters();

        $view = [
            'moviesEnabled' => $tmdb->configured(),
            'logos' => $services->logos(),
            'timezone' => Timezone::for(request()),
            'followedMovies' => $user->movies()->pluck('tmdb_id')->flip(),
            'favouriteMovies' => $user->favouriteMovies()->pluck('movies.id', 'tmdb_id'),
        ];

        if ($filters['when'] === 'any') {
            $results = ['results' => [], 'total_pages' => 0];
            $error = null;

            if ($tmdb->configured()) {
                try {
                    $results = $browse->results($filters);
                } catch (Throwable $exception) {
                    report($exception);
                    $error = 'TMDB can’t be reached right now.';
                }
            }

            return view('livewire.discover-page', $view + [
                'filters' => $filters,
                'items' => collect($results['results']),
                // TMDB serves at most 500 pages.
                'totalPages' => min($results['total_pages'], 500),
                'error' => $error,
                'serviceCounts' => null,
                'refreshedAt' => null,
                'followedShows' => $user->shows()->whereNotNull('tmdb_id')->pluck('tmdb_id')->flip(),
                'favouriteShows' => $user->favouriteShows()->whereNotNull('tmdb_id')->pluck('shows.id', 'tmdb_id'),
            ]);
        }

        $data = $premieres->get();
        $items = $this->premieres($data, $filters);

        // Which services have anything here, with how many.
        $serviceCounts = $items->flatMap(fn (array $item) => self::serviceKeys($item))->countBy()->sortDesc();

        if ($filters['service'] && ! $serviceCounts->has($filters['service'])) {
            $filters['service'] = null;
        }
        if ($filters['service']) {
            $items = $items->filter(fn (array $item) => in_array($filters['service'], self::serviceKeys($item), true));
        }

        return view('livewire.discover-page', $view + [
            'filters' => $filters,
            'items' => $items->values(),
            'totalPages' => 1,
            'error' => null,
            'serviceCounts' => $serviceCounts,
            'refreshedAt' => $data['refreshed_at'],
            'followedShows' => $user->shows()->pluck('tvmaze_id')->flip(),
            'favouriteShows' => $user->favouriteShows()->pluck('shows.id', 'tvmaze_id'),
        ]);
    }

    /**
     * The filters with the defaults filled in, as the view, Browse and the filter links use them.
     *
     * @return array{when: string, type: string, service: ?string, region: string, from: int, sort: string, min_rating: int, page: int}
     */
    public function filters(): array
    {
        return [
            'when' => $this->when,
            'type' => $this->type,
            'service' => $this->service ?: null,
            'region' => $this->region ?: config('hoot.region'),
            'from' => $this->from ?: self::defaultFrom(),
            'sort' => $this->sort,
            'min_rating' => $this->minRating,
            'page' => $this->page,
        ];
    }

    /** The URL of these filters with some changed, for the links (middle-click, no JS). */
    public function url(array $changes = []): string
    {
        $query = array_merge([
            'when' => $this->when, 'type' => $this->type, 'service' => $this->service, 'region' => $this->region,
            'from' => $this->from, 'sort' => $this->sort, 'min_rating' => $this->minRating, 'page' => $this->page,
        ], $changes);

        $defaults = ['when' => 'soon', 'type' => 'tv', 'service' => '', 'region' => '', 'from' => 0, 'sort' => 'popular', 'min_rating' => 0, 'page' => 1];

        return route('discover', array_filter($query, fn ($value, $key) => $value !== null && $value !== $defaults[$key], ARRAY_FILTER_USE_BOTH));
    }

    private static function defaultFrom(): int
    {
        return (int) (config('hoot.browse_from_year') ?? now()->year);
    }

    /** Anything the URL or the browser sends that isn't a valid choice falls back to the default. */
    private function normalize(): void
    {
        $this->when = in_array($this->when, ['soon', 'new', 'any'], true) ? $this->when : 'soon';
        $this->type = in_array($this->type, ['tv', 'movie'], true) ? $this->type : 'tv';
        $this->service = array_key_exists($this->service, StreamingServices::all()) ? $this->service : '';
        $this->region = array_key_exists($this->region, config('hoot.regions')) && $this->region !== config('hoot.region') ? $this->region : '';
        $this->from = $this->from >= 1950 && $this->from <= now()->year && $this->from !== self::defaultFrom() ? $this->from : 0;
        $this->sort = array_key_exists($this->sort, Browse::SORTS) ? $this->sort : 'popular';
        $this->minRating = array_key_exists($this->minRating, Browse::MIN_RATINGS) ? $this->minRating : 0;
        $this->page = max(1, min($this->page, 500));

        // Only Any time has pages and the extra filters.
        if ($this->when !== 'any') {
            [$this->region, $this->from, $this->sort, $this->minRating, $this->page] = ['', 0, 'popular', 0, 1];
        }
    }

    /** The premieres for this type and "when". */
    private function premieres(array $data, array $filters): Collection
    {
        $now = CarbonImmutable::now()->utc()->toIso8601String();
        $today = now()->toDateString();

        return $filters['type'] === 'tv'
            ? collect($data['shows'])->filter(fn ($show) => $filters['when'] === 'soon' ? $show['airs_at'] > $now : $show['airs_at'] <= $now)
            // A movie is "soon" while one of its releases is still to come, and "new" when one was in the last month.
            : collect($data['movies'])->filter(function ($movie) use ($filters, $today) {
                $dates = array_filter([$movie['cinema_date'] ?? null, $movie['digital_date'] ?? null]);

                return $filters['when'] === 'soon'
                    ? collect($dates)->contains(fn ($date) => $date > $today)
                    : collect($dates)->contains(fn ($date) => $date <= $today);
            });
    }

    private static function serviceKeys(array $item): array
    {
        return isset($item['services']) ? $item['services'] : array_filter([$item['service'] ?? null]);
    }
}
