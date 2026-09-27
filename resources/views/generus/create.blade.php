@extends('layouts.app')

@section('title', 'Tambah Generus')
@section('page-title', 'Tambah Generus')
@section('breadcrumb', 'Generus / Tambah Generus')

@section('content')
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm font-semibold text-amber-600">Generus</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Tambah Generus</h2>
            <p class="mt-2 text-sm text-slate-500">Lengkapi identitas dan penempatan awal generus.</p>
        </div>
        <a href="{{ route('generus.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Kembali ke data</a>
    </div>

    @if ($errors->any())
        <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
            <p class="font-semibold">Periksa kembali isian form.</p>
            <ul class="mt-2 list-inside list-disc space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-lg font-bold text-slate-950">Asal Generus</h3>
        <p class="mt-1 text-sm text-slate-500">Tentukan asal generus sebelum melanjutkan isian data.</p>

        <div class="mt-4 grid gap-3 sm:grid-cols-2">
            <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-300 p-4 text-sm transition has-[:checked]:border-amber-400 has-[:checked]:bg-amber-50">
                <input type="radio" name="origin_mode" id="origin_mode_external" value="external" class="mt-1" {{ old('origin_mode', 'external') === 'external' ? 'checked' : '' }}>
                <span>
                    <span class="block font-semibold text-slate-900">Luar Daerah / Data Baru</span>
                    <span class="block text-slate-500">Generus baru yang belum pernah tercatat di sistem ini.</span>
                </span>
            </label>
            <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-300 p-4 text-sm transition has-[:checked]:border-amber-400 has-[:checked]:bg-amber-50">
                <input type="radio" name="origin_mode" id="origin_mode_internal" value="internal" class="mt-1" {{ old('origin_mode') === 'internal' ? 'checked' : '' }}>
                <span>
                    <span class="block font-semibold text-slate-900">Dalam Daerah (Pindah Sambung)</span>
                    <span class="block text-slate-500">Generus pindah sambung dari desa/kelompok lain yang terdaftar di sistem ini.</span>
                </span>
            </label>
        </div>

        <div id="internal-origin-fields" class="mt-5 hidden">
            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700" for="origin_village_id">Desa Asal</label>
                    <select id="origin_village_id" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
                        <option value="">-- Semua desa --</option>
                        @foreach($originVillages as $village)
                            <option value="{{ $village->id }}">{{ $village->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700" for="origin_group_id">Kelompok Asal <span class="text-rose-500">*</span></label>
                    <select id="origin_group_id" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
                        <option value="">-- Pilih kelompok asal --</option>
                        @foreach($originGroups as $group)
                            <option value="{{ $group->id }}" data-village-id="{{ $group->village_id }}">{{ $group->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-medium text-slate-700" for="transfer_candidate">Pilih Generus Pindah Sambung <span class="text-rose-500">*</span></label>
                    <select id="transfer_candidate" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
                        <option value="">-- Pilih kelompok asal terlebih dahulu --</option>
                    </select>
                    <p id="transfer-candidate-empty" class="mt-2 hidden text-xs text-rose-600">Tidak ada generus berstatus pindah sambung dari kelompok ini.</p>
                </div>
            </div>
        </div>
    </div>

    <div id="external-form">
        @include('generus.partials.form', [
            'action' => route('generus.store'),
            'generus' => null,
            'currentAssignment' => null,
            'registrationNumber' => $generatedRegistrationNumber,
            'recordNumber' => $generatedRecordNumber,
            'nis' => $generatedRegistrationNumber,
            'allowTransferStatus' => false,
        ])
    </div>

    <div id="internal-form" class="hidden">
        @include('generus.partials.transfer-form', [
            'action' => route('generus.receive-transfer'),
            'regions' => $regions,
            'villages' => $villages,
            'groups' => $groups,
            'levels' => $levels,
            'academicYears' => $academicYears,
        ])
    </div>

    <script>
        (function () {
            const externalRadio = document.getElementById('origin_mode_external');
            const internalRadio = document.getElementById('origin_mode_internal');
            const internalOriginFields = document.getElementById('internal-origin-fields');
            const externalForm = document.getElementById('external-form');
            const internalForm = document.getElementById('internal-form');
            const originVillage = document.getElementById('origin_village_id');
            const originGroup = document.getElementById('origin_group_id');
            const candidateSelect = document.getElementById('transfer_candidate');
            const candidateEmptyHint = document.getElementById('transfer-candidate-empty');
            const transferGenerusId = document.getElementById('transfer_generus_id');

            let candidates = [];

            function updateOriginMode() {
                const isInternal = internalRadio.checked;

                internalOriginFields.classList.toggle('hidden', !isInternal);
                internalForm.classList.toggle('hidden', !isInternal);
                externalForm.classList.toggle('hidden', isInternal);
            }

            function attachVillageGroupFilter(villageSelect, groupSelect) {
                function filter() {
                    const villageId = villageSelect.value;

                    Array.from(groupSelect.options).forEach((option) => {
                        if (option.value === '') {
                            return;
                        }

                        const matches = villageId === '' || option.dataset.villageId === villageId;
                        option.hidden = !matches;

                        if (!matches && option.selected) {
                            groupSelect.value = '';
                        }
                    });
                }

                villageSelect.addEventListener('change', filter);
                filter();
            }

            function resetCandidates(message) {
                candidates = [];
                candidateSelect.innerHTML = '<option value="">' + message + '</option>';
                candidateEmptyHint.classList.add('hidden');
                transferGenerusId.value = '';
                clearTransferFields();
            }

            function clearTransferFields() {
                document.getElementById('transfer_registration_number').value = '-- pilih generus terlebih dahulu --';
                document.getElementById('transfer_nis').value = '-- pilih generus terlebih dahulu --';
                ['full_name', 'gender', 'birth_date', 'school_name', 'father_name', 'mother_name', 'father_occupation', 'mother_occupation', 'phone_number', 'birth_place', 'school_grade', 'learning_class', 'educational_level', 'birth_order', 'sibling_count'].forEach((field) => {
                    const input = document.getElementById('transfer_' + field);
                    if (input) {
                        input.value = '';
                    }
                });
            }

            async function loadCandidates(groupId) {
                if (!groupId) {
                    resetCandidates('-- Pilih kelompok asal terlebih dahulu --');
                    return;
                }

                candidateSelect.innerHTML = '<option value="">Memuat...</option>';

                const response = await fetch('{{ route('generus.pending-transfers') }}?group_id=' + encodeURIComponent(groupId), {
                    headers: { 'Accept': 'application/json' },
                });

                if (!response.ok) {
                    resetCandidates('-- Gagal memuat data --');
                    return;
                }

                const payload = await response.json();
                candidates = payload.data || [];

                if (candidates.length === 0) {
                    candidateSelect.innerHTML = '<option value="">-- Tidak ada generus pindah sambung --</option>';
                    candidateEmptyHint.classList.remove('hidden');
                    transferGenerusId.value = '';
                    clearTransferFields();
                    return;
                }

                candidateEmptyHint.classList.add('hidden');
                candidateSelect.innerHTML = '<option value="">-- Pilih generus --</option>' + candidates
                    .map((candidate) => '<option value="' + candidate.id + '">' + candidate.full_name + '</option>')
                    .join('');
            }

            function fillTransferFields(candidate) {
                transferGenerusId.value = candidate.id;
                document.getElementById('transfer_registration_number').value = candidate.registration_number;
                document.getElementById('transfer_nis').value = candidate.nis || '-';

                const fields = ['full_name', 'gender', 'birth_date', 'school_name', 'father_name', 'mother_name', 'father_occupation', 'mother_occupation', 'phone_number', 'birth_place', 'school_grade', 'learning_class', 'educational_level', 'birth_order', 'sibling_count'];
                fields.forEach((field) => {
                    const input = document.getElementById('transfer_' + field);
                    if (input) {
                        input.value = candidate[field] ?? '';
                    }
                });
            }

            document.getElementById('transfer-form').addEventListener('submit', (event) => {
                if (!transferGenerusId.value) {
                    event.preventDefault();
                    alert('Pilih generus pindah sambung terlebih dahulu.');
                }
            });

            externalRadio.addEventListener('change', updateOriginMode);
            internalRadio.addEventListener('change', updateOriginMode);
            originGroup.addEventListener('change', () => loadCandidates(originGroup.value));
            candidateSelect.addEventListener('change', () => {
                const candidate = candidates.find((item) => String(item.id) === candidateSelect.value);

                if (candidate) {
                    fillTransferFields(candidate);
                } else {
                    transferGenerusId.value = '';
                    clearTransferFields();
                }
            });

            attachVillageGroupFilter(originVillage, originGroup);
            attachVillageGroupFilter(document.getElementById('transfer_village_id'), document.getElementById('transfer_group_id'));
            updateOriginMode();
        })();
    </script>
@endsection
