<?php

namespace Database\Seeders;

use App\Models\DocumentTemplate;
use Illuminate\Database\Seeder;

class HrDocumentTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $letterhead = <<<'HTML'
<div style="font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #111;">
  <div style="text-align:center; border-bottom:2px solid #065f46; padding-bottom:8px; margin-bottom:18px;">
    <div style="font-size:16px; font-weight:bold; letter-spacing:1px;">PESANTREN IMAM SYAFI'I</div>
    <div style="font-size:11px;">Contoh alamat lembaga — lengkapi sesuai data resmi (DEMO)</div>
  </div>
HTML;

        $footer = <<<'HTML'
  <div style="margin-top:32px; font-size:10px; color:#555;">
    Dokumen ini dihasilkan oleh Aplikasi Kepegawaian PIS. Data DEMO wajib diberi label DEMO.
  </div>
</div>
HTML;

        $templates = [
            [
                'code' => 'KONTRAK-UMUM',
                'name' => 'Kontrak Kerja (DEMO)',
                'doc_type' => 'kontrak',
                'variables' => [
                    'nomor', 'nama_pegawai', 'nip', 'jabatan', 'unit', 'jenis_kontrak',
                    'tanggal_mulai', 'tanggal_selesai', 'tanggal_probation', 'tanggal_ttd',
                    'kota', 'tanggal_cetak',
                ],
                'body' => $letterhead.'<h3 style="text-align:center; margin:0 0 4px;">KONTRAK KERJA</h3>
  <p style="text-align:center; margin:0 0 18px;">Nomor: {nomor}</p>
  <p>Pada hari ini, kontrak kerja dibuat antara Pesantren Imam Syafi\'i dengan:</p>
  <table style="width:100%; margin:12px 0;">
    <tr><td style="width:150px;">Nama</td><td>: {nama_pegawai}</td></tr>
    <tr><td>NIP</td><td>: {nip}</td></tr>
    <tr><td>Jabatan</td><td>: {jabatan}</td></tr>
    <tr><td>Unit</td><td>: {unit}</td></tr>
  </table>
  <p>Jenis kontrak: <strong>{jenis_kontrak}</strong>, berlaku mulai {tanggal_mulai} sampai {tanggal_selesai}.
  Akhir masa percobaan: {tanggal_probation}. Tanggal penandatanganan: {tanggal_ttd}.</p>
  <p>Ketentuan hak dan kewajiban mengikuti peraturan kepegawaian yang berlaku. Dokumen ini dicetak pada {tanggal_cetak}.</p>
  <table style="width:100%; margin-top:40px;"><tr>
    <td style="text-align:center;">Pegawai<br><br><br><br>{nama_pegawai}</td>
    <td style="text-align:center;">{kota}, {tanggal_cetak}<br>Pimpinan<br><br><br><br>..............................</td>
  </tr></table>'.$footer,
            ],
            [
                'code' => 'SK-UMUM',
                'name' => 'Surat Keputusan (DEMO)',
                'doc_type' => 'sk',
                'variables' => [
                    'nomor', 'judul', 'nama_pegawai', 'nip', 'jabatan', 'unit',
                    'tanggal_sk', 'tanggal_efektif', 'isi', 'kota', 'tanggal_cetak',
                ],
                'body' => $letterhead.'<h3 style="text-align:center; margin:0 0 4px;">SURAT KEPUTUSAN</h3>
  <p style="text-align:center; margin:0 0 2px;">Nomor: {nomor}</p>
  <p style="text-align:center; margin:0 0 18px; font-weight:bold;">{judul}</p>
  <p>Menetapkan pegawai berikut:</p>
  <table style="width:100%; margin:12px 0;">
    <tr><td style="width:150px;">Nama</td><td>: {nama_pegawai}</td></tr>
    <tr><td>NIP</td><td>: {nip}</td></tr>
    <tr><td>Jabatan</td><td>: {jabatan}</td></tr>
    <tr><td>Unit</td><td>: {unit}</td></tr>
  </table>
  <p>Isi keputusan: {isi}</p>
  <p>Ditetapkan pada {tanggal_sk} dan berlaku efektif {tanggal_efektif}.</p>
  <table style="width:100%; margin-top:40px;"><tr>
    <td></td>
    <td style="text-align:center;">{kota}, {tanggal_cetak}<br>Pimpinan<br><br><br><br>..............................</td>
  </tr></table>'.$footer,
            ],
            [
                'code' => 'SURAT-UMUM',
                'name' => 'Surat Resmi (DEMO)',
                'doc_type' => 'surat',
                'variables' => [
                    'nomor', 'perihal', 'nama_pegawai', 'nip', 'jabatan', 'unit',
                    'tanggal_surat', 'isi', 'kota', 'tanggal_cetak',
                ],
                'body' => $letterhead.'<p style="margin:0 0 4px;">Nomor: {nomor}</p>
  <p style="margin:0 0 4px;">Perihal: <strong>{perihal}</strong></p>
  <p style="margin:0 0 18px;">{kota}, {tanggal_surat}</p>
  <p>Kepada Yth.<br>{nama_pegawai}<br>NIP {nip} — {jabatan} ({unit})</p>
  <p>Dengan hormat,</p>
  <p>{isi}</p>
  <p>Demikian surat ini disampaikan untuk diperhatikan dan dilaksanakan sebagaimana mestinya.</p>
  <table style="width:100%; margin-top:40px;"><tr>
    <td></td>
    <td style="text-align:center;">{kota}, {tanggal_cetak}<br>Pimpinan<br><br><br><br>..............................</td>
  </tr></table>'.$footer,
            ],
        ];

        foreach ($templates as $template) {
            DocumentTemplate::updateOrCreate(
                ['code' => $template['code']],
                $template + ['is_active' => true]
            );
        }
    }
}
