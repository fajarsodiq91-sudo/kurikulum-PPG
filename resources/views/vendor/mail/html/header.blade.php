@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if (trim($slot) === config('app.name'))
<img src="{{ asset('images/logo-ppg-karawang-timur.png') }}" class="logo" alt="Logo PPG Karawang Timur">
@else
{!! $slot !!}
@endif
</a>
</td>
</tr>
