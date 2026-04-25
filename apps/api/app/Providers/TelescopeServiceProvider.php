<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    /**
     * @var list<string>
     */
    private const array SENSITIVE_REQUEST_PARAMETERS = [
        '_token',
        'access_token',
        'ai_conversation',
        'api_token',
        'conversation',
        'injury_note',
        'injury_notes',
        'medical_note',
        'notes',
        'pain_note',
        'pain_notes',
        'password',
        'password_confirmation',
        'prompt',
        'raw_instruction',
        'refresh_token',
        'response',
        'session_note',
        'session_notes',
        'token',
        'workout_note',
        'workout_notes',
    ];

    /**
     * @var list<string>
     */
    private const array SENSITIVE_REQUEST_HEADERS = [
        'authorization',
        'cookie',
        'x-csrf-token',
        'x-xsrf-token',
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->hideSensitiveRequestDetails();
        $isLocal = $this->app->environment('local');

        Telescope::filter(static function (IncomingEntry $entry) use ($isLocal): bool {
            if (! config('telescope.enabled', false)) {
                return false;
            }

            return $isLocal
                || $entry->isReportableException()
                || $entry->isFailedRequest()
                || $entry->isFailedJob()
                || $entry->isScheduledTask()
                || $entry->hasMonitoredTag();
        });
    }

    /**
     * Prevent sensitive request details from being logged by Telescope.
     */
    protected function hideSensitiveRequestDetails(): void
    {
        Telescope::hideRequestParameters(self::SENSITIVE_REQUEST_PARAMETERS);
        Telescope::hideRequestHeaders(self::SENSITIVE_REQUEST_HEADERS);
    }

    /**
     * Register the Telescope gate.
     *
     * This gate determines who can access Telescope in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewTelescope', static fn (?User $user = null): bool => app()->environment('local'));
    }
}
