<?php

namespace App\Mail;

use App\Models\Domain;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DomainWarningMail extends Mailable
{
    use Queueable, SerializesModels;

    public $domain;
    public $issues;

    public function __construct(Domain $domain, array $issues)
    {
        $this->domain = $domain;
        $this->issues = $issues;
    }

    public function build()
    {
        return $this->subject('⚠️ Spectora Monitor Warnung: ' . $this->domain->url)
                    ->view('emails.domain_warning');
    }
}
