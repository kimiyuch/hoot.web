<x-mail::message>
# Your week on Hoot

Hi {{ $user->name }},

@if ($days->isNotEmpty())
here’s what comes out for what you follow from {{ $from->format('l j F') }} on.

@foreach ($days as $day => $releases)
**{{ \Carbon\CarbonImmutable::parse($day)->format('D j M') }}**

@foreach ($releases as $release)
- {{ \App\Notifications\WeeklyDigest::describe($release) }}@if ($release['timed']), {{ $release['at']->format('H:i') }}@endif

@endforeach

@endforeach
@else
nothing you follow comes out this week.

@endif
@if ($followers->isNotEmpty())
**New followers:** {{ $followers->take(5)->pluck('name')->join(', ', ' and ') }}@if ($followers->count() > 5) and {{ $followers->count() - 5 }} more @endif.

@endif
<x-mail::button :url="route('for-you')">
Open For you
</x-mail::button>

Times are {{ str_replace('_', ' ', last(explode('/', config('hoot.timezone')))) }} time. You get this mail because it’s switched on in [Settings]({{ route('settings') }}#mail); turn it off there.
</x-mail::message>
