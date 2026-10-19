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

    public function __construct(
        public Dosen $dosen,
        public string $jenisUndangan,
        public string $namaPeriode,
        public int $totalUji,
        public string $pdfContent,
        public string $pdfFileName,
    ) {
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
            Attachment::fromData(fn () => $this->pdfContent, $this->pdfFileName)
                ->withMime('application/pdf'),
        ];
    }
}
