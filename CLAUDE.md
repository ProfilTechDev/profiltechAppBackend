# Project Architecture (must follow)

This section codifies architecture decisions established for this codebase. New code MUST follow these rules. If a change would violate them, surface the conflict before writing the code.

## Layering

```
Controller ──► Service ──► Event ──► Listener(s)
                  │
                  └─► Job (when async) ──► Service
```

- **Controllers** are thin: validate via FormRequest, delegate to service, return Data DTOs or paginators.
- **Services** own all business logic, state transitions, and event dispatching. They are the only layer allowed to mutate model state (`$model->update([...])`).
- **Jobs** are thin queue adapters: declare `$tries`/`$backoff`/`middleware()`/`failed()`, then delegate to a service method. Never put business logic in a job's `handle()`. Jobs loop back into the service to perform work — the service is the single source of truth whether the call comes from a controller, a queued job, a CLI command, or a manual resend.
- **Listeners** react to domain events — logging, broadcasting, notifications, audit. They never mutate domain state (call services if state must change).

## State transitions

- Each state transition lives in a single named service method (e.g. `markSubmissionSent`, `markSubmissionFailed`).
- The transition method updates the DB and dispatches the corresponding domain event in the same call. Never one without the other.
- Use a private `transitionTo()` helper to avoid duplicating the update + dispatch pattern.
- Events carry both `previousStatus` and current state so listeners can render the transition.

## Events

- Located in `app/Events/{Domain}/{EventName}.php`.
- Use constructor property promotion with `public readonly` fields.
- Lifecycle events (Before/After/Failed) live alongside state-transition events but are conceptually distinct: lifecycle is about an action, transitions are about state.
- Events that need to broadcast implement `ShouldBroadcastNow` (sync) since we are already inside a queued context — never re-queue the broadcast.
- Frontend listens with explicit `broadcastAs()` names. Match what the Nuxt client expects.
- Do NOT dispatch events "just in case future notifications". Add an event only when there is a real consumer (listener, broadcast, audit).
- Optional event fields (e.g. queue context like `attempt`/`willRetry`) should be nullable so non-queue callers can dispatch the same event without inventing fake values.

## Listeners

- Located in `app/Listeners/{Domain}/{EventName}/{Action}.php`. The parent folder names the event; the class name describes what the listener does.
  - Good: `app/Listeners/CustomOrders/SubmissionStatusUpdated/LogPermanentFailure.php`
  - Bad: `app/Listeners/LogSubmissionStatusChange.php` (flat, ambiguous)
- One listener per side-effect. Don't combine logging + notification + audit into one class.
- Listener names describe the action (`RecordAuditLog`, `NotifyOpsOnFailure`), not the event (the folder already says that).
- Listeners are auto-discovered by Laravel — do not register manually.
- Listeners must handle nullable event fields gracefully (omit fields from context when null rather than logging `null`).

## Jobs

- Job `handle()` should be 1–5 lines: pass queue context (`attempts()`, `tries`, etc.) to a service method, let the service do the work.
- `failed()` callback delegates to a service method for the permanent-failure state transition. Do not put logging or business logic here.
- Queue concerns belong on the job: `$tries`, `$backoff`, `WithoutOverlapping` middleware, `failed()`. Nothing else.
- Restart `queue:work` (and Horizon in prod) after changing job/listener/event code — workers cache the loaded classes in memory.

## Custom exceptions

- Located in `app/Exceptions/{Domain}/{Name}Exception.php`.
- Add a custom exception when at least one of these is true:
  - Listeners need to `instanceof` check it
  - You want a `render()` method for a specific HTTP response
  - It carries typed metadata (`public readonly string $providerId`) that callers need
- Do not add custom exceptions purely for log-readability — Laravel logs the class name regardless.

## Error handling

- Default: let exceptions bubble. Laravel's exception handler logs uncaught exceptions and renders consistent responses. Queue workers handle retries automatically.
- Only `try`/`catch` when the catch adds value:
  - Recovery / fallback logic
  - Adding domain context before re-throw (dispatching an event)
  - Mapping to a user-facing response
- Anti-patterns:
  - `catch (Throwable $e) { Log::error(...); throw $e; }` — Laravel already logs.
  - `catch (Throwable $e) { return null; }` — silent failures hide bugs.

## Filtering & search

