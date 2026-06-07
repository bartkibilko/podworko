# F-03 Domain primitives Implementation Plan

## Overview

Lay the domain foundation: a `Money` value object (integer grosze, immutable arithmetic) plus the three core models — `Neighbourhood`, `Household`, `Membership` — with their migrations, relationships, and factories. This is foundation slice F-03; it deliberately does **not** build the visibility/tenancy global scopes (those need the Cost models from S-03 and the context switcher from S-08 to be meaningful and testable).

## Current State Analysis

- Only `User` model and `Role` enum (`app/Enums/Role.php`, cases Founder/Owner/Guest/Pending) exist. No `app/Domain/`, no `app/Models/Scopes/`, no domain models.
- Migrations so far: `users` (+ `password_reset_tokens`, `sessions`), `cache`, `jobs`, `magic_links`, `make_password_nullable_on_users`.
- `User` uses PHP 8.4 attribute config (`#[Fillable]`, `#[Hidden]`) + `casts()` — the convention to follow for new models.
- F-01 tooling live: PHPStan L5, Pint, Codeception (Unit suite = Asserts only, no framework boot; Functional = Laravel + run_database_migrations).

### Key Discoveries:

- **Money is pinned by `context/foundation/coding-rules.md:44-48`**: `app/Domain/Money.php`, immutable, integer-backed, `add()/subtract()/multiply(int)/divide(int): array/format()`; bigint `_grosze` columns; arithmetic only in grosze. FR-010 rounding policy lives in a later `SettlementAllocator` (S-03), NOT in `Money`.
- **Role lives on `Membership`, not `User`** — per PRD `## Access Control`; the `Role` enum already exists from F-02 as the type to cast against.
- **Visibility/tenancy scopes are premature here** — `ParticipatingHouseholdScope` (FR-016) attaches to Cost/Settlement (S-03); per-neighbourhood scoping (FR-005) needs session context (S-08). Building them now = dead, untestable code.
- **History is immutable (FR-024)** — off-boarding is soft-state, not hard delete; FKs must not cascade.

## Desired End State

- `Money` value object usable by S-03's settlement maths: construct from grosze, add/subtract/multiply/divide, format to Polish "zł", throw on divide-by-zero.
- Schema + Eloquent models for `Neighbourhood` (name + unique `access_code`), `Household` (belongs to a neighbourhood), `Membership` (joins User × Neighbourhood × Household × Role, supports pending + new-household requests).
- Founder modelled as `Membership(role=Founder)`, with a one-founder-per-neighbourhood DB invariant.
- Model factories for all three, so this and later slices can build fixtures.
- Verified by: Unit tests (Money arithmetic/rounding/format/throw), Functional tests (relationships, role cast, FK restrict, pending shape, founder uniqueness), migrations apply cleanly, PHPStan L5 + Pint green.

## What We're NOT Doing

- **No `ParticipatingHouseholdScope` / per-neighbourhood global scope** — deferred to S-03 (Cost) and S-08 (context switcher).
- **No `current_neighbourhood` concept** (FR-005) — S-08.
- **No access-code generation/reset** (FR-002) — only the column; generation is S-01.
- **No `SettlementAllocator` / FR-010 distribution** — S-03. `Money::divide` returns base share + remainder only.
- **No Cost / Settlement / Inspection models** — later slices.
- **No spatie/laravel-multitenancy** — when scoping lands it will be a hand-rolled `neighbourhood_id` global scope.
- **No membership approval flow / endpoints / UI** — S-02. F-03 is models + VO only.
- **No `Money` currency support** — PLN is implicit; grosze only.

## Implementation Approach

Three incremental phases, each independently verifiable: (1) the framework-free `Money` value object with Unit coverage; (2) the schema + models + factories; (3) Functional coverage of relationships and integrity. Follow `User`'s attribute-config style for models; native typing throughout; no service layer; throw on broken invariants.

## Critical Implementation Details

- **One-founder-per-neighbourhood** is enforced with a Postgres partial unique index on `memberships (neighbourhood_id) WHERE role = 'founder'` — there is no FK to express "exactly one founder", so the constraint lives in the schema as a partial index plus the `Role` enum cast.
- **`Money::divide` with negative grosze** uses `intdiv` (truncation toward zero); the remainder carries the sign. The S-03 allocator owns final distribution, so F-03 only guarantees `baseShare * parts + remainder === grosze`.

