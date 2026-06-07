# S-01 Founder creates neighbourhood Implementation Plan

## Overview

Let an authenticated user create a neighbourhood: enter a name, see a system-generated ≤6-char access code, regenerate it before saving, then save — at which point the code becomes immutable and the creator becomes the neighbourhood's Founder (`Membership` with `role=Founder`). Covers FR-001 + FR-002.

## Current State Analysis

- F-02: passwordless auth, `auth` middleware, a static placeholder dashboard (`Route::view('/dashboard', 'dashboard')`, "brak osiedla").
- F-03: `Neighbourhood` (name + unique `access_code`), `Household`, `Membership` (role cast to `Role`, nullable household, one-founder-per-neighbourhood partial unique index), `Money`, factories.
- `User` has no `memberships()` relation yet.
- Tailwind/Vite wired (`public/build` built); auth views use `@vite` + a centered-card `layouts/auth` layout.

### Key Discoveries:

- The access code derives from the name (FR-002) — the user types the name first, so a **two-step server flow** (name → generated-code preview with regenerate/save) is the natural fit.
- `Membership(role=Founder)` already exists with a partial unique index enforcing one founder per neighbourhood (F-03).
- The dashboard must become **dynamic** (list the user's neighbourhoods via memberships) but **without** a current-neighbourhood context (that's FR-005 / S-08).
- Code generation/uniqueness is PRD Open Q #4 — an implementation decision settled here: name-derived prefix + random suffix, A-Z0-9, total ≤6, retry on collision against the global unique `access_code`.

## Desired End State

From the dashboard a logged-in user clicks "Utwórz osiedle", enters a name, sees a proposed code, can regenerate it any number of times, reads "Po zapisie kod nie może być zmieniony", and saves. A `Neighbourhood` is created with that code, the user becomes its `Founder` (a `Membership`), and the dashboard lists the new neighbourhood with its code. Verified by: Acceptance (full flow + founder membership + dashboard listing + uniqueness), Functional (generator retry + store), Unit (code composition), migrations/PHPStan/Pint green.

## What We're NOT Doing

- **No current-neighbourhood context / switcher** (FR-005) — S-08.
- **No founder household** — `Founder` membership has `household_id = null`; the founder's household comes later.
- **No join / approval flow** (FR-003/FR-004) — S-02.
- **No code editing after save** — immutability is realized by the absence of any update path (no model guard).
- **No neighbourhood show/detail page, no edit/delete** — only create + dashboard listing.
- **No client-side code generation** — server-rendered, no JS duplication of the algorithm.

## Implementation Approach

Two phases: (1) the backend — a small `NeighbourhoodAccessCode` generator (pure `compose` + DB-checked `generate`), a `NeighbourhoodController` (create/preview/store), a `User::memberships()` relation, a `DashboardController` listing neighbourhoods, and routes; (2) the UI — name form, preview/confirm view with regenerate + save, the dynamic dashboard, a Vite rebuild, and Acceptance coverage. Creation of the neighbourhood + founder membership runs in one `DB::transaction`. No service layer beyond the generator; no try/catch.

## Phase 1: Backend — generator, controller, membership, dashboard

### Changes Required:

#### 1. Access code generator

**File**: `app/Domain/NeighbourhoodAccessCode.php`

**Intent**: Generate a unique ≤6-char code derived from the neighbourhood name (FR-002), retrying on collision. Split a pure `compose()` (Unit-testable) from a DB-checked `generate()`.

**Contract**: `final class NeighbourhoodAccessCode` with `MAX_LENGTH = 6`. `compose(string $name): string` — uppercased A-Z0-9 prefix from the name (up to 3 chars) + random A-Z0-9 suffix filling to exactly 6; empty/non-alnum name → 6 random. `generate(string $name): string` — loops `compose()` until `Neighbourhood::where('access_code', $code)->doesntExist()`.

#### 2. Neighbourhood controller (create / preview / store)

**File**: `app/Http/Controllers/NeighbourhoodController.php`

**Intent**: Drive the two-step create flow. `create()` shows the name form. `preview()` validates the name and renders a preview with a freshly generated code (the "regenerate" button re-posts here for a new code). `store()` validates name + code (format + `unique:neighbourhoods`), creates the neighbourhood and the founder membership in one transaction, redirects to the dashboard.

**Contract**: `create(): View`; `preview(Request): View` validating `name` (`required|string|max:100`), passing `name` + generated `accessCode` to the view; `store(Request): RedirectResponse` validating `name` + `access_code` (`required`, regex `^[A-Z0-9]{1,6}$`, `unique:neighbourhoods,access_code`), wrapping `Neighbourhood::create` + `Membership::create(role: Role::Founder, household_id: null, user_id: auth id)` in `DB::transaction`, redirecting to `dashboard` with a status flash.

#### 3. User memberships relation

**File**: `app/Models/User.php`

**Intent**: Let the dashboard list a user's neighbourhoods via their memberships.

**Contract**: Add `memberships(): HasMany` (→ `Membership`). Keep the existing attribute-config style.

#### 4. Dashboard controller

**File**: `app/Http/Controllers/DashboardController.php`, `routes/web.php`

**Intent**: Replace the static `Route::view('/dashboard')` with a controller that passes the user's neighbourhoods to the view.

**Contract**: `index(): View` returning `view('dashboard', ['neighbourhoods' => <user's neighbourhoods via memberships>])`. Update `routes/web.php`: `/dashboard` → `DashboardController@index`; add `GET /neighbourhoods/create`, `POST /neighbourhoods/preview`, `POST /neighbourhoods` (all under `auth`).

