@php
echo '<?php';
$lit = fn ($v) => $v === null ? 'null'
    : (is_bool($v) ? ($v ? 'true' : 'false')
    : (is_string($v) ? var_export($v, true)
    : (string) $v));
@endphp


namespace Database\Seeders;

use Illuminate\Database\Seeder;

class {{ $className }} extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
@foreach ($entries as $entry)
@php
$class = ltrim((string) array_key_first($entry), '\\');
$fields = $entry[array_key_first($entry)];
@endphp
        \{{ $class }}::insert([
@foreach ($fields as $col => $value)
@if (is_array($value) && count($value) === 1 && is_string($k = array_key_first($value)) && str_contains($k, '\\'))
@php
$invClass = ltrim((string) array_key_first($value), '\\');
$chain = $value[array_key_first($value)];
@endphp
            '{{ $col }}' => \{{ $invClass }}@foreach ($chain as $method => $params){!! $loop->first ? '::' : '->' !!}{{ $method }}({!! $params === null || $params === [] ? '' : (is_array($params) ? implode(', ', array_map($lit, $params)) : $lit($params)) !!})@endforeach,
@elseif (is_array($value))
            '{{ $col }}' => [{!! implode(', ', array_map($lit, $value)) !!}],
@else
            '{{ $col }}' => {!! $lit($value) !!},
@endif
@endforeach
        ]);

@endforeach
    }
}
