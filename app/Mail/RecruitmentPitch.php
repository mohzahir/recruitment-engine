<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Lead;
use App\Models\Campaign;

class RecruitmentPitch extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $lead;
    public $campaign;
    public $parsedBody;
    public $parsedSubject;

    public function __construct(Lead $lead, Campaign $campaign)
    {
        $this->lead = $lead;
        $this->campaign = $campaign;

        // 1. Parse the Subject Line
        $this->parsedSubject = $this->parseTemplate($campaign->subject);

        // 2. Parse the Email Body
        $this->parsedBody = $this->parseTemplate($campaign->body);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->parsedSubject,
        );
    }

    public function content(): Content
    {
        // We use view()->html() so you can write HTML directly in your database templates
        return new Content(
            htmlString: $this->parsedBody,
        );
    }

    /**
     * This function replaces placeholders like {{company}} with real data
     */
    private function parseTemplate($text)
    {
        $replacements = [
            '{{company}}' => $this->lead->company,
            '{{job_title}}' => $this->lead->job_title,
            '{{location}}' => $this->lead->location ?? 'your area',
            '{{publisher_name}}' => explode(' ', $this->lead->publisher_name)[0] ?? 'there', // Just their first name
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $text);
    }
}