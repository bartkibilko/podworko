<!-- IMPL-REVIEW-REPORT -->
# Implementation Review: F-02 Auth scaffold — Magic-link

- **Plan**: `context/changes/auth-scaffold-magic-link/plan.md`
- **Scope**: All 3 phases
- **Date**: 2026-06-07
- **Commits reviewed**: 8aa4606, 0a7dec3, 5139269, b56a35e
- **Verdict**: NEEDS ATTENTION → TRIAGED (9 fixed, 1 skipped)
- **Findings**: 0 critical | 4 warnings | 6 observations
- **Triage**: Fixed F1, F2(A), F3, F4(A), F5, F7, F8, F9, F10 · Skipped F6 (no queue worker in MVP)

## Verdicts

| Dimension | Verdict |
|-----------|---------|
| Plan Adherence | WARNING |
| Scope Discipline | PASS |
| Safety & Quality | WARNING |
| Architecture | PASS |
| Pattern Consistency | WARNING |
| Success Criteria | PASS |

## Findings

### F1 — consume() read-check-delete is not atomic

- **Severity**: ⚠️ WARNING
- **Impact**: 🏃 LOW — quick decision; fix is obvious and narrowly scoped
- **Dimension**: Safety & Quality
- **Location**: app/Auth/MagicLink.php:49-66
- **Detail**: first()→hash_equals→TTL→delete() is not atomic; two concurrent hits can both pass checks before either delete runs → one token logs in twice.
- **Fix**: Gate success on a conditional delete's affected-row count after the TTL check (`where email AND token`, return rows>0). Postgres makes it atomic.
- **Decision**: FIXED (atomic conditional delete; Functional suite green)

### F2 — GET /login/verify consumes the token

- **Severity**: ⚠️ WARNING
- **Impact**: 🔎 MEDIUM — real tradeoff; pause to reason through it
- **Dimension**: Safety & Quality
- **Location**: routes/web.php:17 + app/Http/Controllers/Auth/MagicLinkController.php:56
- **Detail**: Login + single-use consume happen on a GET; prefetchers/email scanners/preview bots can burn the one-time token before the user clicks.
- **Fix A ⭐ Recommended**: GET shows a confirm page; a POST (CSRF) consumes + logs in.
  - Strength: Eliminates scanner/prefetch burn and CSRF-on-GET smell.
  - Tradeoff: One extra click + a tiny confirm view.
  - Confidence: HIGH — standard mitigation.
  - Blind spot: Slightly more friction for trusted audience.
- **Fix B**: Accept as MVP risk (trusted audience, log driver in dev).
  - Strength: Zero work; matches Laravel signed-URL norms (also GET).
  - Tradeoff: Occasional premature token burn.
  - Confidence: MED — depends on users' mail providers.
  - Blind spot: Real prefetch rates unknown.
- **Decision**: FIXED via Fix A (GET confirm page + POST consume; Acceptance green)

### F3 — Rate limiter has no IP-only cap

- **Severity**: ⚠️ WARNING
- **Impact**: 🏃 LOW — quick decision; fix is obvious and narrowly scoped
- **Dimension**: Safety & Quality
- **Location**: app/Providers/AppServiceProvider.php:30-33
- **Detail**: Limiter keyed by email|ip; rotating the email lets one IP request links to many addresses (mail-bomb / mass-provision). Per-IP cap absent.
- **Fix**: Return two limits — keep per-email, add coarser `Limit::perMinute(N)->by($request->ip())`.
- **Decision**: FIXED (added per-IP 20/15min alongside per-email 5/15min; throttle test + phpstan green)

### F4 — Persistent cache/session only pinned in .env.testing

- **Severity**: ⚠️ WARNING
- **Impact**: 🔎 MEDIUM — real tradeoff; pause to reason through it
- **Dimension**: Safety & Quality / Success Criteria
- **Location**: .env.testing:19-26 (vs production .env)
- **Detail**: RateLimiter only holds across php-fpm workers if prod uses a shared cache store; with array/file cache the throttle silently won't enforce in prod. Sessions likewise must persist. Only the test config guarantees this today.
- **Fix A ⭐ Recommended**: Set prod CACHE_STORE (+SESSION_DRIVER) to a shared store (database/redis); document in infra/tech-stack.
  - Strength: Makes the security control actually hold in prod.
  - Tradeoff: None significant — Railway Postgres available.
  - Confidence: HIGH — RateLimiter requires shared cache.
  - Blind spot: Haven't read Railway prod .env.
