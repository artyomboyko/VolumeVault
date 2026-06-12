<?php

namespace Tests\Unit;

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_baseline_headers_are_always_set(): void
    {
        $response = $this->dispatch(Request::create('http://volumevault.test/', 'GET'));

        $this->assertSame('SAMEORIGIN', $response->headers->get('X-Frame-Options'));
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertSame('same-origin', $response->headers->get('Referrer-Policy'));
    }

    public function test_hsts_is_omitted_over_plain_http(): void
    {
        $response = $this->dispatch(Request::create('http://volumevault.test/', 'GET'));

        $this->assertFalse($response->headers->has('Strict-Transport-Security'));
    }

    public function test_hsts_is_sent_over_https(): void
    {
        $response = $this->dispatch(Request::create('https://volumevault.test/', 'GET'));

        $this->assertStringContainsString('max-age=', (string) $response->headers->get('Strict-Transport-Security'));
        $this->assertStringContainsString('includeSubDomains', (string) $response->headers->get('Strict-Transport-Security'));
    }

    private function dispatch(Request $request): Response
    {
        return (new SecurityHeaders)->handle($request, fn () => new Response('ok'));
    }
}
