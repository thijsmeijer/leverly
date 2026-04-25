# Authorization

Leverly keeps authorization close to Laravel's native policy and gate system. API code should authorize commands and queries before returning user-owned data, and UI code should treat forbidden responses as authorization state rather than a generic server error.

## API

Use a policy for every user-facing resource. Resources owned by a single user can opt into the shared ownership helpers:

1. Implement `App\Support\Authorization\UserOwnedResource`.
2. Use `App\Support\Authorization\HasUserOwnership` on the model.
3. Create the resource policy by extending or composing the behavior from `App\Policies\UserOwnedResourcePolicy`.

The ownership trait expects a `user_id` column by default. Override `ownerKeyName()` on the model if a resource uses a different owner column.

List queries must apply the `ownedBy($user)` scope before transforming or returning records. Detail, update, delete, restore, and force-delete paths should use Laravel policy authorization, such as `$this->authorize('view', $resource)` or `Gate::authorize('view', $resource)`.

Cross-user access should be denied as not found. That keeps resource existence from leaking between accounts while still using Laravel's authorization responses.

## UI

The shared API runtime separates common authorization statuses:

- `401` marks the session as logged out and runs the session-expired handler.
- `403` records the denial in `useAuthorizationStore()` and runs the forbidden handler.
- `404` runs the not-found handler.

Screens can read `authorizationStore.lastDenied` when they need to show a local access-denied state. Actions that intentionally ignore a forbidden response can pass `forbiddenMode: 'silent'`; actions that need caller-owned handling can pass `forbiddenMode: 'throw'`.
