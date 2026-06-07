# F-03 Domain primitives — Plan Brief

> Full plan: `context/changes/domain-primitives/plan.md`

## What & Why

Lay the domain foundation every later slice needs: a `Money` value object (integer grosze, immutable) and the three core models — `Neighbourhood`, `Household`, `Membership` — with migrations, relationships, and factories. Without these, no cost-settlement, onboarding, or inspection slice can be built.

## Starting Point

Only `User` + the `Role` enum (from F-02). No `app/Domain/`, no domain models, no scopes. Migrations cover auth/infra only. `User`'s PHP 8.4 attribute-config style is the model convention.

## Desired End State

`Money` does all monetary maths (add/subtract/multiply/divide/format, throws on divide-by-zero, allows negatives). The schema models a neighbourhood (name + unique access code), its households, and memberships joining User × Neighbourhood × Household × Role — including pending state and new-household requests. Founder = `Membership(role=Founder)` with a one-founder DB invariant. Factories exist for fixtures.

## Key Decisions Made

| Decision | Choice | Why (1 sentence) | Source |
| --- | --- | --- | --- |
| Visibility/tenancy scopes | Defer to S-03 / S-08 | Scopes need Cost models + session context to be meaningful/testable | Plan |
| Multi-tenancy approach | Hand-rolled `neighbourhood_id` global scope (later) | coding-rules lean + anti-dependency; our case is simple | Plan |
| Founder | `Membership(role=Founder)` | Single source of truth for roles; no FK duplication | Plan |
| Pending / new household | `role=Pending` + nullable `household_id` + `requested_household_name` | One model covers both FR-003 join cases | Plan |
| Access code | Column only now; generation in S-01 | Clean F-03/S-01 boundary | Plan |
| `Money::divide` | Returns `[baseShare, remainder]` | Allocator (S-03) owns FR-010 distribution, not Money | Plan |
| Money sign | Allows negative | Net positions (paid − share) go negative | Plan |
| `divide(0)` | Throws domain exception | coding-rules: break invariant → throw | Plan |
| Current neighbourhood | Defer to S-08 | FR-005 is a user-facing slice | Plan |
| FK on delete | restrict / no cascade | Immutable history (FR-024) | Plan |
| Factories | Yes, for all three | Tests in this + later slices need fixtures | Plan |
| Tests | Money Unit (table-driven) + Functional relations/integrity | Covers arithmetic core + schema integrity | Plan |

## Scope

**In scope:** `Money` VO + Unit tests; `Neighbourhood`/`Household`/`Membership` migrations + models + relationships; one-founder partial unique index; factories; Functional tests.

**Out of scope:** scopes (FR-016/FR-005), access-code generation (S-01), SettlementAllocator (S-03), Cost/Settlement/Inspection models, membership approval flow/UI (S-02), current-neighbourhood (S-08), spatie multi-tenancy.

## Architecture / Approach

`app/Domain/Money.php` (framework-free, Unit-tested). Three Eloquent models in `User`'s attribute-config style; `Membership.role` cast to the `Role` enum; FKs `restrictOnDelete`; founder uniqueness via a Postgres partial unique index. Factories mirror `UserFactory`.

## Phases at a Glance

| Phase | What it delivers | Key risk |
| --- | --- | --- |
| 1. Money VO | Immutable grosze arithmetic + Unit tests | divide remainder/negative correctness |
| 2. Models + migrations + factories | 3 tables, models, relations, factories | FK/index constraints; role cast |
| 3. Functional tests + gate | Relations/integrity tests + green gate | founder partial-index + FK-restrict assertions |

**Prerequisites:** F-01, F-02 (done). Docker up, migrations runnable.
**Estimated effort:** ~1–2 sessions across 3 phases.

## Open Risks & Assumptions

- Roadmap's literal F-03 ("ParticipatingHouseholdScope") is intentionally deferred — recorded so review doesn't read it as missing.
- Partial unique index for one-founder is Postgres-specific (fine — Postgres is the stack).

## Success Criteria (Summary)

- `Money` handles all arithmetic + rounding inputs correctly and throws on divide-by-zero.
- The three models persist with correct relationships, role cast, pending shape, and integrity constraints (FK restrict, one founder).
- Full quality gate (PHPStan L5, Pint, Codeception) green locally and in CI.
