<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DatabaseBackupMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        protected string $backupPath,
        protected string $databaseName,
        protected float $sizeMb,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Database Backup — {$this->databaseName} — " . now()->format('Y-m-d'),
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.database-backup',
            with: [
                'databaseName' => $this->databaseName,
                'sizeMb'       => $this->sizeMb,
                'generatedAt'  => now()->format('Y-m-d H:i:s'),
            ],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->backupPath)
                ->as(basename($this->backupPath))
                ->withMime('application/gzip'),
        ];
    }
}
