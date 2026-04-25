<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Observability;

use Laravel\Telescope\Watchers;
use Tests\TestCase;

final class TelescopeConfigurationTest extends TestCase
{
    public function test_it_keeps_only_the_foundational_local_observability_watchers_enabled(): void
    {
        $watchers = array_keys(config('telescope.watchers'));

        $this->assertContains(Watchers\CommandWatcher::class, $watchers);
        $this->assertContains(Watchers\ExceptionWatcher::class, $watchers);
        $this->assertContains(Watchers\JobWatcher::class, $watchers);
        $this->assertContains(Watchers\LogWatcher::class, $watchers);
        $this->assertContains(Watchers\QueryWatcher::class, $watchers);
        $this->assertContains(Watchers\RequestWatcher::class, $watchers);
        $this->assertContains(Watchers\ScheduleWatcher::class, $watchers);

        $this->assertNotContains(Watchers\DumpWatcher::class, $watchers);
        $this->assertNotContains(Watchers\ModelWatcher::class, $watchers);
        $this->assertNotContains(Watchers\ViewWatcher::class, $watchers);
    }

    public function test_it_stays_opt_in_outside_local_and_uses_the_application_database_by_default(): void
    {
        $this->assertFalse((bool) config('telescope.enabled'));
        $this->assertSame(config('database.default'), config('telescope.storage.database.connection'));
        $this->assertSame(['OPTIONS'], config('telescope.watchers.'.Watchers\RequestWatcher::class.'.ignore_http_methods'));
        $this->assertSame('error', config('telescope.watchers.'.Watchers\LogWatcher::class.'.level'));
    }
}
