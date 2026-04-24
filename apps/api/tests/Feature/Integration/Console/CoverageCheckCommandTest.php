<?php

namespace Tests\Feature\Integration\Console;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class CoverageCheckCommandTest extends TestCase
{
    public function test_it_passes_when_statement_coverage_meets_the_threshold(): void
    {
        $exitCode = Artisan::call('coverage:check', [
            'report' => base_path('tests/Fixtures/Coverage/clover.xml'),
            '--min' => 80,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('80.00%', Artisan::output());
    }

    public function test_it_fails_when_statement_coverage_is_below_the_threshold(): void
    {
        $exitCode = Artisan::call('coverage:check', [
            'report' => base_path('tests/Fixtures/Coverage/clover.xml'),
            '--min' => 81,
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('below required 81.00%', Artisan::output());
    }
}
