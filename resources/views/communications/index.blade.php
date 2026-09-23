@extends('layouts.app')

@section('title', 'Komunikasi')
@section('page-title', 'Komunikasi')
@section('breadcrumb', 'Komunikasi / Pesan')

@section('content')
    <div class="mb-6">
        <p class="text-sm font-semibold text-amber-600">Komunikasi</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Komunikasi</h2>
        <p class="mt-2 text-sm text-slate-500">Kelola pengiriman pesan terkait program pelatihan.</p>
    </div>

        @if (session('success'))
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-[minmax(18rem,0.8fr)_minmax(0,1.5fr)]">
            <div class="lg:col-span-1 rounded-xl bg-white p-6 shadow-sm border border-slate-200">
                <h2 class="text-xl font-semibold mb-4">Kirim Pesan</h2>
                <form method="POST" action="{{ route('communications.store') }}">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="training_id">Pelatihan</label>
                        <select id="training_id" name="training_id" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="">-- Pilih --</option>
                            @foreach($trainings as $training)
                                <option value="{{ $training->id }}">{{ $training->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="channel">Saluran</label>
                        <select id="channel" name="channel" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="whatsapp">WhatsApp</option>
                            <option value="email">Email</option>
                            <option value="sms">SMS</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="subject">Subjek</label>
                        <input id="subject" name="subject" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="message">Pesan</label>
                        <textarea id="message" name="message" rows="4" required class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="status">Status</label>
                        <select id="status" name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="draft">Draft</option>
                            <option value="sent">Terkirim</option>
                            <option value="failed">Gagal</option>
                        </select>
                    </div>
                    <button type="submit" class="w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Simpan</button>
                </form>
            </div>

            <div class="lg:col-span-2 rounded-xl bg-white p-6 shadow-sm border border-slate-200">
                <h2 class="text-xl font-semibold mb-4">Riwayat Komunikasi</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-200">
                                <th class="py-3 pr-4">Subjek</th>
                                <th class="py-3 pr-4">Saluran</th>
                                <th class="py-3 pr-4">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($communications as $communication)
                                <tr class="border-b border-slate-100">
                                    <td class="py-3 pr-4">{{ $communication->subject }}</td>
                                    <td class="py-3 pr-4">{{ $communication->channel }}</td>
                                    <td class="py-3 pr-4">{{ $communication->status }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-6 text-center text-slate-500">Belum ada komunikasi.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $communications->links() }}</div>
            </div>
        </div>
    </div>
@endsection
