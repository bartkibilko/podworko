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

## 10xDevs AI Toolkit - Module 2, Lesson 3

Review AI-generated code before merge with the **implementation review chain**:

```
/10x-implement -> /10x-impl-review -> triage -> (/10x-lesson | fix | skip | disagree)
```

`/10x-impl-review` is the lesson focus. Review is a quality gate, not an instruction to fix every finding.

### Task Router - Where to start

| Skill | Use it when |
| --- | --- |
| **Code review (lesson focus)** | |
| `/10x-impl-review <change-id>` | You have implemented code and want a structured review before merge. The skill checks plan adherence, scope discipline, safety and quality, architecture, pattern consistency, and success criteria, then presents findings for triage. |
| **Recurring lesson outcome** | |
| `/10x-lesson` | A finding reveals a recurring project rule or agent failure pattern. Record it in `context/foundation/lessons.md` instead of treating it as a one-off note. |

### Triage discipline

- Severity says how bad the finding is. Impact says how much the decision matters now.
- Valid outcomes: fix now, fix differently, skip, accept as risk, record as recurring rule (`/10x-lesson`), disagree.
- Fix critical findings. Do not burn hours on low-impact observations just because the agent found them.
- Conscious skipping of low-impact findings is a valid review outcome, not negligence.
- If you disagree with a finding, record why. Wrong agent reasoning is also signal.

### Review boundaries

- This lesson reviews implemented code. It does not create the plan, execute new phases, or teach CI review.
- Testing strategy and quality gates are introduced in Module 3.
- Do not use `/10x-contract` as a triage outcome in this lesson.

### Paths used by this lesson

- `context/changes/<change-id>/plan.md` - expected implementation contract
- `context/changes/<change-id>/reviews/` - review output
- `context/foundation/lessons.md` - recurring lessons

Skills must not write to `context/archive/`. Archived changes are immutable; if a resolved target path starts with `context/archive/`, abort with: "This change is archived. Open a new change with `/10x-new` instead."

<!-- END @przeprogramowani/10x-cli -->