## Phase 1: Money value object

### Overview

A framework-free, immutable money primitive in grosze, with Unit tests. No DB, no Laravel boot.

### Changes Required:

#### 1. Money value object

**File**: `app/Domain/Money.php`

**Intent**: Centralize all monetary arithmetic in one immutable, integer-backed type so no other code does money maths (per coding-rules). Allows negative values (net positions). FR-010 rounding distribution is explicitly out — it belongs to S-03's allocator.

**Contract**: `final class Money` holding `private readonly int $grosze`. Methods: `__construct(int $grosze)`; `grosze(): int`; `add(self): self`; `subtract(self): self`; `multiply(int): self`; `divide(int $parts): array` returning `[int $baseShare, int $remainder]` where `baseShare = intdiv($grosze, $parts)` and `remainder = $grosze - $baseShare * $parts`, throwing `InvalidArgumentException` when `$parts <= 0`; `equals(self): bool`; `isNegative(): bool`; `format(): string` rendering Polish currency (e.g. `1234` → `"12,34 zł"`, negative handled). `strict_types` declared.

### Success Criteria:

#### Automated Verification:

- Unit suite passes: `docker compose exec app vendor/bin/codecept run Unit`
- Static analysis clean: `docker compose exec app vendor/bin/phpstan analyse --memory-limit=512M`
- Code style clean: `docker compose exec app vendor/bin/pint --test`

#### Manual Verification:

- `tinker`: `(new App\Domain\Money(2500))->divide(3)` returns `[833, 1]` and `833*3+1 === 2500`.
- `(new App\Domain\Money(-2500))->format()` renders a sensible negative string.

**Implementation Note**: Automated verification is the gate for this phase (framework-free maths).

---

## Phase 2: Domain models, migrations, factories

### Overview

Schema and Eloquent models for the three domain entities, with factories for fixtures.

### Changes Required:

#### 1. Neighbourhoods table + model

**File**: `database/migrations/<ts>_create_neighbourhoods_table.php`, `app/Models/Neighbourhood.php`

**Intent**: A neighbourhood with a human name and an immutable short access code (generation deferred to S-01).

**Contract**: Table `neighbourhoods`: `id`, `name` (string), `access_code` (string, **unique**, ≤6 chars by convention), `timestamps`. Model `final class Neighbourhood` with attribute-config fillables (`name`, `access_code`), `households(): HasMany`, `memberships(): HasMany`. Working `down()`.

#### 2. Households table + model

**File**: `database/migrations/<ts>_create_households_table.php`, `app/Models/Household.php`

**Intent**: A household belongs to a neighbourhood; users attach via memberships (no direct user FK).

**Contract**: Table `households`: `id`, `neighbourhood_id` (FK → neighbourhoods, **restrictOnDelete**), `label` (string), `timestamps`. Model `final class Household` with `neighbourhood(): BelongsTo`, `memberships(): HasMany`.

#### 3. Memberships table + model

**File**: `database/migrations/<ts>_create_memberships_table.php`, `app/Models/Membership.php`

**Intent**: The join of User × Neighbourhood × Household × Role. Founder is a membership with `role=Founder`. Supports pending state and the "request a new household" case (nullable household + requested name).

**Contract**: Table `memberships`: `id`, `user_id` (FK users, restrict), `neighbourhood_id` (FK, restrict), `household_id` (FK households, **nullable**, restrict), `role` (string), `requested_household_name` (string, nullable), `timestamps`. Constraints: unique `(user_id, neighbourhood_id)`; **partial unique index** on `(neighbourhood_id) WHERE role = 'founder'`. Model `final class Membership` with `casts()` mapping `role => Role::class`, relations `user()/neighbourhood()/household()` (household nullable). `down()` drops cleanly.

#### 4. Factories

**File**: `database/factories/NeighbourhoodFactory.php`, `HouseholdFactory.php`, `MembershipFactory.php`

**Intent**: Fixture builders for tests (this slice and S-01..S-03), mirroring `UserFactory`.

**Contract**: Each factory `definition()` returns valid defaults (random name, unique-ish access_code, role defaulting to Owner; household linked to its neighbourhood). Wire `HasFactory` + `#[UseFactory(...)]`/`newFactory` per the `User` convention so `Model::factory()` resolves.

### Success Criteria:

#### Automated Verification:

