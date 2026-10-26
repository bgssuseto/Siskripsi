<?php

namespace App\Mail;

use App\Models\Dosen;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UndanganDosenMail extends Mailable
{
    use Queueable, SerializesModels;

    /** Dicoba ulang bila server email sedang gangguan. */
    public int $tries = 3;
    public int $backoff = 120;

    /** PDF disimpan base64 supaya aman diserialisasi ke antrian (payload JSON). */
    public string $pdfBase64;

    public function __construct(
        public Dosen $dosen,
        public string $jenisUndangan,
        public string $namaPeriode,
        public int $totalUji,
        string $pdfContent,
        public string $pdfFileName,
    ) {
        $this->pdfBase64 = base64_encode($pdfContent);
    }

    public function envelope(): Envelope
    {
        $jenisLabel = $this->jenisUndangan === 'sempro' ? 'Seminar Proposal' : 'Sidang Skripsi';

        return new Envelope(
            subject: "Undangan Menjadi Dewan Penguji {$jenisLabel} - {$this->namaPeriode}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.undangan-dosen',
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => base64_decode($this->pdfBase64), $this->pdfFileName)
                ->withMime('application/pdf'),
        ];
    }
}
