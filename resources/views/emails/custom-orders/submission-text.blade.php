{{ $body }}

@foreach ($lines as $line)
- {{ $line->orderLine?->snapshot?->name ?? $t['unknown_product'] }} — {{ $line->quantity }} {{ $t['quantity_unit'] }}{{ $line->thickness !== null ? ', '.\Illuminate\Support\Str::lower($t['thickness']).' '.rtrim(rtrim((string) $line->thickness, '0'), '.').' mm' : '' }}
@foreach ($line->orderLine?->snapshot?->visibleAttributes($lang) ?? collect() as $attr)
    {{ $attr->label }}: {{ $attr->value }}
@endforeach

@endforeach
