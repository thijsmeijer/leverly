<?php

namespace App\Console\Commands;

use App\Mail\LocalMailpitSmokeMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SendLocalMailpitSmokeMailCommand extends Command
{
    protected $signature = 'leverly:mail-smoke {--to=dev@leverly.local : Recipient address for the smoke email}';

    protected $description = 'Send a local smoke email to Mailpit.';

    public function handle(): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->error('This command only runs in local or testing environments.');

            return self::FAILURE;
        }

        $recipient = (string) $this->option('to');
        $token = 'mail-smoke-'.Str::lower((string) Str::ulid());
        $mail = new LocalMailpitSmokeMail($token);

        Mail::to($recipient)->send($mail);

        $this->info('Mailpit smoke email sent.');
        $this->line("Recipient: {$recipient}");
        $this->line("Subject: {$mail->subjectLine()}");
        $this->line('Inbox: http://mail.leverly.local');

        return self::SUCCESS;
    }
}
