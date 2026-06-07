# F-02 Auth scaffold — Magic-link — Plan Brief

> Full plan: `context/changes/auth-scaffold-magic-link/plan.md`

## What & Why

Build passwordless **magic-link authentication**: email → single-use link (15 min) → logged-in session. It's the most load-bearing foundation in the roadmap — every user-facing slice sits behind it, and the PRD guardrail "żaden niezaproszony użytkownik nie widzi danych" starts here. We hand-roll it (Laravel signed/token pattern) rather than pull in Fortify, which ships no passwordless flow.

## Starting Point

Fresh Laravel 13.8 scaffold: default `web` session guard + `User` model (PHP 8.4 attribute style), `MAIL_MAILER=log`, only `users/cache/jobs` migrations, `routes/web.php` is just `welcome`. No auth package, no auth UI. F-01 tooling (PHPStan L5, Codeception, Pint, CI) is live.

## Desired End State

A visitor enters an email at `/login`, gets a neutral confirmation, receives a link (in dev: `storage/logs/laravel.log`), and clicking it within 15 minutes creates-or-finds their account, marks the email verified, opens a session, and lands on a placeholder "brak osiedla" dashboard. Expired/reused links and excessive requests are rejected; guests on protected routes redirect to `/login`.

## Key Decisions Made

| Decision | Choice | Why (1 sentence) | Source |
| --- | --- | --- | --- |
| Auth implementation | Hand-rolled magic-link (token + TTL, password-reset pattern) | Fortify has no passwordless OOTB; coding-rules favour zero unneeded deps | Plan |
| F-02 vs F-03 boundary | Mechanism + `Role` enum *type* only | Role/pending are per-`Membership`, which is F-03; avoids dead/global role columns | Plan |
| First sign-in | Email-only → link → logged in | Neighbourhoods/codes don't exist until S-01/S-02 (chicken-and-egg) | Plan |
| Registration | Open magic-link registration (create on consume) | First user must exist before any seed; data is still gated by Membership later | Plan |
| Link lifecycle | 15 min TTL + single-use (row deleted on consume) | Standard magic-link UX, replay-safe; signature alone isn't single-use | Plan |
| Abuse control | Named RateLimiter per email+IP (~5/15 min) | Targeted protection on a load-bearing endpoint | Plan |
| Authorization scope | `auth` middleware + placeholder landing only; no Policy/Gate | No domain models to guard yet; policies arrive in F-03 | Plan |
| Testing | Acceptance (happy/expired/reused/throttled/guard) + Functional on token | Locks the load-bearing auth paths per coding-rules | Plan |

## Scope

**In scope:** magic-link issue/consume, `magic_links` migration, auth controller + routes, email notification, rate limiter, login/confirmation/dashboard views, logout, guest→login redirect, `Role` enum, tests.

**Out of scope:** neighbourhood code & join flow (S-02), `Membership`/`Household`/`Neighbourhood` (F-03), role assignment & pending pipeline, Policy/Gate classes, Fortify/Sanctum/Breeze, password login, FR-005 context switcher, production mail provider, a real dashboard.

## Architecture / Approach

Mirror Laravel's password-reset broker: a `MagicLink` helper issues a random token (stores only its hash + `created_at`) and consumes it (verify within 15 min, then delete = single-use). A thin `MagicLinkController` (no service layer, per coding-rules) handles show-form / request-link / verify; a `Notification` emails the verify URL; a named `RateLimiter` guards the POST; standard `auth` middleware protects a placeholder `/dashboard`. `Role` enum is checked in as a type for F-03.

## Phases at a Glance

| Phase | What it delivers | Key risk |
| --- | --- | --- |
| 1. Backend + token lifecycle | Migration, issue/consume logic, controller, notification, limiter | Single-use correctness (server-state, not URL signature) |
| 2. UI + middleware + enum | Login/confirmation/dashboard views, logout, guest redirect, `Role` enum | Mobile-first ≥320px; redirect resolves to named `login` route |
| 3. Acceptance + green gate | Full Codeception suite + PHPStan/Pint/CI green | Carbon time-travel for expiry test; throttle assertion |

**Prerequisites:** F-01 (done). Docker stack up, `php artisan migrate` runnable.
**Estimated effort:** ~2–3 sessions across 3 phases.

## Open Risks & Assumptions

- Assumes Laravel 11/12 `create_users_table` already created `password_reset_tokens` + `sessions` inline (session guard works). Verify on first migrate.
- `tech-stack.md` says "magic-link in Fortify" — this plan supersedes that with a hand-rolled approach; worth noting in review so the hand-off isn't read as contradicted.
- Open registration means empty accounts can be created without invitation — harmless because all data access is gated by `Membership` (F-03), but worth a conscious nod at review.

## Success Criteria (Summary)

- A user can log in entirely without a password via an emailed link, and lands authenticated.
- Expired links, reused links, and request floods are all rejected; guests can't reach protected routes.
- Full quality gate (PHPStan L5, Pint, Codeception suite) is green locally and in CI.
