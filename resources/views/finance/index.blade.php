@extends('layouts.app', ['title' => 'Keuangan Pribadi', 'heading' => 'Reimbursement & Pinjaman', 'subheading' => 'Pengajuan klaim biaya dan pinjaman kasbon pegawai'])

@section('content')
    <div class="grid gap-6 lg:grid-cols-2">
        <!-- Reimbursement Form -->
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-base font-semibold text-slate-900">Ajukan Reimbursement</h3>
            <form method="POST" action="{{ route('finance.reimbursement.store') }}" enctype="multipart/form-data" class="mt-4 grid gap-3">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-slate-700">Judul / Keperluan</label>
                    <input type="text" name="title" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-700">Nominal (IDR)</label>
                        <input type="number" name="amount" min="1000" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700">Tanggal Pengeluaran</label>
                        <input type="date" name="expense_date" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700">Keterangan / Catatan</label>
                    <textarea name="description" rows="2" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700">Lampiran (Nota/Kwitansi PDF/Gambar)</label>
                    <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png" class="mt-1 w-full text-xs text-slate-500 file:mr-4 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-emerald-700 hover:file:bg-emerald-100">
                </div>
                <button class="mt-2 rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Kirim Reimbursement</button>
            </form>
        </div>

        <!-- Loan Form -->
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-base font-semibold text-slate-900">Ajukan Pinjaman / Kasbon</h3>
            <form method="POST" action="{{ route('finance.loan.store') }}" class="mt-4 grid gap-3">
                @csrf
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-700">Jumlah Pinjaman (IDR)</label>
                        <input type="number" name="amount" min="10000" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700">Tenor (Bulan)</label>
                        <input type="number" name="tenor_months" min="1" max="24" value="1" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700">Alasan Peminjaman</label>
                    <textarea name="reason" rows="4" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
                </div>
                <button class="mt-2 rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Kirim Pinjaman</button>
            </form>
        </div>
    </div>

    <!-- Tables -->
    <div class="mt-8 grid gap-6">
        <!-- Reimbursements List -->
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-base font-semibold text-slate-900">Riwayat Reimbursement</h3>
            <div class="mt-3 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                        <tr><th class="px-3 py-2">Pegawai</th><th class="px-3 py-2">Judul</th><th class="px-3 py-2">Nominal</th><th class="px-3 py-2">Tanggal</th><th class="px-3 py-2">Status</th><th class="px-3 py-2">Aksi</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($reimbursements as $item)
                            <tr>
                                <td class="px-3 py-2">{{ $item->employee?->full_name }}</td>
                                <td class="px-3 py-2 font-medium">{{ $item->title }}</td>
                                <td class="px-3 py-2">Rp {{ number_format($item->amount, 0, ',', '.') }}</td>
                                <td class="px-3 py-2 text-xs">{{ \App\Support\Format::date($item->expense_date) }}</td>
                                <td class="px-3 py-2 text-xs"><span class="rounded-full px-2 py-0.5 font-medium {{ $item->status === 'disetujui' ? 'bg-emerald-100 text-emerald-800' : ($item->status === 'ditolak' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800') }}">{{ ucfirst(str_replace('_', ' ', $item->status)) }}</span></td>
                                <td class="px-3 py-2 text-xs">
                                    @if(($item->status === 'menunggu_atasan' && auth()->user()->hasPermission('reimbursement.approve.supervisor')) || ($item->status === 'menunggu_hrd' && auth()->user()->hasPermission('reimbursement.approve.hrd')))
                                        <form method="POST" action="{{ route('finance.reimbursement.approve', $item) }}" class="inline">
                                            @csrf
                                            <button class="text-emerald-700 hover:underline font-semibold">Setujui</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-3 py-4 text-center text-slate-500">Belum ada pengajuan reimbursement.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $reimbursements->links() }}</div>
        </div>

        <!-- Loans List -->
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-base font-semibold text-slate-900">Riwayat Pinjaman / Kasbon</h3>
            <div class="mt-3 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                        <tr><th class="px-3 py-2">Pegawai</th><th class="px-3 py-2">Jumlah</th><th class="px-3 py-2">Tenor / Cicilan</th><th class="px-3 py-2">Alasan</th><th class="px-3 py-2">Status</th><th class="px-3 py-2">Aksi</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($loans as $loan)
                            <tr>
                                <td class="px-3 py-2">{{ $loan->employee?->full_name }}</td>
                                <td class="px-3 py-2 font-medium">Rp {{ number_format($loan->amount, 0, ',', '.') }}</td>
                                <td class="px-3 py-2 text-xs">{{ $loan->tenor_months }} bln (Rp {{ number_format($loan->monthly_installment, 0, ',', '.') }}/bln)</td>
                                <td class="px-3 py-2 text-xs max-w-xs truncate">{{ $loan->reason }}</td>
                                <td class="px-3 py-2 text-xs"><span class="rounded-full px-2 py-0.5 font-medium {{ $loan->status === 'disetujui' ? 'bg-emerald-100 text-emerald-800' : ($loan->status === 'ditolak' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800') }}">{{ ucfirst(str_replace('_', ' ', $loan->status)) }}</span></td>
                                <td class="px-3 py-2 text-xs">
                                    @if(($loan->status === 'menunggu_atasan' && auth()->user()->hasPermission('loan.approve.supervisor')) || ($loan->status === 'menunggu_hrd' && auth()->user()->hasPermission('loan.approve.hrd')))
                                        <form method="POST" action="{{ route('finance.loan.approve', $loan) }}" class="inline">
                                            @csrf
                                            <button class="text-emerald-700 hover:underline font-semibold">Setujui</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-3 py-4 text-center text-slate-500">Belum ada pengajuan pinjaman.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $loans->links() }}</div>
        </div>
    </div>
@endsection
