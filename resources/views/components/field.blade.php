@props(['name', 'label', 'type' => 'text', 'value' => null, 'required' => false, 'hint' => null])
<div {{ $attributes->only('class') }}>
    <label for="{{ $name }}" class="block text-sm font-medium mb-1">{{ $label }}@if($required)<span class="text-red-500"> *</span>@endif</label>
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ $type === 'password' ? '' : old($name, $value) }}"
           {{ $attributes->except('class') }}
           class="w-full rounded-lg border px-3 py-2 text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-200 {{ $errors->has($name) ? 'border-red-400' : 'border-gray-300' }}">
    @if($hint)<p class="mt-1 text-xs text-gray-500">{{ $hint }}</p>@endif
    @error($name)<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
</div>
