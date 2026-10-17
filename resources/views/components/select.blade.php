@props(['name', 'label', 'options', 'value' => null, 'placeholder' => '— Pilih —'])
<div {{ $attributes->only('class') }}>
    <label for="{{ $name }}" class="block text-sm font-medium mb-1">{{ $label }}</label>
    <select id="{{ $name }}" name="{{ $name }}"
            class="w-full rounded-lg border px-3 py-2 text-sm bg-white outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-200 {{ $errors->has($name) ? 'border-red-400' : 'border-gray-300' }}">
        <option value="">{{ $placeholder }}</option>
        @foreach($options as $k => $v)
            <option value="{{ $k }}" @selected((string) old($name, $value) === (string) $k)>{{ $v }}</option>
        @endforeach
    </select>
    @error($name)<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
</div>
