<?php

namespace Tests\Feature;

use App\Support\MailBranding;
use Tests\TestCase;

class MailBrandingTest extends TestCase
{
    public function test_mail_logo_uses_a_public_https_url_on_localhost(): void
    {
        config([
            'app.url' => 'http://127.0.0.1:8000',
            'mail.logo_url' => null,
        ]);

        $this->assertStringStartsWith('https://', MailBranding::logoUrl());
        $this->assertStringContainsString('DICNHSLOGO1.png', MailBranding::logoUrl());
    }

    public function test_configured_mail_logo_url_wins(): void
    {
        config(['mail.logo_url' => 'https://example.com/logo.png']);

        $this->assertSame('https://example.com/logo.png', MailBranding::logoUrl());
    }
}
