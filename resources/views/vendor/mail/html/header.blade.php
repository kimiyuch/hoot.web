@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block; text-decoration: none;">
{{-- A PNG, not the SVG: most mail clients don't show SVG. 112px, shown at 56px for sharp screens. --}}
<img src="{{ asset('images/mail-icon.png') }}" width="56" height="56" alt="" style="display: block; margin: 0 auto; width: 56px; height: 56px; border: 0;">
<span class="wordmark">{{ $slot }}</span>
</a>
</td>
</tr>
