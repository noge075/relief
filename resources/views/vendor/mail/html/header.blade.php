@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
    @php
        $logoPath = public_path('img/logo.jpg');
        $logoData = '';
        if (file_exists($logoPath)) {
            $logoData = base64_encode(file_get_contents($logoPath));
        }
    @endphp
    @if($logoData)
        <img src="data:image/jpeg;base64,{{ $logoData }}" alt="{{ config('app.name') }}" class="logo">
    @else
        {{ config('app.name') }}
    @endif
</a>
</td>
</tr>
