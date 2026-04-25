# Sensitive Logging

Leverly treats training notes, injury text, pain notes, and future AI conversation content as sensitive by default.

## Correlation IDs

Every API request receives a server-owned `X-Correlation-ID` response header. API error responses also include the same value at `meta.correlation_id` so support can connect a user-visible failure to server logs without exposing request payloads.

Client-supplied correlation IDs are not reused as the server ID. They may be retained only as hidden diagnostic context.

## Log Redaction

Application log channels run through the sensitive log sanitizer. Context keys containing note, injury, medical, prompt, response, conversation, or pain-note wording are written as `[redacted]`.

Do not place raw note or AI content in log messages. If a log message accidentally includes a sensitive context value, the logging processor attempts to replace that value before the record is written, but code should still log stable identifiers and counts instead of raw payload text.

Preferred log context:

```php
Log::warning('Workout import failed.', [
    'user_id' => $user->getKey(),
    'workout_session_id' => $sessionId,
    'correlation_id' => app(\App\Support\Correlation\CorrelationStore::class)->currentCorrelationId(),
]);
```

Avoid:

```php
Log::warning('Workout import failed.', [
    'session_notes' => $request->input('session_notes'),
    'injury_notes' => $request->input('injury_notes'),
]);
```

## Error Responses

API exception responses must not echo request payloads. Server errors use a generic message and correlation metadata. Validation errors may include field names and validation messages, but should not include raw sensitive submitted values.
