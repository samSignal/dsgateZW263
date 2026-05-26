<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdmissionsExpiryWarningMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $titleText;
    public string $bodyText;

    public function __construct(string $titleText, string $bodyText)
    {
        $this->titleText = $titleText;
        $this->bodyText = $bodyText;
    }

    public function build()
    {
        return $this->subject($this->titleText)->view('emails.admissions_expiry_warning');
    }
}

