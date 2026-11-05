{{-- One streaming service in the Discover filter: logo, name and, on premieres, how many. --}}
@props(['service', 'active' => false, 'count' => null, 'logos' => []])

<a href="{{ request()->fullUrlWithQuery(['service' => $service, 'page' => null]) }}" @class([
    'flex items-center gap-3 rounded-lg px-2 py-1.5 text-base/6 sm:text-sm/6',
    'bg-white/8 text-white' => $active,
    'text-white/60 hover:bg-white/5 hover:text-white' => ! $active,
]) @if ($active) aria-current="page" @endif>
    @isset($logos[$service])
        <img src="{{ $logos[$service] }}" alt="" class="size-6 rounded-md outline-1 -outline-offset-1 outline-white/10">
    @else
        <span class="size-6 rounded-md bg-white/8"></span>
    @endisset
    <span class="flex-1 truncate">{{ \App\Services\StreamingServices::name($service) }}</span>
    @if ($count !== null)
        <span class="text-sm/5 text-white/40 tabular-nums">{{ $count }}</span>
    @endif
</a>
