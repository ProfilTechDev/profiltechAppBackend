{{ $body }}

Bestilling:
@foreach ($lines as $line)
- {{ $line->orderLine?->snapshot?->name ?? 'Ukendt vare' }} — {{ $line->quantity }} stk{{ $line->thickness !== null ? ', tykkelse '.rtrim(rtrim((string) $line->thickness, '0'), '.').' mm' : '' }}

@foreach ($line->orderLine?->snapshot?->visibleAttributes() ?? collect() as $attr)
    {{ $attr->label }}: {{ $attr->value }}
@endforeach

@endforeach
