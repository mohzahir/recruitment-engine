<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ColdOutreachMail extends Mailable
{
    use Queueable, SerializesModels;

    public $subject;
    public $bodyHtml;
    public $attachmentPath;
    public $attachmentName;

    public function __construct($subject, $bodyHtml, $attachmentPath = null, $attachmentName = null)
    {
        $this->subject = $subject;
        $this->bodyHtml = $bodyHtml;
        $this->attachmentPath = $attachmentPath;
        $this->attachmentName = $attachmentName;
    }

    public function build()
    {
        $mail = $this->subject($this->subject)->html($this->bodyHtml);

        // إضافة المرفق إذا كان موجوداً
        if ($this->attachmentPath) {
            $mail->attach(storage_path('app/public/' . $this->attachmentPath), [
                'as' => $this->attachmentName ?? 'Attachment',
            ]);
        }

        return $mail;
    }
}