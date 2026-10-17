@props(['label', 'value', 'icon' => 'users', 'href' => null])
@php $tag = $href ? 'a' : 'div'; @endphp
<{{ $tag }} @if($href) href="{{ $href }}" @endif class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4 {{ $href ? 'hover:border-emerald-300 transition' : '' }}">
    <div class="h-11 w-11 shrink-0 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center">
        <x-icon :name="$icon" class="w-6 h-6" />
    </div>
    <div>
        <div class="text-2xl font-semibold leading-none">{{ $value }}</div>
        <div class="mt-1 text-sm text-gray-500">{{ $label }}</div>
    </div>
</{{ $tag }}>
