```blade
@php
    $s = $schedule ?? null;

    $shiftMap = $shifts->mapWithKeys(function ($shift) {
        return [
            $shift->id => [
                's' => substr($shift->start_time, 0, 5),
                'e' => substr($shift->end_time, 0, 5),
                'c' => (bool) $shift->crosses_midnight,
            ],
        ];
    });
@endphp

@csrf

@if (session('conflicts'))
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
        <div class="mb-1 font-semibold">
            Jadwal tidak dapat disimpan karena ada benturan:
        </div>

        <ul class="list-disc pl-5">
            @foreach (session('conflicts') as $c)
                <li>{{ $c }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if ($errors->any())
    <div class="mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-800">
        Periksa kembali isian Anda.
    </div>
@endif

<div class="grid gap-4 sm:grid-cols-2">
    @if ($s)
        <x-select
            name="employee_id"
            label="Pegawai"
            :options="$employees->pluck('full_name', 'id')"
            :value="$s->employee_id"
            class="sm:col-span-2"
        />
    @else
        <div class="sm:col-span-2">
            <label class="mb-1 block text-sm font-medium">
                Pegawai
                <span class="text-red-500">*</span>
                <span class="font-normal text-gray-500">(boleh lebih dari satu)</span>
            </label>

            <div class="grid max-h-56 gap-1 overflow-y-auto rounded-lg border {{ $errors->has('employee_ids') ? 'border-red-400' : 'border-gray-300' }} p-3 sm:grid-cols-2">
                @foreach ($employees as $e)
                    <label class="flex items-center gap-2 text-sm">
                        <input
                            type="checkbox"
                            name="employee_ids[]"
                            value="{{ $e->id }}"
                            class="rounded border-gray-300 text-emerald-600"
                            @checked(in_array($e->id, old('employee_ids', [])))
                        >

                        {{ $e->full_name }}

                        <span class="text-xs text-gray-400">
                            {{ $e->department?->name }}
                        </span>
                    </label>
                @endforeach
            </div>

            @error('employee_ids')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    @endif

    <x-field
        name="work_date"
        type="date"
        label="Tanggal"
        :value="$s?->work_date?->format('Y-m-d') ?? ($defaultDate ?? null)"
        required
    />

    <x-select
        name="shift_id"
        label="Jenis Jadwal (opsional)"
        :options="$shifts->pluck('name', 'id')"
        :value="$s?->shift_id"
        placeholder="— Kustom —"
    />

    <x-field
        name="start_time"
        type="time"
        label="Jam Mulai"
        :value="$s ? substr($s->start_time, 0, 5) : null"
        required
    />

    <x-field
        name="end_time"
        type="time"
        label="Jam Selesai"
        :value="$s ? substr($s->end_time, 0, 5) : null"
        required
    />

    <div class="sm:col-span-2">
        <input type="hidden" name="crosses_midnight" value="0">

        <label class="inline-flex items-center gap-2 text-sm">
            <input
                id="crosses"
                type="checkbox"
                name="crosses_midnight"
                value="1"
                class="rounded border-gray-300 text-emerald-600"
                @checked(old('crosses_midnight', $s?->crosses_midnight))
            >

            Berakhir keesokan harinya (lintas tengah malam)
        </label>
    </div>

    <x-field
        name="location"
        label="Lokasi / Layanan"
        :value="$s?->location"
        class="sm:col-span-2"
    />

    <x-field
        name="notes"
        label="Catatan"
        :value="$s?->notes"
        class="sm:col-span-2"
    />
</div>

<div class="mt-6 flex gap-2">
    <button
        type="submit"
        class="rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-emerald-700"
    >
        Simpan
    </button>

    <a
        href="{{ route('admin.schedules.index') }}"
        class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm hover:bg-gray-50"
    >
        Batal
    </a>
</div>

<script>
    const shiftMap = @json($shiftMap);

    const shiftSelect = document.getElementById('shift_id');
    const startTime = document.getElementById('start_time');
    const endTime = document.getElementById('end_time');
    const crossesMidnight = document.getElementById('crosses');

    if (shiftSelect) {
        shiftSelect.addEventListener('change', function (event) {
            const shift = shiftMap[event.target.value];

            if (!shift) {
                return;
            }

            if (startTime) {
                startTime.value = shift.s;
            }

            if (endTime) {
                endTime.value = shift.e;
            }

            if (crossesMidnight) {
                crossesMidnight.checked = shift.c;
            }
        });
    }
</script>
```
