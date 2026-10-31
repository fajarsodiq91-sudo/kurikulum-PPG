@extends('layouts.app')

@section('title', 'Pilih Kelas KBM')
@section('page-title', 'Pilih Kelas KBM')
@section('breadcrumb', 'KBM / Sesi Hari Ini')

@section('content')
    <div class="mb-6">
        <p class="text-sm font-semibold text-amber-600">KBM</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Pilih Kelas KBM</h2>
        <p class="mt-2 text-sm text-slate-500">Anda mengajar lebih dari satu Kelas KBM. Pilih kelas yang akan dipresensi untuk tanggal {{ $date }}.</p>
    </div>

    <section class="max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-3">
            @foreach ($classGrades as $classGrade)
                <a href="{{ route('learning-sessions.today', ['class_grade_id' => $classGrade->id, 'date' => $date]) }}"
                    class="rounded-lg border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:border-brand-400 hover:bg-brand-50">
                    {{ $classGrade->name }}
                </a>
            @endforeach
        </div>
    </section>
@endsection