- **Fix B**: Verify current Railway env already uses a persistent store.
  - Strength: May already be correct.
  - Tradeoff: Needs deploy-env check.
  - Confidence: MED — unverified.
  - Blind spot: Same.
- **Decision**: FIXED via Fix A (.env.example already defaults both to database; added explicit prod note to infrastructure.md)

### F5 — Open registration diverges from PRD FR-003 (email+kod+pending)

- **Severity**: 🔍 OBSERVATION
- **Impact**: 🔎 MEDIUM — real tradeoff; pause to reason through it
- **Dimension**: Plan Adherence / Scope
- **Location**: app/Http/Controllers/Auth/MagicLinkController.php:41 + resources/views/auth/link-sent.blade.php:8
- **Detail**: F-02 intentionally ships email-only auth (kod/pending → S-02), but the link-sent copy implies conditional sending while every address gets a link + auto-provisioned account. Intentional boundary; copy slightly misleading.
- **Fix**: Confirm standalone-login decision; optionally soften the copy or note the F-02→S-02 gate.
- **Decision**: FIXED (softened link-sent copy to a direct, non-misleading message; open-registration F-02 boundary accepted, kod+pending in S-02)

### F6 — Synchronous mail send → 500 on SMTP failure

- **Severity**: 🔍 OBSERVATION
- **Impact**: 🏃 LOW — quick decision; fix is obvious and narrowly scoped
- **Dimension**: Safety & Quality
- **Location**: app/Http/Controllers/Auth/MagicLinkController.php:47
- **Detail**: notify() sends inline; SMTP failure → token already written + a 500 (no try/catch per rules).
- **Fix**: Implement ShouldQueue on MagicLinkNotification.
- **Decision**: SKIPPED (no queue worker in MVP — has_background_jobs:false; dev mail=log/sync; revisit when real SMTP + queue land)

### F7 — Derived name is unsanitized / may overflow users.name

- **Severity**: 🔍 OBSERVATION
- **Impact**: 🏃 LOW — quick decision; fix is obvious and narrowly scoped
- **Dimension**: Safety & Quality
- **Location**: app/Http/Controllers/Auth/MagicLinkController.php:43
- **Detail**: name = Str::before($email,'@') stores raw input as display name; long local-parts may exceed the column.
- **Fix**: Truncate (e.g. Str::limit) the derived name.
- **Decision**: FIXED (Str::limit to 255 chars)

### F8 — Inline-CSS auth layout instead of Tailwind/Vite

- **Severity**: 🔍 OBSERVATION
- **Impact**: 🏃 LOW — quick decision; fix is obvious and narrowly scoped
- **Dimension**: Pattern Consistency
- **Location**: resources/views/layouts/auth.blade.php:7-30
- **Detail**: Plan said reuse Tailwind v4 + Vite; a self-contained <style> block was used (Vite build not wired locally; deliberate, commented).
- **Fix**: Swap to @vite + Tailwind once the asset build is wired into dev/deploy.
- **Decision**: FIXED (built via one-off node:22 container; layout + 4 views → @vite + Tailwind v4; added Node build step to CI codecept job; full gate green)

### F9 — Several deviations not recorded in the plan

- **Severity**: 🔍 OBSERVATION
- **Impact**: 🏃 LOW — quick decision; fix is obvious and narrowly scoped
- **Dimension**: Plan Adherence
- **Location**: .env.testing, tests/*.suite.yml, pint.json, migration 000002, routes/web.php
- **Detail**: Test-infra (db cache/session, Db-module removal from Acceptance, cleanup:false), pint.json exclude, the extra make_password_nullable migration, magic_links email-as-PK, and login.store/login.sent route names were not in the plan's Changes Required. All justified in commit messages.
- **Fix**: Add a short addendum to plan.md so it stays the source of truth.
- **Decision**: FIXED (added "Addendum — deviations during implementation & review" section to plan.md)

### F10 — make_password_nullable down() unrunnable once nulls exist

- **Severity**: 🔍 OBSERVATION
- **Impact**: 🏃 LOW — quick decision; fix is obvious and narrowly scoped
- **Dimension**: Data safety
- **Location**: database/migrations/2026_06_07_000002_make_password_nullable_on_users.php:21-24
- **Detail**: down() restores NOT NULL; rollback fails if any passwordless user exists. Forward-only in practice.
- **Fix**: Accept + note, or backfill a placeholder password in down() before re-adding NOT NULL.
- **Decision**: FIXED (down() backfills NULL passwords before restoring NOT NULL)
