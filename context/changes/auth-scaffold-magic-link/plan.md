# F-02 Auth scaffold — Magic-link (passwordless) Implementation Plan

## Overview

Build a hand-rolled, passwordless **magic-link authentication primitive**: a user submits an email, receives a single-use link valid for 15 minutes, and clicking it creates an authenticated session. This is foundation slice F-02 — it delivers *only the authentication mechanism* plus a `Role` enum (a type for F-03 to consume) and `auth` middleware wiring. Neighbourhood join (code-gated), `Membership`, role assignment, pending approval, and authorization policies are explicitly **out of scope** — they belong to F-03 / S-01 / S-02.

## Current State Analysis

Fresh Laravel 13.8 scaffold (PHP 8.4), nothing auth-specific built yet:

- `config/auth.php`: default `web` guard (`session` driver) + `eloquent` provider on `App\Models\User`. No changes needed to guard/provider.
- `app/Models/User.php`: uses PHP 8.4 attribute config (`#[Fillable]`, `#[Hidden]`), `casts()` includes `email_verified_at => datetime` and `password => hashed`. We keep the attribute style; `password` stays nullable-in-practice (passwordless) but the column remains.
- `database/migrations/`: only `create_users_table` (which in Laravel 11/12 also creates `password_reset_tokens` + `sessions` inline), `create_cache_table`, `create_jobs_table`. Sessions table exists → session guard works out of the box.
- `routes/web.php`: only `/` → `welcome`. No auth routes.
- `bootstrap/app.php`: minimal — only `trustProxies(at: '*')`. No middleware aliases or redirect config.
- `MAIL_MAILER=log` → in dev, magic-link emails land in `storage/logs/laravel.log` (easy to grab the URL for manual testing).
- No Fortify / no auth package in `composer.json`. No auth controllers/views.
- F-01 tooling is live: PHPStan level 5 (Larastan), Codeception 3 suites, Pint pre-commit, CI gate.

### Key Discoveries:

- **Role is per-Membership, not per-User** — PRD `## Access Control` + roadmap F-03 model the four roles (Założyciel/Właściciel/Gość/Oczekujący) as an attribute of a `Membership` (user × household × neighbourhood). F-02 precedes `Membership`, so F-02 ships the `Role` *enum type* only; assignment lives in F-03/S-02. (`context/foundation/prd.md:148-165`, `context/foundation/roadmap.md:100-112`)
- **Chicken-and-egg with the neighbourhood code** — FR-003's "join with email + code" needs an existing neighbourhood; neighbourhoods first appear in S-01. So F-02's auth layer is email-only; the code-gated join is built *on top* of this mechanism in S-02. (`context/foundation/prd.md:69`, `context/foundation/roadmap.md:96`)
- **Laravel already ships the exact pattern** — the framework's password-reset broker stores a hashed token + `created_at` in `password_reset_tokens` and enforces TTL in code. We mirror that pattern for `magic_links` (hashed token, TTL in code, single-use by row deletion) instead of pulling in Fortify (which has no passwordless flow OOTB and would add unused password paths). (`context/foundation/coding-rules.md:121-127` — anti-dependency stance)
- **`email_verified_at`** — clicking a magic link proves email ownership, so consumption sets `email_verified_at` (satisfies a future `MustVerifyEmail` contract for free). (`app/Models/User.php` casts)
- **Money/visibility/tenancy rules do not apply to this slice** — no financial data, no scoped models. The load-bearing coding-rule here is the auth guardrail (`context/foundation/prd.md:42-43`): unauthenticated users never reach protected data.

## Desired End State

A visitor can authenticate without a password:

1. Visit `/login`, enter an email, submit.
2. Receive a neutral "if that address can sign in, a link is on the way" message (anti-enumeration) regardless of whether the account exists.
3. A magic-link email is dispatched (visible in `storage/logs/laravel.log` in dev).
4. Clicking the link within 15 minutes logs the user in (creating the account on first use), marks the email verified, invalidates the token, and lands them on a placeholder "brak osiedla" dashboard.
5. Clicking an expired or already-used link shows a clear error and a path back to `/login`.
6. Excessive link requests for the same email+IP are throttled.
7. Any guest hitting a protected route is redirected to `/login`.

