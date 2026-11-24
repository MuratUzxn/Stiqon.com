{{-- Timetable: a grid with a few filled cells. The title sits low enough for the square format. --}}
@php
    $filled = ['0-1' => '#1d3557', '1-0' => '#457b9d', '1-3' => '#d9a441', '2-2' => '#1d3557', '3-1' => '#c8553d', '3-4' => '#457b9d', '4-0' => '#d9a441', '4-3' => '#1d3557'];
@endphp
<rect width="100" height="141.4" fill="#e9f0f3" />
<text x="16" y="38" font-size="13" font-weight="700" letter-spacing="-0.6" fill="#1d3557">Kursplan</text>
<text x="16" y="46" font-size="4.2" font-weight="700" letter-spacing="1.8" fill="#457b9d">FRÜHJAHR</text>
@foreach (range(0, 4) as $row)
    @foreach (range(0, 4) as $column)
        <rect
            x="{{ 16 + $column * 14 }}" y="{{ 54 + $row * 12.4 }}" width="12.2" height="10.6" rx="2"
            fill="{{ $filled["{$row}-{$column}"] ?? '#ffffff' }}"
        />
    @endforeach
@endforeach
