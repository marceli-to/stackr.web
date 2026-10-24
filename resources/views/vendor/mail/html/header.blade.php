@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
{{-- A PNG, not the SVG mark: most mail clients don't show SVG. Rendered at 2x. --}}
<img src="{{ asset('mail-logo.png') }}" width="117" height="34" alt="{{ config('app.name') }}" style="display: block; width: 117px; height: 34px; border: 0;">
</a>
</td>
</tr>