Verified by: Codeception Acceptance suite (happy + expired + reused + throttled + guard-redirect), a Functional test on token lifecycle, PHPStan level 5 clean, Pint clean.

## What We're NOT Doing

- **No neighbourhood code, no join flow** — email-only auth. Code-gated join is S-02.
- **No `Membership`, `Household`, `Neighbourhood` models** — F-03.
- **No role assignment, no pending-approval pipeline** — only the `Role` *enum type* is defined; nothing assigns or reads it yet.
- **No Policy / Gate classes** — there are no domain models to guard. Only the `auth` middleware (guest → redirect) is wired. Authorization policies arrive in F-03+.
- **No Fortify / Sanctum / Breeze / Jetstream.**
- **No password login, no registration form, no 2FA, no "remember me" UI.** The `password` column stays (framework expects it) but is unused.
- **No multi-neighbourhood context switching (FR-005)** — S-08.
- **No production mail provider** — `MAIL_MAILER=log` is fine for F-02; SMTP/provider config is a deploy concern.
- **No real dashboard** — just an authenticated placeholder page proving the session works.

## Implementation Approach

Mirror Laravel's password-reset broker pattern for a single-use, TTL-bound token, but drive login instead of password change. A thin controller handles three actions (show form, request link, consume link); a Notification sends the email; a named `RateLimiter` guards the request endpoint; the standard `auth` middleware protects a placeholder route. Keep everything inline in the controller per `coding-rules.md` (no premature service layer) — extract a small token helper only if Phase 1 reveals genuine duplication.

## Critical Implementation Details

