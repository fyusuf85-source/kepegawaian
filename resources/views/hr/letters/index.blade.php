@extends('layouts.app', ['title' => 'Surat', 'heading' => 'Surat Kepegawaian', 'subheading' => 'Teguran, pembinaan, pemberhentian, dan keterangan'])

@section('content')
    <form method="GET" class="flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div><label class="block text-xs font-medium text-slate-500">Cari</label><input name="q" value="{{ request('q') }}" placeholder="Nomor / perihal / pegawai" class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm"></div>
        <div>
            <label class="block text-xs font-medium text-slate-500">Jenis</label>
            <select name="letter_type" class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="">Semua</option>
                @foreach(['teguran', 'pembinaan', 'pemberhentian', 'keterangan', 'lain'] as $type)
                    <option value="{{ $type }}" @selected(request('letter_type') === $type)>{{ ucfirst($type) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500">Status</label>
            <select name="status" class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="">Semua</option>
                @foreach(['draf', 'menunggu_persetujuan', 'disetujui', 'terkirim', 'dibatalkan'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ str_replace('_', ' ', $status) }}</option>
                @endforeach
            </select>
        </div>
        <button class="rounded-lg border border-slate-300 bg-slate-50 px-4 py-2 text-sm hover:bg-slate-100">Filter</button>
        @if($canManage)
            <a href="{{ route('letters.create') }}" class="ml-auto rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Buat Surat</a>
        @endif
    </form>

    <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr><th class="px-4 py-3">Nomor</th><th class="px-4 py-3">Jenis</th><th class="px-4 py-3">Pegawai</th><th class="px-4 py-3">Perihal</th><th class="px-4 py-3">Tanggal</th><th class="px-4 py-3">Status</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($letters as $letter)
                <tr>
                    <td class="px-4 py-3 font-mono text-xs"><a href="{{ route('letters.show', $letter) }}" class="text-emerald-700 hover:underline">{{ $letter->number }}</a></td>
                    <td class="px-4 py-3">{{ ucfirst($letter->letter_type) }}</td>
                    <td class="px-4 py-3">{{ $letter->employee?->full_name }}<div class="text-xs text-slate-500">{{ $letter->employee?->nip }}</div></td>
                    <td class="px-4 py-3">{{ $letter->subject }}</td>
                    <td class="px-4 py-3 text-xs">{{ \App\Support\Format::date($letter->letter_date) }}</td>
                    <td class="px-4 py-3"><span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs">{{ str_replace('_', ' ', $letter->status) }}</span></td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">Belum ada surat.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $letters->links() }}</div>
@endsection
