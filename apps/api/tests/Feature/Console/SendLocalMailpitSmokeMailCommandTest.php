<?php

namespace Tests\Feature\Console;

use App\Mail\LocalMailpitSmokeMail;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendLocalMailpitSmokeMailCommandTest extends TestCase
{
    public function test_it_sends_a_mailpit_smoke_email_to_the_requested_recipient(): void
    {
        Mail::fake();

        $this->artisan('leverly:mail-smoke', ['--to' => 'athlete@example.test'])
            ->assertExitCode(0);

        Mail::assertSent(
            LocalMailpitSmokeMail::class,
            fn (LocalMailpitSmokeMail $mail): bool => $mail->hasTo('athlete@example.test')
                && str_starts_with($mail->subjectLine(), 'Leverly Mailpit smoke test mail-smoke-'),
        );
    }
}
