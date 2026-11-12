@extends('layouts.app')

@section('title', 'Penerbitan Rapor')
@section('page-title', 'Penerbitan Rapor')
@section('breadcrumb', 'Rapor / Penerbitan')

@section('content')
    <div class="mb-6">
        <p class="text-sm font-semibold text-amber-600">PPG Daerah</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Penerbitan Rapor</h2>
        <p class="mt-2 text-sm text-slate-500">
            Konfirmasi penerbitan rapor per tahun ajaran &amp; semester. Untuk semester yang menentukan kenaikan kelas (semester 2), rapor baru terbit setelah setiap Pelaksana PPG Kelompok mengonfirmasi siapa saja generusnya yang tinggal kelas (selain itu otomatis naik). Untuk semester 1, rapor langsung terbit begitu dikonfirmasi di sini.
        </p>
    </div>

    @if (session('success'))
        <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-slate-950">Konfirmasi Penerbitan Baru</h3>

        @if ($errors->any())
            <div class="mt-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('report-card-publications.store') }}" class="mt-4 flex flex-wrap items-end gap-4">
            @csrf
            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700" for="academic_year_id">Tahun Ajaran</label>
                <select id="academic_year_id" name="academic_year_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    <option value="">-- Pilih --</option>
                    @foreach ($academicYears as $year)
                        <option value="{{ $year->id }}">{{ $year->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700" for="semester_id">Semester</label>
                <select id="semester_id" name="semester_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    <option value="">-- Pilih --</option>
                    @foreach ($semesters as $semester)
                        <option value="{{ $semester->id }}">{{ $semester->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">Konfirmasi Penerbitan</button>
        </form>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-slate-950">Riwayat Penerbitan</h3>

        <div class="mt-4 divide-y divide-slate-100">
            @forelse ($publications as $publication)
                <div class="py-4">
                    <div class="flex items-center justify-between gap-3">
                        <span class="font-semibold text-slate-900">{{ $publication->semester->name }} {{ $publication->academicYear->name }}</span>
                        @if ($publication->isPublished())
                            <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Terbit</span>
                        @else
                            <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700">Menunggu Konfirmasi Kelompok</span>
                        @endif
                    </div>

                    @if ($publication->requires_group_confirmation)
                        @php($confirmedCount = $publication->groupConfirmations->where('status', 'confirmed')->count())
                        <p class="mt-1 text-sm text-slate-500">{{ $confirmedCount }}/{{ $publication->groupConfirmations->count() }} kelompok sudah konfirmasi.</p>
                        <ul class="mt-2 flex flex-wrap gap-2">
                            @foreach ($publication->groupConfirmations as $confirmation)
                                <li class="rounded-full px-2.5 py-1 text-xs font-medium {{ $confirmation->status === 'confirmed' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $confirmation->group->name }}
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @empty
                <p class="py-6 text-center text-sm text-slate-500">Belum ada riwayat penerbitan rapor.</p>
            @endforelse
        </div>
    </section>
@endsection
