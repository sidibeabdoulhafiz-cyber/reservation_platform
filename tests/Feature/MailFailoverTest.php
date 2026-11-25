<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MailFailoverTest extends TestCase
{
    public function test_failover_lws_puis_brevo(): void
    {
        config([
            'mail.mailers.lws.host' => 'mail.exemple.com',
            'mail.mailers.lws.username' => 'contact@exemple.com',
            'mail.mailers.lws.password' => 'x',
        ]);

        $this->assertSame(['lws', 'smtp'], config('mail.mailers.failover.mailers'));
        $transport = Mail::mailer('failover')->getSymfonyTransport();
        $this->assertStringStartsWith('failover(', (string) $transport);
        $this->assertStringContainsString('mail.exemple.com', (string) $transport);
    }
}
