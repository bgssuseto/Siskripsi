<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $subjek }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f1f5f9; font-family: Arial, Helvetica, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f1f5f9; padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background-color:#ffffff; border-radius:16px; overflow:hidden; border:1px solid #e2e8f0;">
                    <tr>
                        <td style="background-color:#1e3a8a; padding:22px 32px;">
                            <p style="margin:0; color:#ffffff; font-size:16px; font-weight:bold;">Universitas Muria Kudus</p>
                            <p style="margin:2px 0 0; color:#c7d2fe; font-size:12px;">Program Studi Teknik Informatika &middot; Sistem Informasi Skripsi</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="height:4px; background-color:{{ $warna }}; line-height:4px; font-size:0;">&nbsp;</td>
                    </tr>
                    <tr>
                        <td style="padding:28px 32px;">
                            <p style="margin:0 0 16px; font-size:14px; color:#1e293b; line-height:1.6;">{{ $sapaan }}</p>

                            @foreach($paragraf as $p)
                                <p style="margin:0 0 14px; font-size:14px; color:#1e293b; line-height:1.7;">{{ $p }}</p>
                            @endforeach

                            @foreach($rincian as $blok)
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:6px 0 14px; border:1px solid #e2e8f0; border-radius:12px; background-color:#f8fafc;">
                                    @foreach($blok as $label => $nilai)
                                        <tr>
                                            <td style="padding:8px 16px; font-size:12px; color:#64748b; width:130px; vertical-align:top;">{{ $label }}</td>
                                            <td style="padding:8px 16px 8px 0; font-size:13px; color:#0f172a; font-weight:bold; vertical-align:top;">{{ $nilai }}</td>
                                        </tr>
                                    @endforeach
                                </table>
                            @endforeach

                            @if($tombolTeks && $tombolUrl)
                                <table role="presentation" cellpadding="0" cellspacing="0" style="margin:18px 0 6px;">
                                    <tr>
                                        <td style="background-color:#4f46e5; border-radius:10px;">
                                            <a href="{{ $tombolUrl }}" style="display:inline-block; padding:11px 20px; font-size:13px; font-weight:bold; color:#ffffff; text-decoration:none;">{{ $tombolTeks }}</a>
                                        </td>
                                    </tr>
                                </table>
                            @endif

                            <p style="margin:22px 0 0; font-size:13px; color:#64748b;">
                                Hormat kami,<br>
                                Koordinator Skripsi/Tugas Akhir<br>
                                Program Studi Teknik Informatika
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:14px 32px; background-color:#f8fafc; border-top:1px solid #e2e8f0;">
                            <p style="margin:0; font-size:11px; color:#94a3b8;">
                                Email ini dikirim otomatis oleh Sistem Informasi Skripsi TI &mdash; mohon tidak membalas email ini.
                                Bila ada data yang keliru, hubungi Koordinator Skripsi.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
