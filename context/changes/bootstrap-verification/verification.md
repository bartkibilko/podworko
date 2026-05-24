---
bootstrapped_at: 2026-05-24T14:29:30Z
starter_id: laravel
starter_name: Laravel
project_name: podworko
language_family: php
package_manager: composer
cwd_strategy: subdir-then-move
bootstrapper_confidence: verified
phase_3_status: ok
audit_command: "null"
---

## Hand-off

Verbatim copy of `context/foundation/tech-stack.md` frontmatter:

```yaml
starter_id: laravel
package_manager: composer
project_name: podworko
hints:
  language_family: php
  team_size: solo
  deployment_target: fly
  ci_provider: github-actions
  ci_default_flow: auto-deploy-on-merge
  bootstrapper_confidence: verified
  path_taken: standard
  quality_override: false
  self_check_answers: null
  has_auth: true
  has_payments: false
  has_realtime: false
  has_ai: false
  has_background_jobs: false
```

### Why this stack (verbatim)

Solo developer budujący after-hours przez 12 tygodni multi-tenant web-appkę dla wspólnot mieszkaniowych bez zarządcy. Laravel to recommended default dla pary `(web, php)`; clears trzy z czterech agent-friendly bramek (convention-based, popular_in_training, well_documented), pęka na `typed: false` — kompensata przez PHPStan/Larastan w CLAUDE.md, do dograć w bootstrapperze. Magic-link auth (FR-003) zbudowany w Fortify; Storage facade pokrywa załączniki paragonów (FR-013, Open Question #3); Eloquent policies mapują naturalnie na role Założyciel/Właściciel/Gość/Oczekujący z sekcji Access Control. Bootstrapper confidence: verified — scaffolding będzie gładki. Deployment na Fly.io (starter default), CI na GitHub Actions z auto-deploy on merge. Auth flag ustawiony; payments/realtime/AI/background-jobs out-of-scope per PRD Non-Goals (powiadomienia odłożone do v2 per Open Question #2). Multi-tenant context switching (FR-005) nie jest OOTB w Laravel — wymaga spatie/laravel-multitenancy lub ręcznego global scope per tenant_id, surfaced jako friction w konwersacji wyboru.

## Pre-scaffold verification

| Signal             | Value                                          | Severity | Notes                                                          |
| ------------------ | ---------------------------------------------- | -------- | -------------------------------------------------------------- |
| npm package        | not run                                        | n/a      | non-JS starter; cmd_template invokes composer, not npm         |
| GitHub repo        | not run                                        | n/a      | card docs_url is https://laravel.com/docs (not a github.com URL) |

No recency signal available; WARN-AND-CONTINUE per protocol.

## Scaffold log

**Resolved invocation**: `composer create-project laravel/laravel .bootstrap-scaffold --no-interaction --prefer-dist`
**Strategy**: subdir-then-move
**Exit code**: 0
**Files moved**: 23
**Conflicts (.scaffold siblings)**: none
**.gitignore handling**: moved silently (cwd had no prior .gitignore)
**.bootstrap-scaffold cleanup**: deleted

Moved entries: `.editorconfig`, `.env`, `.env.example`, `.gitattributes`, `.gitignore`, `.npmrc`, `app/`, `artisan`, `bootstrap/`, `composer.json`, `composer.lock`, `config/`, `database/`, `package.json`, `phpunit.xml`, `public/`, `README.md`, `resources/`, `routes/`, `storage/`, `tests/`, `vendor/`, `vite.config.js`.

Preserved meta-scaffold (untouched by conflict policy): `.claude/`, `.git/`, `CLAUDE.md`, `context/`, `skills-lock.json`.

Side-signal from composer install (not a formal Step 3 audit): `No security vulnerability advisories found` was reported during dependency resolution. Initial migrations also ran (`0001_01_01_000000_create_users_table`, `0001_01_01_000001_create_cache_table`, `0001_01_01_000002_create_jobs_table`) against the bundled SQLite database at `database/database.sqlite`.

## Post-scaffold audit

**Tool**: skipped — no built-in audit tool for php
**Recommended external tool**: Roave's `roave/security-advisories` Composer plugin (zero-cost, blocks `composer require` of any package with a known advisory) or `local-php-security-checker` (CLI scanner against `composer.lock`). Either can be wired into CI later as the standing replacement for the missing built-in audit slot.

## Hints recorded but not acted on

| Hint                       | Value                              |
| -------------------------- | ---------------------------------- |
| bootstrapper_confidence    | verified                           |
| quality_override           | false                              |
| path_taken                 | standard                           |
| self_check_answers         | null                               |
| team_size                  | solo                               |
| deployment_target          | fly                                |
| ci_provider                | github-actions                     |
| ci_default_flow            | auto-deploy-on-merge               |
| has_auth                   | true                               |
| has_payments               | false                              |
| has_realtime               | false                              |
| has_ai                     | false                              |
| has_background_jobs        | false                              |

v1 surfaces these in the closing summary but takes no automated compensating action. A future M1L4 skill will consume them when generating agent context.

## Next steps

Next: a future skill will set up agent context (CLAUDE.md, AGENTS.md). For now, your project is scaffolded and verified — happy hacking.

Useful manual steps in the meantime:
- `git init` (if you have not already) to start your own repo history.
- Review any `.scaffold` siblings the conflict policy created and decide which version of each file to keep.
- Address audit findings per your project's risk tolerance — the full breakdown is in this log.
