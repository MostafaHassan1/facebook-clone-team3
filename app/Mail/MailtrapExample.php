<?php
namespace App\Mail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
class MailtrapExample extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct()
    {
        //
    }

public function build()
    {
        return $this->from('mail@example.com', 'Mailtrap')
            ->subject('Mailtrap Confirmation')
            ->markdown('mails.exmpl')
            ->with([
                'name' => 'Hello Team3',
                'link' => 'https://mailtrap.io/inboxes'
            ]);
    }
}
