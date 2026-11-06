{{-- A heart: adds to your favourites (POST), or with a remove action takes it out again (DELETE). --}}
@props(['action' => null, 'fields' => [], 'removeAction' => null, 'label' => null])

@php
    $heart = 'm9.653 16.915-.005-.003-.019-.01a20.759 20.759 0 0 1-1.162-.682 22.045 22.045 0 0 1-2.582-1.9C4.045 12.733 2 10.352 2 7.5a4.5 4.5 0 0 1 8-2.828A4.5 4.5 0 0 1 18 7.5c0 2.852-2.044 5.233-3.885 6.82a22.049 22.049 0 0 1-3.744 2.582l-.019.01-.005.003h-.002a.739.739 0 0 1-.69.001l-.002-.001Z';
    $favourite = (bool) $removeAction;
    $title = $favourite ? "Remove {$label} from favourites" : "Add {$label} to favourites";
@endphp

<form method="post" action="{{ $removeAction ?? $action }}" {{ $attributes }}>
    @csrf
    @if ($favourite)
        @method('delete')
    @else
        @foreach ($fields as $name => $value)
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endforeach
    @endif
    <button type="submit" title="{{ $title }}" aria-label="{{ $title }}" aria-pressed="{{ $favourite ? 'true' : 'false' }}"
        @class(['relative grid size-8 place-items-center rounded-lg hover:bg-white/5', 'text-calendar-400' => $favourite, 'text-white/40 hover:text-white' => ! $favourite])>
        <svg viewBox="0 0 20 20" aria-hidden="true" class="size-4.5" @if ($favourite) fill="currentColor" @else fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" @endif><path d="{{ $heart }}"/></svg>
        <span class="absolute top-1/2 left-1/2 size-[max(100%,3rem)] -translate-1/2 pointer-fine:hidden" aria-hidden="true"></span>
    </button>
</form>