- Use Spatie Query Builder for list endpoints.
- Custom filter classes live in `app/Http/Filters/{Domain}/{Name}Filter.php` and implement `Spatie\QueryBuilder\Filters\Filter`.
- One filter class per filter key. Each handles its own value-mapping logic.
- Coarse-grained filters (`active`/`completed`, `sent`/`unsent`) are preferred over exposing raw DB values when the UI doesn't need the fine distinction.
- Always call `->appends(request()->query())` on paginators so filter/sort state is preserved in pagination links.

## DTOs

- Use Spatie Data for all request/response shapes. Located in `app/Data/{Domain}/{Name}Data.php`.
- Use `public readonly` properties with constructor promotion.
- Prefer a static `fromModel(Model $model)` factory when mapping from Eloquent — Spatie's automatic mapping doesn't traverse relations via dot-notation.
- Use `DataCollection` and `#[DataCollectionOf]` for typed nested collections.

## Snapshot pattern

- Transaction-time data (order lines, order line products, line attributes) is captured into denormalised "snapshot" tables.
- Snapshots reference the live entity (`product_id`) but contain a copy of the relevant fields at the time of capture.
- Snapshots are immutable after creation — never updated when the source entity changes.

## Frontend integration

- Frontend (Nuxt) is at `app.profiltech.dk`; Laravel API is at `api.profiltech.dk` in prod.
- Sanctum SPA auth is used. Cookies are scoped to `.profiltech.dk` so both subdomains share the session.
- All API responses are JSON. No web views except `/up` health check.

## Pagination

- All list endpoints honour `?per_page=N`, clamped to `[1, 100]` (default 20).
- Pattern: `min(100, max(1, (int) request()->query('per_page', 20)))`.

## Communication & i18n

- All code, comments, DB columns, variable names, and git commits are in English.
- Danish is only used in user-facing UI labels and in conversations with the team.
- Vendor-facing content (e.g. emails to non-Danish providers) is translated via sibling config keys (`label`, `label_en`) — NOT via Laravel's `lang/` files unless we genuinely need three+ languages.

## WooCommerce integration

- Webhook signature is validated via WC native HMAC (`X-WC-Webhook-Signature`) — base64 HMAC-SHA256 of raw body with `WOOCOMMERCE_WEBHOOK_SECRET`. Do not invent custom header schemes.
- WC API consumed via WooCommerce/ subfolders (anti-corruption layer). Domain models stay flat — never named after WC concepts.
- Race-safety: webhook handlers check `wc_modified_at` to skip stale updates; `WithoutOverlapping` middleware per `wc_order_id` on jobs.

## Emails

- HTML emails must be Outlook-safe: table-based layout, inline styles only, no flexbox/grid, no `<head>` styles, `<table cellpadding cellspacing border>` attributes preferred over CSS.
- Always provide both `view` (HTML) and `text` variants.
- Structural labels (Order, Quantity, Thickness) come from a translation array passed via `with: ['t' => ...]`. Attribute labels come from `config/custom_orders.php` with `label_en` siblings.
- Inline Blade `@if` directives don't work when a word character precedes `@`. Use ternary expressions inside `{{ }}` instead: `{{ $cond ? '...' : '' }}`.

## Octane safety

- Reset state between requests — do not append to static properties or class-level caches.
- Use `scoped` instead of `singleton` for request-scoped services.
- Restart Octane (`php artisan octane:reload`) after route or middleware changes — routes are cached in memory.

---

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.5
- laravel/fortify (FORTIFY) - v1
- laravel/framework (LARAVEL) - v13
- laravel/horizon (HORIZON) - v5
- laravel/octane (OCTANE) - v2
- laravel/prompts (PROMPTS) - v0
- laravel/reverb (REVERB) - v1
- laravel/sanctum (SANCTUM) - v4
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.
- To check environment variables, read the `.env` file directly.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== octane/core rules ===

# Octane

- Octane boots the application once and reuses it across requests, so singletons persist between requests.
- The Laravel container's `scoped` method may be used as a safe alternative to `singleton`.
- Never inject the container, request, or config repository into a singleton's constructor; use a resolver closure or `bind()` instead:

```php
// Bad
$this->app->singleton(Service::class, fn (Application $app) => new Service($app['request']));

// Good
$this->app->singleton(Service::class, fn () => new Service(fn () => request()));
```

- Never append to static properties, as they accumulate in memory across requests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- The `{name}` argument should not include the test suite directory. Use `php artisan make:test --pest SomeFeatureTest` instead of `php artisan make:test --pest Feature/SomeFeatureTest`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

</laravel-boost-guidelines>