- **Single-use is server-state, not signature.** A Laravel signed URL gives tamper-proofing + expiry but is *replayable until expiry*. Single-use requires deleting/marking the stored token on consume. We therefore use a random token compared against a hashed stored value (password-reset pattern) and delete the row on successful consume — this is the source of truth for "used", not the URL signature.
- **Anti-enumeration ordering.** The POST handler must return the identical neutral response and identical timing-insensitive flow whether or not the email maps to an existing account. Create-or-find happens server-side; the response never reveals which branch ran.
- **`strict_types` + native typing** on every new `.php` file under `app/`, `database/`, `tests/` (Pint will not add `declare(strict_types=1)` — it is the implementer's responsibility). (`context/foundation/coding-rules.md:9-10`)

## Phase 1: Magic-link backend + token lifecycle

### Overview

The authentication mechanism end-to-end at the HTTP-handler level: issue a token, email it, consume it into a session. No UI yet (tested via Functional + direct requests).

### Changes Required:

#### 1. Magic-links table

**File**: `database/migrations/<timestamp>_create_magic_links_table.php`

**Intent**: Persist single-use login tokens with enough state to enforce 15-minute TTL and one-time use, mirroring `password_reset_tokens`.

**Contract**: Table `magic_links` with columns: `email` (string, indexed — not unique, a user may request again), `token` (string, stores a **hash** of the random token), `created_at` (timestamp, nullable — TTL computed from this). Working `down()` drops the table.

#### 2. Magic-link issuance + consumption logic

**File**: `app/Auth/MagicLink.php` (plain final class, not an Eloquent model)

**Intent**: Centralize the three token operations so the controller stays thin and the logic is unit-testable without HTTP: generate a random token + persist its hash for an email; verify a presented (email, token) pair against the stored hash within TTL; invalidate (delete) on consume. TTL (15 min) is a class constant.

**Contract**: Methods roughly `issueFor(string $email): string` (returns the **plaintext** token to embed in the URL, stores only the hash), `consume(string $email, string $token): bool` (true iff a non-expired matching row exists; deletes all rows for that email on success), and internal expiry check against `created_at + 15min`. Uses `Illuminate\Support\Str::random()` for the token and `hash('sha256', ...)` or `Hash` for storage. No code snippet needed — it follows the password-broker shape.

#### 3. Auth controller (request + verify actions)

**File**: `app/Http/Controllers/Auth/MagicLinkController.php`

**Intent**: Handle the three HTTP entry points. `create()` renders the login form (Phase 2 view). `store()` validates email, applies the named rate limiter, find-or-creates the `User`, issues a token, sends the notification, and returns the neutral confirmation — identical regardless of account existence (anti-enumeration). `verify()` reads email+token from the link, calls `MagicLink::consume()`, and on success: `Auth::login($user)`, set `email_verified_at` if null, `$request->session()->regenerate()`, redirect to dashboard; on failure: redirect to `/login` with a clear error.

**Contract**: Routes (registered in `routes/web.php`, kebab-case):
- `GET /login` → `create` (name `login` — this is the redirect target for the `auth` middleware)
- `POST /login` → `store` (named limiter `magic-link` applied)
- `GET /login/verify` → `verify` (query params `email`, `token`; or a path param — implementer's choice, keep it signing-friendly)

`store()` validates `email` as `required|email`. User creation sets `name` from the email local-part as a placeholder (real name capture is a later slice).

#### 4. Magic-link notification

**File**: `app/Notifications/MagicLinkNotification.php`

**Intent**: Email the user a clickable login URL. Polish UI strings (per `coding-rules.md` — Polish only in user-facing text). In dev (`MAIL_MAILER=log`) the rendered mail lands in the log for manual link extraction.

**Contract**: `Notification` with `via() => ['mail']` and a `toMail()` returning a `MailMessage` whose action URL is the `/login/verify` URL carrying email + plaintext token. The notification receives the verify URL (or the token) via constructor — it does not itself touch `MagicLink` storage.

#### 5. Named rate limiter

**File**: `app/Providers/AppServiceProvider.php` (`boot()`)

**Intent**: Throttle link requests per email+IP to blunt email-bombing on this load-bearing endpoint.

**Contract**: `RateLimiter::for('magic-link', ...)` keyed by `email . '|' . ip`, limit ~5 attempts per 15 minutes. Applied as `throttle:magic-link` middleware on the `POST /login` route. Choose the limit constant in one place.

### Success Criteria:

#### Automated Verification:

- Migration applies cleanly: `docker compose exec app php artisan migrate`
- Functional test for token lifecycle passes: `docker compose exec app vendor/bin/codecept run Functional`
- Static analysis clean: `docker compose exec app vendor/bin/phpstan analyse --memory-limit=512M`
- Code style clean: `docker compose exec app vendor/bin/pint --test`

#### Manual Verification:

- Submitting an email via a direct `POST /login` (e.g. tinker/curl) writes a `magic_links` row and logs a mail containing a `/login/verify` URL.
- Visiting that URL within 15 min creates/logs in the user and the row is deleted.
- Visiting the same URL a second time fails (single-use).

**Implementation Note**: After completing this phase and all automated verification passes, pause for manual confirmation before Phase 2.

---

## Phase 2: Auth UI + middleware + Role enum

### Overview

The user-facing surface and session plumbing: login form, "link sent" confirmation, authenticated placeholder landing, logout, guest→login redirect, and the `Role` enum type for F-03.

### Changes Required:

#### 1. Login + confirmation views

**File**: `resources/views/auth/login.blade.php`, `resources/views/auth/link-sent.blade.php`

**Intent**: Minimal, mobile-first (usable ≥320px per NFR) email form and a neutral post-submit confirmation. Polish UI strings. Reuse the existing Tailwind v4 + Vite setup.

**Contract**: `login.blade.php` posts `email` to `POST /login`, shows validation/flash errors. `link-sent.blade.php` shows the neutral "jeśli ten adres może się zalogować, link jest w drodze" message. No styling framework beyond the bundled Tailwind.

#### 2. Placeholder dashboard

**File**: `resources/views/dashboard.blade.php` + route `GET /dashboard` (name `dashboard`)

**Intent**: An authenticated landing page proving the session works, showing a "brak osiedla" empty state (osiedla arrive in S-01). Includes a logout control.

**Contract**: Route `GET /dashboard` behind `auth` middleware. `/` (welcome) may redirect authenticated users here, or stay — keep the change minimal; the canonical authenticated target is `dashboard`.

#### 3. Logout

**File**: `routes/web.php` + a method on `MagicLinkController` (or an inline closure)

**Intent**: End the session.

**Contract**: `POST /logout` (name `logout`) → `Auth::logout()`, invalidate + regenerate session token, redirect to `/login`. CSRF-protected form.

#### 4. Guest→login redirect wiring

**File**: `bootstrap/app.php` (`withMiddleware`)

**Intent**: Ensure unauthenticated access to protected routes redirects to the named `login` route (PRD: "Niezalogowany użytkownik … przekierowany do strony logowania").

**Contract**: Rely on Laravel's default unauthenticated redirect to the route named `login` (which Phase 1 defines). Add explicit middleware/redirect config in `bootstrap/app.php` only if the default does not resolve to our `login` route.

#### 5. Role enum (type for F-03)

**File**: `app/Enums/Role.php`

**Intent**: Define the four roles as a backed enum so F-03's `Membership` can type a `role` column against it. Nothing in F-02 assigns or reads it — it is a pure type checked into place now.

**Contract**: `enum Role: string` with cases `Founder = 'founder'`, `Owner = 'owner'`, `Guest = 'guest'`, `Pending = 'pending'`. A `label(): string` method returning the Polish display name (Założyciel/Właściciel/Gość/Oczekujący). `strict_types` declared.

### Success Criteria:

#### Automated Verification:

- Static analysis clean: `docker compose exec app vendor/bin/phpstan analyse --memory-limit=512M`
- Code style clean: `docker compose exec app vendor/bin/pint --test`
- App boots / routes resolve: `docker compose exec app php artisan route:list`

#### Manual Verification:

- Full click-through in a browser: `/login` → submit email → grab link from `storage/logs/laravel.log` → click → land on `/dashboard` logged in.
- Layout is usable at 320px width (DevTools responsive mode).
- Logout returns to `/login` and `/dashboard` is no longer reachable (redirects to `/login`).
- Visiting `/dashboard` as a guest redirects to `/login`.

**Implementation Note**: After completing this phase and all automated verification passes, pause for manual confirmation before Phase 3.

---

## Phase 3: Acceptance suite + green gate

### Overview

Lock the load-bearing auth paths behind automated Acceptance tests and prove the full quality gate is green.

### Changes Required:

#### 1. Acceptance suite for magic-link login

**File**: `tests/Acceptance/MagicLinkLoginCest.php`

**Intent**: Cover the user-facing auth scenarios end-to-end (per `coding-rules.md` — Acceptance owns user-facing flows).

**Contract**: One Cest, behaviour-named methods covering:
- `userReceivesLinkAndLogsIn` — request link, follow the verify URL, assert authenticated + landed on dashboard + token row gone.
- `expiredLinkIsRejected` — travel past 15 min (Carbon test time), assert rejection + redirect to login.
- `usedLinkCannotBeReused` — consume once, second visit fails.
- `excessiveRequestsAreThrottled` — exceed the limiter, assert 429/throttle response.
- `guestIsRedirectedFromProtectedRoute` — hit `/dashboard` unauthenticated, assert redirect to `/login`.
- `responseIsNeutralForUnknownEmail` — anti-enumeration: same response for unknown vs known email.

Use `\Helper\Acceptance` with `cleanup: true` (suite config) for per-test DB state.

#### 2. Functional/Unit coverage already in Phase 1

**File**: `tests/Functional/MagicLinkTokenCest.php` (created in Phase 1)

**Intent**: Confirm token issue/consume/expiry/single-use at the domain level without HTTP.

**Contract**: Methods asserting: issued token hash stored (plaintext never persisted), consume succeeds within TTL, fails after TTL, fails on second consume.

### Success Criteria:

#### Automated Verification:

- Full suite passes: `docker compose exec app vendor/bin/codecept run`
- Static analysis clean: `docker compose exec app vendor/bin/phpstan analyse --memory-limit=512M`
- Code style clean: `docker compose exec app vendor/bin/pint --test`

#### Manual Verification:

- CI run on the PR is green (phpstan + codecept jobs from F-01 workflow).
- No regression in the `/` welcome route.

**Implementation Note**: Final phase — after green, the slice is ready for `/10x-impl-review`.

---

## Testing Strategy

### Unit / Functional Tests:

- Token lifecycle (`MagicLink`): hash-only storage, TTL boundary, single-use deletion.

### Acceptance Tests:

- Happy path, expired link, reused link, throttled requests, guest redirect, neutral response for unknown email.

### Manual Testing Steps:

1. `docker compose up -d`, `php artisan migrate`.
2. Open `/login`, submit your email.
3. `tail storage/logs/laravel.log`, copy the verify URL.
4. Open it within 15 min → confirm landing on `/dashboard` logged in.
5. Reopen the same URL → confirm single-use rejection.
6. Wait >15 min (or edit `created_at`) and retry a fresh link past TTL → confirm expiry rejection.
7. Submit the email rapidly >5× → confirm throttling.
8. Resize to 320px → confirm layout usable.

## Performance Considerations

Negligible — auth is low-QPS (PRD `target_scale.qps: low`). The `magic_links.email` index keeps lookups cheap. No N+1 surface.

## Migration Notes

Single additive migration (`create_magic_links_table`); no existing data to migrate. `down()` drops the table cleanly.

## References

- PRD: `context/foundation/prd.md` — FR-003, FR-004, `## Access Control`, NFR (auth guardrail)
- Roadmap: `context/foundation/roadmap.md:86-98` (F-02), `:100-112` (F-03 boundary)
- Coding rules: `context/foundation/coding-rules.md` — strict types, anti-dependency, anti-service-layer, Codeception suites
- Tech stack: `context/foundation/tech-stack.md` — Fortify mention (superseded here by hand-rolled decision), `has_auth: true`
- Pattern reference: Laravel `password_reset_tokens` broker (single-use + TTL token shape)

## Progress

> Convention: `- [ ]` pending, `- [x]` done. Append ` — <commit sha>` when a step lands. Do not rename step titles. See `references/progress-format.md`.

### Phase 1: Magic-link backend + token lifecycle

#### Automated

- [x] 1.1 Migration applies cleanly: `php artisan migrate` — 8aa4606
- [x] 1.2 Functional test for token lifecycle passes: `codecept run Functional` — 8aa4606
- [x] 1.3 Static analysis clean: `phpstan analyse --memory-limit=512M` — 8aa4606
- [x] 1.4 Code style clean: `pint --test` — 8aa4606

#### Manual

- [x] 1.5 POST /login writes a `magic_links` row and logs a `/login/verify` URL — 8aa4606
- [x] 1.6 Visiting the URL within 15 min creates/logs in the user and deletes the row — 8aa4606
- [x] 1.7 Second visit to the same URL fails (single-use) — 8aa4606

### Phase 2: Auth UI + middleware + Role enum

#### Automated

- [x] 2.1 Static analysis clean: `phpstan analyse --memory-limit=512M` — 0a7dec3
- [x] 2.2 Code style clean: `pint --test` — 0a7dec3
- [x] 2.3 Routes resolve: `php artisan route:list` — 0a7dec3

#### Manual

- [x] 2.4 Full browser click-through: `/login` → email → link from log → `/dashboard` logged in — 0a7dec3
- [x] 2.5 Layout usable at 320px width — 0a7dec3
- [x] 2.6 Logout returns to `/login`; `/dashboard` no longer reachable — 0a7dec3
- [x] 2.7 Guest hitting `/dashboard` is redirected to `/login` — 0a7dec3

### Phase 3: Acceptance suite + green gate

#### Automated

- [x] 3.1 Full suite passes: `codecept run` — 5139269
- [x] 3.2 Static analysis clean: `phpstan analyse --memory-limit=512M` — 5139269
- [x] 3.3 Code style clean: `pint --test` — 5139269

#### Manual

- [x] 3.4 CI run on the PR is green (phpstan + codecept jobs) — 5139269
- [x] 3.5 No regression in the `/` welcome route — 5139269
