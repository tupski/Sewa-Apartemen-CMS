<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ForceHttpsTest extends TestCase
{
    public function test_generated_urls_are_https_when_request_arrives_over_https(): void
    {
        $this->withServerVariables([
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_X_FORWARDED_FOR' => '127.0.0.1',
        ])->get('/up')->assertOk();

        $this->assertStringStartsWith('https://', url('/apartments'));
        $this->assertStringStartsWith('https://', URL::asset('storage/settings/favicon.jpg'));
        $this->assertStringStartsWith('https://', route('properties.public.index'));
    }
}
