# S-01 Founder creates neighbourhood — Plan Brief

> Full plan: `context/changes/founder-creates-neighbourhood/plan.md`

## What & Why

Let a logged-in user create a neighbourhood (name → system-generated ≤6-char access code, regenerable before save), becoming its Founder. Covers FR-001 + FR-002 and unblocks S-02 (join + approve).

## Starting Point

F-02 auth + static "brak osiedla" dashboard; F-03 `Neighbourhood`/`Household`/`Membership` models (role cast, one-founder partial unique index, unique `access_code`) + factories. `User` has no `memberships()` yet.

## Desired End State

From the dashboard a user creates a neighbourhood, previews/regenerates the code, saves (code now immutable), becomes Founder (`Membership`), and the dashboard lists their neighbourhoods with codes.

## Key Decisions Made

| Decision | Choice | Source |
| --- | --- | --- |
| Code algorithm | Name-derived prefix + random suffix, A-Z0-9, ≤6, retry on collision | Plan |
| Preview/regenerate | Two-step server flow (name → code preview → regenerate/save) | Plan |
| Founder household | Deferred — Founder membership with `household_id = null` | Plan |
| Dashboard | Dynamic list of user's neighbourhoods (no current-context; S-08) | Plan |
| Code charset/collision | A-Z0-9, global unique, generator retries | Plan |
| Immutability + multi | No edit path; a user may found multiple neighbourhoods | Plan |
| Tests | Acceptance flow + Functional generator + Unit compose | Plan |

## Scope

**In:** access-code generator, NeighbourhoodController (create/preview/store), `User::memberships()`, DashboardController + dynamic dashboard, create/preview views, founder membership, tests.
**Out:** current-neighbourhood/switcher (S-08), founder household, join/approval (S-02), code editing, neighbourhood show/edit/delete, client-side generation.

## Architecture / Approach

`app/Domain/NeighbourhoodAccessCode` (pure `compose` + DB-checked `generate`); `NeighbourhoodController` two-step flow; create neighbourhood + founder `Membership` in one `DB::transaction`; dashboard lists neighbourhoods via memberships. Reuses Tailwind `layouts/auth`.

## Phases at a Glance

| Phase | Delivers | Key risk |
| --- | --- | --- |
| 1. Backend | Generator, controller, founder membership, dashboard listing + Unit/Functional | code uniqueness/retry; transactional creation |
| 2. UI + Acceptance | Create/preview views, dynamic dashboard, Vite rebuild, Acceptance + gate | two-step regenerate flow; test-infra truncate list |

**Prerequisites:** F-01/F-02/F-03 (done). **Estimated effort:** ~1 session, 2 phases.

## Open Risks & Assumptions

- Save trusts the posted `access_code` (hidden field) but re-validates format + uniqueness — tampering can only yield another valid unique code (harmless).
- Concurrency on `access_code` is guarded by the unique index; race is negligible for solo MVP.

## Success Criteria (Summary)

- A user creates a neighbourhood end-to-end, regenerates the code, saves, and becomes Founder; dashboard lists it.
- Code is unique, ≤6 A-Z0-9, name-derived, immutable after save.
- Full quality gate (PHPStan L5, Pint, Codeception) green.
