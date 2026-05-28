# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

Podworko — web app for residential cost-sharing in unmanaged neighbourhoods. Owners log shared expenses; the system computes who-owes-whom between participating households; both sides confirm each payment before a ledger entry closes.

## Stack

Laravel 13.8 + PHP 8.4 + Postgres 17.7, entirely containerised. See `context/foundation/tech-stack.md` for rationale and known frictions.

## Common commands

```bash
docker compose up -d --build       # first run, or after Dockerfile change
docker compose up -d               # subsequent starts
docker compose down                # stop (volume preserved)

docker compose exec app php artisan migrate
docker compose exec app vendor/bin/codecept run                 # full suite (after codeception install)
docker compose exec app vendor/bin/codecept run Acceptance      # single suite
docker compose exec app php artisan tinker
docker compose exec app vendor/bin/pint                         # code style autofix
docker compose exec app vendor/bin/phpstan analyse              # static analysis (after install)

docker compose exec db psql -U podworko_user -d podworko_db

# Browser: http://localhost:8000
```

## Rules & references

Read these as relevant to the task at hand. They are NOT always needed every turn — load them on demand.

- **Product specification**: `context/foundation/prd.md` — read before designing schema, controllers, or business logic. Functional requirements (FR-NNN), Business Logic, Access Control, Open Questions.
- **Stack rationale & frictions**: `context/foundation/tech-stack.md` — why Laravel; the two known frictions (`typed: false` → PHPStan/Larastan planned; multi-tenancy not OOTB).
- **Git workflow**: `context/foundation/git-rules.md` — commit messages, SemVer, when (not) to commit.
- **Docker gotchas**: `context/foundation/docker-rules.md` — UID/GID guard, DB credentials single-source, `composer dev` trap, reset/rebuild flows.
- **Bootstrap audit trail**: `context/changes/bootstrap-verification/verification.md` — scaffold log, hand-off, hints recorded but not acted on.
- **Coding rules**: `context/foundation/coding-rules.md` — strict types, naming, anti-abstraction, money/visibility/tenancy patterns, Codeception test setup, PHPStan level 5.

<!-- BEGIN @przeprogramowani/10x-cli -->

## 10xDevs AI Toolkit - Module 2, Lesson 1

Move from sprint-zero setup to project orchestration with the **roadmap chain**:

```
(Module 1 foundation docs) -> /10x-roadmap -> backlog-ready roadmap items
```

`/10x-roadmap` is the lesson focus. `/10x-new` is intentionally introduced in Module 2, Lesson 2, when a selected roadmap item becomes an implementation change folder.

### Task Router - Where to start

| Skill | Use it when |
| --- | --- |
| **Roadmap (lesson focus)** | |
| `/10x-roadmap` | You have `context/foundation/prd.md` and a scaffolded project baseline, and you need a vertical-first MVP roadmap. The skill reads the PRD, inspects the code baseline, uses available foundation docs such as `tech-stack.md`, `infrastructure.md`, and `deploy-plan.md`, then writes `context/foundation/roadmap.md`. Use it BEFORE creating per-change folders or implementation plans. |
| **Re-run upstream if needed** | |
| `/10x-shape` / `/10x-prd` / `/10x-tech-stack-selector` / `/10x-bootstrapper` / `/10x-agents-md` / `/10x-infra-research` | Bundled from Module 1 so foundation contracts can be fixed before roadmap sequencing. If roadmap generation exposes a PRD gap, repair the PRD before pretending the backlog is ready. |

### How the chain hands off

- `/10x-roadmap` bridges product and implementation. It does not choose frameworks, design schemas, or write a per-change implementation plan.
- The output is `context/foundation/roadmap.md`: ordered milestones, vertical slices, bounded foundations, dependencies, unknowns, risk, and backlog handoff fields.
- Roadmap items should receive stable human-readable identifiers in backlog tools. The actual `context/changes/<change-id>/` folder is created in Lesson 2 with `/10x-new`.

### Roadmap boundaries

- Default to vertical slices: user-visible outcomes that cross UI, data, business logic, and integrations.
- Horizontal work is allowed only as a bounded enabler that names the downstream vertical milestone it unlocks.
- Avoid orphan horizontal work such as "build the whole database", "build all API endpoints", or "design the whole UI" before the first user-visible flow.
- Roadmap is not a calendar estimate. Do not invent dates, story points, or sprint velocity unless the user explicitly asks for a separate planning artifact.

### Foundation paths used by this lesson

- `context/foundation/prd.md` - input
- `context/foundation/tech-stack.md` - optional input
- `context/foundation/infrastructure.md` - optional input
- `context/deployment/deploy-plan.md` - optional input
- `context/foundation/roadmap.md` - output
- `context/foundation/lessons.md` - recurring rules and pitfalls
- `docs/reference/contract-surfaces.md` - load-bearing names registry

Skills must not write to `context/archive/`. Archived changes are immutable; if a resolved target path starts with `context/archive/`, abort with: "This change is archived. Open a new change with `/10x-new` instead."

<!-- END @przeprogramowani/10x-cli -->
