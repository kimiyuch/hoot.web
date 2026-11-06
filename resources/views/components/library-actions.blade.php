{{--
    The heart and the Follow button for a show or movie on a card. "fields" identify it (tvmaze_id or tmdb_id);
    "favourite" is its catalog id when it's one of your favourites.
--}}
@props(['type', 'fields' => [], 'label' => null, 'following' => false, 'favourite' => null])

@php $kind = $type === 'movie' ? 'movies' : 'shows'; @endphp

<div {{ $attributes->class('flex items-center gap-1') }}>
    <x-favourite-button :action="route('favourites.'.$kind.'.store')" :fields="$fields" :label="$label"
        :remove-action="$favourite ? route('favourites.'.$kind.'.destroy', $favourite) : null" />
    <x-follow-button :action="route($kind.'.follow')" :fields="$fields" :label="$label" :following="$following" />
</div>
