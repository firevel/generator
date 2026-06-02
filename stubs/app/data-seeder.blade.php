@php
echo '<?php';

// Recursive PHP-literal renderer. Handles the seeder Value grammar:
//   - scalars                                        → PHP literal
//   - { ClassFQN: { method: params, … } } (key '\\') → chain expression
//   - list-shaped arrays                             → PHP list literal
//   - assoc arrays                                   → PHP assoc literal
//
// Same shape as `stubs/api-resource/migration.blade.php`'s inline param
// renderer — just bigger because chain steps can take assoc-map or nested
// invocation args (needed to support the schema generator's `nested`
// directive, which expands to `Model::create([…])->getKey()`).
$renderValue = null;
$renderValue = function ($v) use (&$renderValue) {
    if ($v === null) { return 'null'; }
    if (is_bool($v)) { return $v ? 'true' : 'false'; }
    // var_export handles ints, floats (preserves trailing .0 so a whole-number
    // float stays a float in the emitted code), and strings (with escapes).
    if (! is_array($v)) { return var_export($v, true); }

    // Invocation: single-key map whose key contains a backslash (FQN).
    if (count($v) === 1) {
        $k = array_key_first($v);
        if (is_string($k) && str_contains($k, '\\')) {
            $chain = $v[$k];
            $out = '\\' . ltrim($k, '\\');
            $first = true;
            foreach ($chain as $method => $params) {
                $sep = $first ? '::' : '->';
                $first = false;
                if ($params === null || $params === []) {
                    $out .= $sep . $method . '()';
                } elseif (is_array($params) && array_is_list($params)) {
                    // Multiple positional args.
                    $out .= $sep . $method . '(' . implode(', ', array_map($renderValue, $params)) . ')';
                } else {
                    // Single arg — scalar, assoc-map, list literal, or nested invocation.
                    $out .= $sep . $method . '(' . $renderValue($params) . ')';
                }
            }
            return $out;
        }
    }

    // List literal.
    if (array_is_list($v)) {
        return '[' . implode(', ', array_map($renderValue, $v)) . ']';
    }

    // Assoc literal.
    $parts = [];
    foreach ($v as $key => $val) {
        $parts[] = var_export((string) $key, true) . ' => ' . $renderValue($val);
    }
    return '[' . implode(', ', $parts) . ']';
};
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
// A belongsToMany pivot link is a model-less, raw table insert:
//   { "table": "post_tag", "insert": { "post_id": .., "tag_id": .. } }
// Everything else is a model row keyed by its FQN: { "App\\Models\\X": cols }.
$isPivot = array_key_exists('table', $entry) && array_key_exists('insert', $entry);
if ($isPivot) {
    $pivotTable = $entry['table'];
    $fields = $entry['insert'];
} else {
    $class = ltrim((string) array_key_first($entry), '\\');
    $fields = $entry[array_key_first($entry)];
}
@endphp
@if ($isPivot)
        \Illuminate\Support\Facades\DB::table('{{ $pivotTable }}')->insert([
@else
        \{{ $class }}::insert([
@endif
@foreach ($fields as $col => $value)
            '{{ $col }}' => {!! $renderValue($value) !!},
@endforeach
        ]);

@endforeach
    }
}
