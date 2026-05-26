<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdmissionsMagicLinkMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $titleText;
    public string $bodyText;
    public string $buttonText;
    public string $buttonUrl;
    public string $expiresText;

    public function __construct(string $titleText, string $bodyText, string $buttonText, string $buttonUrl, string $expiresText)
    {
        $this->titleText = $titleText;
        $this->bodyText = $bodyText;
        $this->buttonText = $buttonText;
        $this->buttonUrl = $buttonUrl;
        $this->expiresText = $expiresText;
    }

    public function build()
    {
        return $this->subject($this->titleText)->view('emails.admissions_magic_link');
    }
}

