<?php

namespace Tests\Feature\Console;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ScribeOpenApiGenerationTest extends TestCase
{
    public function test_scribe_openapi_generation_is_available_and_configured(): void
    {
        $this->assertArrayHasKey('scribe:generate', Artisan::all());
        $this->assertArrayHasKey('leverly:openapi:sync-scribe', Artisan::all());
        $this->assertTrue(config('scribe.openapi.enabled'));
        $this->assertFalse(config('scribe.postman.enabled'));
        $this->assertFalse(config('scribe.laravel.add_routes'));
    }
}
