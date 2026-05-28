# F-01: Project tooling — Plan Brief

> Full plan: `context/changes/project-tooling/plan.md`

## What & Why

F-01 to pierwsza foundation Podwórka: wpięcie PHPStan/Larastan level 5, Codeception 3 suites, Pint pre-commit przez CaptainHook, plus minimalne GitHub Actions CI. Cel: kompensata `typed: false` Laravela (decyzja z `tech-stack.md`) i ustanowienie deterministycznej ścieżki weryfikacji dla wszystkich następnych slice'ów — F-02 (auth) i F-03 (domain primitives) wymagają już-działającego phpstan/codecept jako sanity gate.

## Starting Point

Laravel 13.8 scaffold z PHPUnit 12 + Pint już w `require-dev`, ale: brak `phpstan.neon`, brak `captainhook.json`, brak `codeception.yml`, brak `.github/workflows/`. `tests/` zawiera scaffold Laravela (`Feature/`, `Unit/ExampleTest.php`, `TestCase.php`) — do usunięcia per `coding-rules.md` § Tests. `composer.json scripts.test` wrapper na `php artisan test` jest sprzeczny z deklaracją "nie używamy artisan test".

## Desired End State

Każda zmiana w `app/`, `database/`, `tests/` przechodzi przez PHPStan level 5 z 0 errors. `vendor/bin/codecept run` startuje 3 suites (Unit / Functional / Acceptance) z `cleanup: true` na Postgres. `git commit` z brudnym formatowaniem PHP jest blokowany przez CaptainHook → autofix. Każdy PR triggeruje 2 joby CI (phpstan + codecept), oba muszą być zielone żeby merge. F-02 i kolejne slices startują na sprawdzonym fundamencie.

## Key Decisions Made

| Decision | Choice | Why (1 sentence) | Source |
| --- | --- | --- | --- |
| Test framework | Codeception (3 suites: Unit/Functional/Acceptance) | Już zadeklarowany w `coding-rules.md` § Tests jako kompensata `typed: false`. | coding-rules.md |
| Static analysis level | PHPStan/Larastan level 5 | Baseline per `coding-rules.md:119`; bump do 6/7/8 w osobnym PR gdy codebase stabilny. | coding-rules.md |
| Pre-commit mechanism | CaptainHook (composer-native) | Trzyma narzędziowanie w composerze, brak Node deps, auto-instaluje hook przez plugin-composer. | Plan |
| Pre-commit scope | Pint tylko (`--dirty` autofix) | <1s commit → brak presji na `--no-verify`; PHPStan/Codeception lecą w CI. | Plan |
| composer test alias | Repoint na `codecept run` | `coding-rules.md` zabrania `artisan test`; alias musi być spójny. | Plan |
| CI scope | Minimal GH Actions YAML (phpstan + codecept jobs) | User override roadmap Parked-decision; chce server-side gate od dnia zero. | Plan (override) |
| CI Postgres driver | Service container Postgres 17 (native PHP, brak Docker-in-Docker) | Faster CI niż docker-compose; SQLite odrzucony bo bigint behavior różni się od Pg per coding-rules.md § Money. | Plan |
| Cleanup Laravel-default tests | Usuwamy `tests/Feature/`, `tests/Unit/ExampleTest.php`, `tests/TestCase.php` | `coding-rules.md:86` explicite. | coding-rules.md |
| PHPUnit dev-dep | Usuwamy z direct, zostaje transitive z codeception | `coding-rules.md:65` — codeception ma PHPUnit pod spodem. | coding-rules.md |
| Pint configuration | Brak `pint.json` (PSR-12 default) | `coding-rules.md:11` — brak personal overrides. | coding-rules.md |

## Scope

**In scope:**
- PHPStan/Larastan install + `phpstan.neon` per coding-rules.md
- Codeception install + bootstrap + 3 suite configs + 3 smoke Cest
- Cleanup Laravel-default tests + PHPUnit removal z direct deps
- CaptainHook install + `captainhook.json` z `pint --dirty` pre-commit
- `.github/workflows/ci.yml` z phpstan + codecept jobs
- `.env.testing` matchujący CI service container
- `composer.json` scripts: `phpstan` alias, repoint `test` na codecept

