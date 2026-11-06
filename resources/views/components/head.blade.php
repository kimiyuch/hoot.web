@props(['title' => null, 'description' => 'Possum keeps watch over the shows and movies you follow: every new episode and release, on every service, in your timezone.', 'index' => false])
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $title ?? 'Possum' }}</title>
<meta name="description" content="{{ $description }}">
@unless ($index)
    <meta name="robots" content="noindex, nofollow">
@endunless
<meta name="theme-color" content="#1d1022">

<link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
{{ $slot }}

@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])