- Migrations apply cleanly: `docker compose exec app php artisan migrate`
- Static analysis clean: `docker compose exec app vendor/bin/phpstan analyse --memory-limit=512M`
- Code style clean: `docker compose exec app vendor/bin/pint --test`

#### Manual Verification:

- `tinker`: building a neighbourhood → household → owner membership via factories produces a connected graph with `role` cast to a `Role` enum.

**Implementation Note**: Automated verification is the gate for this phase.

---

## Phase 3: Functional tests + green gate

### Overview

Functional coverage of relationships and integrity, then the full quality gate.

### Changes Required:

#### 1. Domain model Functional tests

**File**: `tests/Functional/DomainModelsCest.php`

**Intent**: Prove the schema/relationships behave: relationship traversal, role enum cast, FK restrict (no cascade), pending shape, founder uniqueness.

**Contract**: Behaviour-named methods covering: neighbourhood→household→membership→user traversal; `membership.role` is a `Role` instance; a pending membership with null `household_id` + `requested_household_name` persists; deleting a neighbourhood that still has households is rejected (restrict); a second `role=Founder` membership in one neighbourhood violates the partial unique index. Uses factories + Laravel module record assertions (same-connection, per the F-02 lesson).

### Success Criteria:

#### Automated Verification:

- Full suite passes: `docker compose exec app vendor/bin/codecept run`
- Static analysis clean: `docker compose exec app vendor/bin/phpstan analyse --memory-limit=512M`
- Code style clean: `docker compose exec app vendor/bin/pint --test`

#### Manual Verification:

- CI run is green (phpstan + codecept jobs).
- No regression in the F-02 auth suites.

**Implementation Note**: Final phase — after green, ready for `/10x-impl-review`.

## Testing Strategy

### Unit Tests:
- `Money`: add/subtract/multiply/divide (incl. remainder correctness and negatives), format, divide-by-zero throw. Table-driven.

### Functional Tests:
- Model relationships, role cast, FK restrict, pending membership shape, one-founder invariant.

### Manual Testing Steps:
1. `docker compose exec app php artisan migrate`.
2. `tinker`: exercise `Money::divide` and factory graph as above.

## Performance Considerations

Negligible (low QPS, small data). Indexes: `access_code` unique, `(user_id, neighbourhood_id)` unique, partial founder index, FK indexes.

## Migration Notes

Additive migrations only; no existing data. All `down()` drop cleanly. FKs use `restrictOnDelete` to preserve history (FR-024).

## References

- PRD: `context/foundation/prd.md` — FR-001/002 (neighbourhood+code), FR-003/004 (membership/pending), `## Access Control`, NFR (sum=total → integer Money)
- Coding rules: `context/foundation/coding-rules.md` — Money §, Visibility/tenancy §, Migrations §, Tests §
- Roadmap: `context/foundation/roadmap.md:100-112` (F-03), boundaries to S-01/S-02/S-03/S-08
- Prior art: `app/Models/User.php` (attribute config), `app/Enums/Role.php`

## Progress

> Convention: `- [ ]` pending, `- [x]` done. Append ` — <commit sha>` when a step lands. Do not rename step titles.

### Phase 1: Money value object

#### Automated

- [x] 1.1 Unit suite passes: `codecept run Unit` — a81d11f
- [x] 1.2 Static analysis clean: `phpstan analyse --memory-limit=512M` — a81d11f
- [x] 1.3 Code style clean: `pint --test` — a81d11f

#### Manual

- [x] 1.4 tinker: `Money(2500)->divide(3)` = `[833, 1]`; negative format sensible — a81d11f

### Phase 2: Domain models, migrations, factories

#### Automated

- [x] 2.1 Migrations apply cleanly: `php artisan migrate` — a81d11f
- [x] 2.2 Static analysis clean: `phpstan analyse --memory-limit=512M` — a81d11f
- [x] 2.3 Code style clean: `pint --test` — a81d11f

#### Manual

- [x] 2.4 tinker: factory graph neighbourhood→household→membership with role cast — a81d11f

### Phase 3: Functional tests + green gate

#### Automated

- [x] 3.1 Full suite passes: `codecept run` — a81d11f
- [x] 3.2 Static analysis clean: `phpstan analyse --memory-limit=512M` — a81d11f
- [x] 3.3 Code style clean: `pint --test` — a81d11f

#### Manual

- [ ] 3.4 CI run is green (phpstan + codecept)
- [x] 3.5 No regression in F-02 auth suites — a81d11f