**Out of scope:**
- Migracja istniejących testów domain-owych (nie ma)
- PHPStan baseline file (zakładamy 0 errors na scaffoldzie)
- Pint personal-style overrides
- Pre-commit dla PHPStan/Codeception (świadomie tylko Pint)
- Husky/lint-staged/shell-hook (CaptainHook wybrany)
- CI cache dependency optymalizacje (follow-up gdy uciążliwy)
- Branch protection rules click-through (manualne TODO post-F-01)
- Blade linter (niewspółmierne do MVP scope)

## Architecture / Approach

4 fazy, każda osobny commit, każda zostawia projekt runnable.
`P1 PHPStan` (najmniejsze ryzyko, pierwszy quality signal) → `P2 Codeception` (największy diff, sanity-checked przez P1 phpstan) → `P3 Pint+CaptainHook` (izolowane, brak tarcia z testami) → `P4 CI YAML` (konsumuje wszystkie 3, używa commands z P1+P2).

Wszystkie composer/artisan/codecept commands przez `docker compose exec app` per `docker-rules.md`. Hook CaptainHook też → unikamy desyncu PHP version host vs kontener.

## Phases at a Glance

| Phase | What it delivers | Key risk |
| --- | --- | --- |
| 1. Static analysis | PHPStan level 5 + `composer phpstan` script, 0 errors baseline | Larastan extension może mieć false-positives na Laravel 13.8 magic (świeża wersja); fallback `excludePaths` lub inline `ignoreErrors` |
| 2. Testing framework | Codeception 3 suites + smoke Cest + cleanup scaffold + repoint `composer test` | Acceptance suite Laravel module config tricky (`cleanup: true` + migrations); iteracja może wymagać 1-2 prób |
| 3. Pint pre-commit | CaptainHook auto-install hook, `pint --dirty` blokuje brudny commit | `docker compose exec -T` w hook context bez TTY — testować na realnym commit, nie na manual call |
| 4. CI gate | `.github/workflows/ci.yml` z phpstan + codecept jobs zielonymi | `setup-php` v2 PHP 8.4 jeszcze RC — pinujemy `'8.4'` string; Postgres service container env musi matchować `.env.testing` co do bita |

**Prerequisites:** Docker compose działa (`docker compose ps` zwraca app + db Online); GitHub repo `bartkibilko/podworko` istnieje (sprawdzone, 14 issues #1-#14 utworzone); `gh` CLI authed (sprawdzone w poprzednich sessions).
**Estimated effort:** ~1 sesja po wszystkich 4 fazach, dispatched przez `/10x-implement project-tooling phase N` sekwencyjnie z manual gate między fazami.

## Open Risks & Assumptions

- **CI scope override** — user wybrał włączenie minimal CI YAML mimo że roadmap.md § Parked explicite odkłada CI ("odkładamy aż drugi/trzeci developer dołączy"). Risk: solo-MVP burnuje GH Actions minutes bez stakeholdera korzystającego z server-side gate. Akceptowane.
- **PHPStan level 5 = 0 errors założenie** — Laravel 13.8 + Larastan na świeżym scaffoldzie powinno być 0 errors, ale Larastan extension dla 13.8 może mieć regresje. Jeśli pojawią się errory framework-side: dopisujemy `excludePaths` lub inline `ignoreErrors`; nie tworzymy `phpstan-baseline.neon` (per coding-rules.md filozofia "level baseline → bump w osobnym PR").
- **`composer dump-autoload` po cleanup scaffold tests** — usunięcie `tests/TestCase.php` może wymagać `composer dump-autoload` dla PSR-4 mapy. Implementer pamięta po Phase 2 cleanup.
- **Brak `nunomaduro/collision` decyzji** — zostawiona implementatorowi w Phase 2.5 (verify czy używane przez tinker/pail przed `composer remove`).

## Success Criteria (Summary)

- Każda nowa zmiana w `app/` / `database/` / `tests/` jest gate-owana lokalnym phpstan + Pint hookiem + CI workflow przed merge.
- `/10x-implement` może wywoływać `vendor/bin/codecept run` jako automated verification step dla F-02 i kolejnych slice'ów bez dodatkowego setupu.
- Solo-developer może klonować repo + `composer install` + dostać działający quality gate stack zero manual configuration steps.
