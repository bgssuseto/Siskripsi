<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Undangan Dewan Penguji</title>
</head>
<body style="margin:0; padding:0; background-color:#f1f5f9; font-family: Arial, Helvetica, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f1f5f9; padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius:16px; overflow:hidden; border:1px solid #e2e8f0;">
                    <tr>
                        <td style="background-color:#1e3a8a; padding:24px 32px;">
                            <p style="margin:0; color:#ffffff; font-size:16px; font-weight:bold;">Universitas Muria Kudus</p>
                            <p style="margin:2px 0 0; color:#c7d2fe; font-size:12px;">Program Studi Teknik Informatika</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 16px; font-size:14px; color:#1e293b; line-height:1.6;">
                                Kepada Yth.<br>
                                Bapak/Ibu <strong>{{ $dosen->nama_dosen }}</strong><br>
                                Dosen Program Studi Teknik Informatika<br>
                                Universitas Muria Kudus
                            </p>

                            <p style="margin:0 0 16px; font-size:14px; color:#1e293b; line-height:1.7; text-align:justify;">
                                Dengan hormat, sehubungan dengan akan dilaksanakannya kegiatan
                                <strong>{{ $jenisUndangan === 'sempro' ? 'Seminar Proposal' : 'Sidang Skripsi' }}</strong>
                                mahasiswa Program Studi Teknik Informatika, Fakultas Teknik, Universitas Muria Kudus pada
                                <strong>{{ strtoupper($namaPeriode) }}</strong>, kami mengundang Bapak/Ibu untuk berkenan
                                hadir sebagai Dewan Penguji bagi <strong>{{ $totalUji }} mahasiswa</strong> sesuai jadwal
                                yang telah kami susun sebagaimana terlampir pada surat undangan resmi (PDF) di email ini.
                            </p>

                            <p style="margin:0 0 16px; font-size:14px; color:#1e293b; line-height:1.7;">
                                Mohon Bapak/Ibu berkenan mencermati kembali detail jadwal pada lampiran. Apabila terdapat
                                kesalahan data atau jadwal yang bentrok, mohon segera menghubungi Koordinator Skripsi
                                agar dapat segera ditindaklanjuti.
                            </p>

                            <p style="margin:0 0 4px; font-size:14px; color:#1e293b; line-height:1.7;">
                                Atas perhatian dan kesediaan Bapak/Ibu, kami ucapkan terima kasih.
                            </p>

                            <p style="margin:24px 0 0; font-size:13px; color:#64748b;">
                                Hormat kami,<br>
                                Koordinator Skripsi/Tugas Akhir<br>
                                Program Studi Teknik Informatika
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 32px; background-color:#f8fafc; border-top:1px solid #e2e8f0;">
                            <p style="margin:0; font-size:11px; color:#94a3b8;">
                                Email ini dikirim otomatis oleh Sistem Informasi Skripsi TI &mdash; Program Studi Teknik Informatika,
                                Universitas Muria Kudus. Surat undangan resmi terlampir dalam format PDF.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
