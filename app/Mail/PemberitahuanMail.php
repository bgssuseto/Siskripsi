<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email pemberitahuan umum (verifikasi berkas, jadwal ujian, pengingat H-1).
 * Tampilan: resources/views/emails/pemberitahuan.blade.php.
 */
class PemberitahuanMail extends Mailable
{
    use Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 120;

    /**
     * @param array<int, string>          $paragraf  isi utama (teks biasa)
     * @param array<int, array<string,string>> $rincian  tabel ringkas: tiap item [label => nilai]
     */
    public function __construct(
        public string $subjek,
        public string $sapaan,
        public array $paragraf,
        public array $rincian = [],
        public ?string $tombolTeks = null,
        public ?string $tombolUrl = null,
        public string $warna = '#1e3a8a',
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjek);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.pemberitahuan');
    }
}