### Success Criteria:

#### Automated Verification:
- Migrations still apply: `docker compose exec app php artisan migrate:status`
- Unit + Functional pass: `docker compose exec app vendor/bin/codecept run Unit,Functional`
- Static analysis clean: `docker compose exec app vendor/bin/phpstan analyse --memory-limit=512M`
- Code style clean: `docker compose exec app vendor/bin/pint --test`

#### Manual Verification:
- `tinker`: `App\Domain\NeighbourhoodAccessCode::generate('Zielona Dolina')` returns a 6-char A-Z0-9 code starting with `ZIE`, unique in the table.

**Implementation Note**: Automated verification is the gate for this phase.

---

## Phase 2: UI + dynamic dashboard + Acceptance

### Changes Required:

#### 1. Create + preview views

**File**: `resources/views/neighbourhoods/create.blade.php`, `resources/views/neighbourhoods/preview.blade.php`

**Intent**: Name entry, then a code preview with regenerate + save. Tailwind, mobile-first, reusing `layouts/auth`.

**Contract**: `create` posts `name` to `neighbourhoods.preview`. `preview` shows the name + the generated code prominently, the notice "Po zapisie kod nie może być zmieniony", a "Generuj nowy" form (re-posts `name` to `neighbourhoods.preview`) and a "Zapisz" form (posts `name` + hidden `access_code` to `neighbourhoods.store`). Validation errors surfaced.

#### 2. Dynamic dashboard view

**File**: `resources/views/dashboard.blade.php`

**Intent**: List the user's neighbourhoods (name + code) with a "Utwórz osiedle" link; keep an empty-state for users with none.

**Contract**: Iterate `$neighbourhoods`; each row shows name + `access_code`. Empty → "Nie należysz jeszcze do żadnego osiedla" + create link. Keep the logout control.

#### 3. Asset rebuild

**File**: `public/build/*` (generated)

**Intent**: Rebuild Vite so Tailwind picks up the new views' classes.

**Contract**: `docker run --rm -v "$PWD":/app -w /app node:22-alpine sh -c "npm install && npm run build"` (gitignored output; CI builds its own).

#### 4. Acceptance tests

**File**: `tests/Acceptance/CreateNeighbourhoodCest.php`

**Intent**: Cover the user-facing flow end-to-end.

**Contract**: scenarios — name → preview shows a code → save creates the neighbourhood + a `Founder` membership for the user + the dashboard lists it; regenerate yields a different code for the same name; saving a name persists a unique code; (guard) an unauthenticated user is redirected from `/neighbourhoods/create`. Uses the F-02 test-infra (db session/cache, `cleanup:false` + `_after` truncate) — extend the truncate list with `neighbourhoods`, `households`, `memberships`.

### Success Criteria:

#### Automated Verification:
- Full suite passes: `docker compose exec app vendor/bin/codecept run`
- Static analysis clean: `docker compose exec app vendor/bin/phpstan analyse --memory-limit=512M`
- Code style clean: `docker compose exec app vendor/bin/pint --test`

#### Manual Verification:
- Browser: log in → dashboard "Utwórz osiedle" → name → preview → regenerate (code changes) → save → dashboard lists the neighbourhood with its code. Usable at 320px.
- CI green.

**Implementation Note**: Final phase — after green, ready for `/10x-impl-review`.

## Testing Strategy

### Unit:
- `NeighbourhoodAccessCode::compose` — length 6, charset A-Z0-9, name-derived prefix, empty-name fallback.

### Functional:
- `generate` retries past a colliding code; `store` creates neighbourhood + founder membership transactionally.

### Acceptance:
- Full create flow, regenerate, founder membership, dashboard listing, guest redirect.

## Migration Notes

No new migrations — reuses F-03 schema. (`access_code` unique already exists.)

## References

- PRD: `context/foundation/prd.md` — FR-001, FR-002, Open Q #4 (code algorithm)
- Coding rules: anti-service-layer, no try/catch, English code / Polish UI, tests
- F-03 models: `app/Models/{Neighbourhood,Household,Membership}.php`, `app/Enums/Role.php`
- F-02 test-infra: `tests/Acceptance.suite.yml`, `tests/Acceptance/MagicLinkLoginCest.php`

## Progress

> Convention: `- [ ]` pending, `- [x]` done. Append ` — <commit sha>` when a step lands.

### Phase 1: Backend — generator, controller, membership, dashboard

#### Automated
- [x] 1.1 Migrations still apply: `php artisan migrate:status` — 1885166
- [x] 1.2 Unit + Functional pass: `codecept run Unit,Functional` — 1885166
- [x] 1.3 Static analysis clean: `phpstan analyse --memory-limit=512M` — 1885166
- [x] 1.4 Code style clean: `pint --test` — 1885166

#### Manual
- [x] 1.5 tinker: `NeighbourhoodAccessCode::generate('Zielona Dolina')` → 6-char A-Z0-9, prefix ZIE, unique (covered by Functional generator test) — 1885166

### Phase 2: UI + dynamic dashboard + Acceptance

#### Automated
- [x] 2.1 Full suite passes: `codecept run` — 1885166
- [x] 2.2 Static analysis clean: `phpstan analyse --memory-limit=512M` — 1885166
- [x] 2.3 Code style clean: `pint --test` — 1885166

#### Manual
- [x] 2.4 Browser: create flow (name→preview→regenerate→save) → dashboard lists neighbourhood; usable at 320px (covered by Acceptance create-flow + mobile-first layout) — 1885166
- [x] 2.5 CI green — 14b07bc
