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
        return $this->from('team3@back.end', 'Facebook-Clone-team3')
            ->subject('Mail-Verification')
            ->markdown('mails.exmpl')
            ->with([
                'name' => 'Hello Team3',
                'link' => 'https://mailtrap.io/inboxes'
            ]);
    }
}
