<?php
namespace App\Mail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
class RegistrationTokenMail extends Mailable {
    use Queueable, SerializesModels;
    public function __construct(public string $url) {}
    public function build() { return $this->subject('Confirme seu cadastro - Torrent Translate')->view('mail.registration-token'); }
}
