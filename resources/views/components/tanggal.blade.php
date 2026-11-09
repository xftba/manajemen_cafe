@props([
    'name',
    'label',
    'value' => '',
    'type'  => 'date',
    'min'   => null,
    'max'   => null,
    'aktif' => false,
    'ringkas' => false,   // true = tanpa label, dibungkus form sendiri (untuk bilah tab)
])

@if ($ringkas)
    <form method="GET" class="shrink-0">
        @foreach (request()->except([$name, 'page', 'export']) as $k => $v)
            @if (is_scalar($v) && $v !== '')
                <input type="hidden" name="{{ $k }}" value="{{ $v }}">
            @endif
        @endforeach

        <label title="{{ $label }}"
               class="flex h-9 items-center rounded-full px-4 transition
                      {{ $aktif ? 'bg-accent text-white shadow-sm' : 'bg-neutral-200/80 text-neutral-600 hover:bg-neutral-300/70' }}">
            <input type="{{ $type }}" name="{{ $name }}" value="{{ $value }}"
                   @if ($min) min="{{ $min }}" @endif
                   @if ($max) max="{{ $max }}" @endif
                   onchange="this.form.submit()"
                   class="bg-transparent text-[11px] font-medium outline-none {{ $aktif ? '[color-scheme:dark]' : '' }}">
        </label>
    </form>
@else
    <div class="shrink-0">
        <p class="mb-1 text-[10px] font-medium text-neutral-500">{{ $label }}</p>
        <label class="flex h-9 items-center rounded-full px-4 transition
                      {{ $aktif ? 'bg-accent text-white shadow-sm' : 'bg-neutral-200/80 text-neutral-600 hover:bg-neutral-300/70' }}">
            <input type="{{ $type }}" name="{{ $name }}" value="{{ $value }}"
                   @if ($min) min="{{ $min }}" @endif
                   @if ($max) max="{{ $max }}" @endif
                   onchange="this.form.submit()"
                   class="bg-transparent text-[11px] font-medium outline-none {{ $aktif ? '[color-scheme:dark]' : '' }}">
        </label>
    </div>
@endif