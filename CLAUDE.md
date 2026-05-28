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

## 10xDevs AI Toolkit - Module 2, Lesson 2

Turn one roadmap item into the first implementation cycle with the **change planning chain**:

```
/10x-roadmap -> /10x-new -> /10x-plan -> /10x-plan-review -> /10x-implement
```

`/10x-new`, `/10x-plan`, `/10x-plan-review`, and `/10x-implement` are the lesson focus. `/10x-frame` and `/10x-research` are not required rituals here; they are escalation paths introduced in the next lesson.

### Task Router - Where to start

| Skill | Use it when |
| --- | --- |
| **Change setup (lesson focus)** | |
| `/10x-new <change-id>` | You selected a roadmap item and need a stable change folder. Creates `context/changes/<change-id>/change.md` so planning, implementation, progress, commits, and later review all share one identity. Use AFTER roadmap selection, BEFORE `/10x-plan`. |
| **Planning (lesson focus)** | |
| `/10x-plan <change-id>` | You have a change folder and need a reviewable implementation plan. Reads roadmap context, foundation docs, codebase evidence, and any existing change notes; writes `plan.md` and `plan-brief.md` with phases, file contracts, success criteria, and `## Progress`. |
| **Plan readiness (lesson focus)** | |
| `/10x-plan-review <change-id>` | You have `plan.md` and need a light pre-code readiness check. Use it to catch missing end state, weak contracts, malformed progress, scope drift, or blind spots before code changes begin. |
| **Implementation (lesson focus)** | |
| `/10x-implement <change-id> phase <n>` | You have an approved plan and want to execute one phase with verification, manual gate, commit ritual, and SHA write-back to `## Progress`. |
| **Lifecycle closure** | |
| `/10x-archive <change-id>` | A change is merged or intentionally closed. Move it out of active `context/changes/` into archive state. |

### How the chain hands off

- `/10x-new` creates the durable change identity.
- `/10x-plan` turns that identity into an implementation contract.
- `/10x-plan-review` checks the plan before the agent mutates code.
- `/10x-implement` executes one planned phase, verifies, asks for manual confirmation when needed, commits, and records progress.

### Lesson boundaries

- Plan is the default router after roadmap selection. Start with `/10x-plan` unless the problem is unclear or external evidence is blocking.
- Do not run `/10x-frame + /10x-research` as ceremony for every change.
- Do not turn this lesson into a full end-to-end product build. A checkpoint with a planned and partially or fully implemented stream is valid.
- Code review of the implemented diff belongs to Lesson 3 via `/10x-impl-review`.
- Lifecycle closure via `/10x-archive` after a change is merged or intentionally closed.

### Paths used by this lesson

- `context/foundation/roadmap.md` - upstream roadmap
- `context/changes/<change-id>/change.md` - change identity
- `context/changes/<change-id>/plan.md` - implementation contract
- `context/changes/<change-id>/plan-brief.md` - compressed handoff
- `context/foundation/lessons.md` - recurring rules and pitfalls
- `docs/reference/contract-surfaces.md` - load-bearing names registry

Skills must not write to `context/archive/`. Archived changes are immutable; if a resolved target path starts with `context/archive/`, abort with: "This change is archived. Open a new change with `/10x-new` instead."

<!-- END @przeprogramowani/10x-cli -->
