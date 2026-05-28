# F-01: Project tooling Implementation Plan

## Overview

Wpięcie quality-gate toolingu (PHPStan/Larastan level 5, Codeception 3 suites, Pint pre-commit przez CaptainHook, minimal GitHub Actions CI) jako pierwsza foundation Podwórka. Kompensata `typed: false` Laravela z `tech-stack.md` + ustanowienie deterministycznej ścieżki weryfikacji dla wszystkich następnych slice'ów (F-02 auth scaffold zaczyna z niezerowym test coverage).

## Current State Analysis

- `composer.json`: Laravel 13.8 + PHP ^8.3. Dev deps obejmują Pint 1.27, PHPUnit 12.5, Mockery, Collision, Faker, Pail, Pao — czyli mainstream Laravel scaffold.
- `phpstan.neon`, `captainhook.json`, `codeception.yml`: brak.
- `tests/`: scaffoldowy `tests/TestCase.php` + `tests/Feature/` + `tests/Unit/` (Laravel default — do usunięcia per `coding-rules.md` § Tests).
- `composer.json scripts.test`: wrapper na `php artisan test` (PHPUnit) — sprzeczny z `coding-rules.md` "nie używamy php artisan test".
- `.git/hooks/pre-commit`: brak.
- `.github/workflows/`: katalog nie istnieje.
- `vendor/bin/pint` działa (Pint już zainstalowany), ale brak konfiguracji bo per `coding-rules.md` "brak personal style overrides w pint.json" — PSR-12 default jest zamierzony.

## Desired End State

Po zakończeniu F-01:

1. `docker compose exec app vendor/bin/phpstan analyse --memory-limit=512M` zwraca **0 errors** na obecnym scaffoldzie (z config'em paths: `app`, `database/factories`, `database/seeders`, `tests`).
2. `docker compose exec app vendor/bin/codecept run` startuje, wykrywa 3 suites (Unit / Functional / Acceptance) z `cleanup: true` na bazie, raportuje "0 tests run" lub jeden smoke test per suite.
3. `git commit` z brudnym formatowaniem PHP zostaje zablokowany przez CaptainHook → `vendor/bin/pint --dirty` autofix → użytkownik widzi modyfikacje i re-stage'uje.
4. Push do brancha + PR triggeruje GitHub Actions workflow `ci.yml` z dwoma jobami: `phpstan` (zielony) + `codecept` (zielony). PR-y czerwone na którymkolwiek = nie merge'owalne (status check required).
5. PHPUnit już nie jest direct dep (zostaje transitive przez codeception/codeception, niewidoczny w `require-dev` listing).
6. `composer test` (alias z `composer.json scripts.test`) wywołuje `vendor/bin/codecept run`.

### Key Discoveries:

- **`coding-rules.md` jest autorytatywnym briefem F-01** (`context/foundation/coding-rules.md:64-119`). Większość decyzji architektonicznych już zapadła: framework testowy (Codeception), level PHPStan (5), paths, baseline strategy (level 5 baseline → bump w osobnych PR), cleanup Laravel-default scaffold.
- **PHPUnit jest transitive dep Codeception** (`coding-rules.md:65`) — można usunąć z `require-dev` bo `codecept` przyciągnie go i tak.
- **Pre-commit scope jest świadomie minimalny** (per user decyzja: Pint tylko, nie PHPStan/Codeception w hookie) bo PHPStan/Codeception lecą w CI, a długi commit prowadzi do `--no-verify`.
- **Roadmap Parked-section explicite odkłada CI** (`roadmap.md:293`) — user override w tym F-01 dodał minimal CI z powrotem do scope (świadomy override, notowany w `## Open Risks & Assumptions` plan-brief).
- **`tests/TestCase.php` Laravel default** dziedziczy `Illuminate\Foundation\Testing\TestCase` — Codeception ma własny TestCase via `\Codeception\Test\Unit` — Laravel-default plik usuwamy razem z `tests/Feature/` i `tests/Unit/ExampleTest.php`.

## What We're NOT Doing

- **Migracja istniejących testów domain-owych** — żadnych nie ma, nie ma co migrować.
- **Pint personal-style overrides** — `pint.json` świadomie NIE tworzymy, PSR-12 default.
- **PHPStan level >5** — level 5 baseline jest deklarowanym wyborem (`coding-rules.md:119` "Level 5 baseline → podnosimy do 6/7/8 gdy codebase stabilny").
- **PHPStan baseline file** (`phpstan-baseline.neon`) — zakładamy że scaffold Laravela 13.8 daje 0 errors na level 5 dla `app/` + `database/` + `tests/`. Jeśli pojawią się false-positives z framework — dopisujemy `excludePaths` lub `ignoreErrors` inline, nie tworzymy baseline.
- **Pre-commit PHPStan / Codeception** — świadomie tylko Pint per `Hook scope` decision; PHPStan/Codeception lecą w CI.
- **Husky / lint-staged / shell-script hook** — odrzucone na rzecz CaptainHook (composer-native, brak Node deps).
- **Dependency caching w CI** (composer-cache action, Docker layer cache) — initial workflow bez cache; optymalizacja w follow-up gdy CI uciążliwy.
- **Branch protection rules** w GitHub — wymagają user-side click w settings UI; lista required checks zostawiona jako manual step w post-F-01 follow-up note.
- **Linter Blade** (np. `tighten/duster` lub `prettier-plugin-blade`) — Blade w obecnym repo to tylko `welcome.blade.php`, niewspółmierne do scope F-01.

## Implementation Approach

**Sekwencja**: 4 fazy, każda osobny commit, każda zostawia projekt w runnable state.

Phase 1 (PHPStan) jest **najmniej ryzykowna** i daje pierwszy quality signal. Phase 2 (Codeception) ma największy diff (codecept bootstrap generuje sporo plików) i potencjalne tarcie z PHPUnit removal — robimy ją drugą żeby PHPStan był już w miejscu jako sanity-check (PHPStan przejdzie po-codecept potwierdzi że nic nie poszło źle w Eloquencie/configu). Phase 3 (Pint + CaptainHook) jest izolowana — nie dotyka tests/static. Phase 4 (CI) konsumuje wszystkie 3 poprzednie — workflow YAML musi znać commands `vendor/bin/phpstan` i `vendor/bin/codecept`.

**Wszystkie composer + artisan + codecept commands** lecą przez `docker compose exec app` per `docker-rules.md`. Hook CaptainHook też — żeby uniknąć desyncu PHP version między host (system php) a kontenerem (8.4-fpm).

## Critical Implementation Details

- **CaptainHook hook musi działać przez `docker compose exec`** — host machines (macOS) mają zwykle inną wersję PHP niż kontener. `captainhook.json` `pre-commit.actions[].action` ustawiamy na `docker compose exec -T app vendor/bin/pint --dirty` (flag `-T` wyłącza TTY allocation, niezbędne w hook context bez interaktywnego shell-a). Jeśli docker-compose niedostępne (np. CI clone) hook musi fail-fast z wyraźnym komunikatem, nie próbować lokalnego PHP fallback.
- **Codeception `cleanup: true` w Acceptance/Functional** wymaga że suite config wskazuje na transactional rollback (`Db` module z `cleanup: true`) — domyślne `codecept bootstrap` generuje to dla Functional, ale Acceptance config trzeba ręcznie dostroić bo Acceptance Cest w Laravel używa `WebDriver`/`Laravel` module.
- **CI Postgres service** — workflow musi spinować Postgres 17 service container z tym samym schemą credentials co lokalny docker-compose, żeby migrations na CI używały tego samego connection string. Alternatywa: SQLite in-memory — odrzucamy bo `coding-rules.md` § Migrations zakłada bigint + Postgres-specific types które na SQLite zachowują się inaczej.

## Phase 1: Static analysis (PHPStan + Larastan)

### Overview

Instalacja PHPStan + Larastan dev-deps, `phpstan.neon` w root per `coding-rules.md:100-115`, weryfikacja że level 5 daje 0 errors na scaffoldzie, dodanie `composer phpstan` script alias.

### Changes Required:

#### 1. Composer dev dependencies

**File**: `composer.json`

**Intent**: Dodaj `phpstan/phpstan` i `larastan/larastan` jako require-dev przez `composer require --dev`. Larastan jest extension Laravel-aware (znajduje magiczne `Model::all()`, container resolutions, Eloquent relationships).

**Contract**: Sekcja `require-dev` zyskuje 2 wpisy; `composer.lock` aktualizowany. Polecenie: `docker compose exec app composer require --dev phpstan/phpstan larastan/larastan`.

#### 2. PHPStan configuration

**File**: `phpstan.neon`

**Intent**: Konfiguracja level 5 z paths objętymi analizą oraz inkluzją extension Larastan. Skopiuj treść z `coding-rules.md:102-115` 1:1 (już zwalidowana decyzja).

**Contract**: NEON file w root. Pola: `includes` → `vendor/larastan/larastan/extension.neon`. `parameters.level: 5`, `parameters.paths: [app, database/factories, database/seeders, tests]`, `parameters.excludePaths: [bootstrap/cache]`.

#### 3. Composer script alias

**File**: `composer.json`

**Intent**: Dodaj `scripts.phpstan` alias żeby `composer phpstan` wywołało analizę z poprawnymi flagami; ułatwia rozwiązywanie z CI YAML i lokalnego dev.

**Contract**: `scripts.phpstan: ["@php vendor/bin/phpstan analyse --memory-limit=512M --no-progress"]` (lub bez `@php` jeśli `composer run` w docker konsystentniej rozwiązuje binary).

### Success Criteria:

#### Automated Verification:

- `composer require` przeszło bez konfliktów: brak `php composer.phar` errors.
- `phpstan.neon` istnieje w root: `test -f phpstan.neon`.
- PHPStan analiza zwraca exit 0: `docker compose exec app vendor/bin/phpstan analyse --memory-limit=512M`.
- `composer phpstan` script działa: `docker compose exec app composer phpstan`.

#### Manual Verification:

- Output PHPStan zawiera `[OK] No errors`.
- `cat phpstan.neon` matchuje `coding-rules.md:102-115` (no drift).
- `composer show --installed | grep -E 'phpstan|larastan'` listuje obie paczki w odpowiednich wersjach.

**Implementation Note**: Po wszystkich automated checks zatrzymaj się i poczekaj na manualne potwierdzenie od człowieka. Phase blocks używają `- ` bullets, checkbox-y są w `## Progress`.

---

## Phase 2: Testing framework (Codeception)

### Overview

Instalacja Codeception bundle (codeception/codeception + module-laravel + module-db + module-asserts), bootstrap 3 suites, cleanup Laravel-default test scaffold, deinstalacja PHPUnit jako direct dev-dep, repoint `composer test`.

### Changes Required:

#### 1. Composer dev dependencies — Codeception bundle

**File**: `composer.json`

**Intent**: Dodaj Codeception core + 3 moduły dispatch (laravel dla framework boot, db dla `cleanup: true` rollback, asserts dla `$I->assertEquals` etc.) jako require-dev.

**Contract**: `composer require --dev codeception/codeception codeception/module-laravel codeception/module-db codeception/module-asserts`. Sekcja `require-dev` zyskuje 4 wpisy; `composer.lock` aktualizowany.

#### 2. Codeception bootstrap

**File**: `codeception.yml` + `tests/Unit.suite.yml` + `tests/Functional.suite.yml` + `tests/Acceptance.suite.yml` + `tests/_support/` (auto-generated)

**Intent**: Uruchom `codecept bootstrap` w kontenerze; wygeneruje root config + 3 suite configs + helper directories. Następnie dostosuj suite configs do `coding-rules.md` § Tests konwencji: każda suite z `cleanup: true` w Db module, Functional ma `Laravel` module dla domain-without-HTTP, Acceptance dispatch przez Laravel module (PHPBrowser-style, bez WebDriver — Selenium overkill dla MVP).

**Contract**:
- `codeception.yml` root: paths `tests`, `actor: Tester`, `bootstrap: _bootstrap.php`.
- `tests/Unit.suite.yml`: modules `Asserts`, bez Db/Laravel (pure PHP unit).
- `tests/Functional.suite.yml`: modules `Asserts`, `Db: cleanup: true`, `Laravel`.
- `tests/Acceptance.suite.yml`: modules `Asserts`, `Db: cleanup: true`, `Laravel: run_database_migrations: true`.

#### 3. Cleanup Laravel-default test scaffold

**File**: `tests/Feature/ExampleTest.php` + `tests/Unit/ExampleTest.php` + `tests/TestCase.php`

**Intent**: Usuń trzy pliki scaffoldu per `coding-rules.md:86`. Codeception ma własny `_support/Helper/*.php` + `*Cest.php` convention; Laravel-default `TestCase` nie jest używany.

**Contract**: 3 plików `git rm`. Po cleanup `tests/` zawiera tylko: `_support/`, `Unit.suite.yml`, `Functional.suite.yml`, `Acceptance.suite.yml`, `_bootstrap.php`, plus directory placeholders na Cest classes (`tests/Acceptance/`, `tests/Functional/`, `tests/Unit/`).

#### 4. Smoke Cest per suite

**File**: `tests/Unit/SmokeCest.php` + `tests/Functional/SmokeCest.php` + `tests/Acceptance/SmokeCest.php`

**Intent**: Po jednym trywialnym teście per suite żeby `codecept run` raportował realne "3 tests run, 0 failures" zamiast "0 tests" (CI gate w Phase 4 mógłby false-positive zielony na pustym output).

**Contract**: Każdy Cest ma jedną metodę `smoke<Suite>Setup(<Tester> $I)` z jednym `$I->assertTrue(true)` lub equiv. Konwencja nazewnictwa per `coding-rules.md:82` (camelCase opisowy, nie `testXxx`).

#### 5. Deinstalacja PHPUnit jako direct dep

**File**: `composer.json`

**Intent**: Usuń `phpunit/phpunit` z `require-dev` — Codeception przyciągnie go jako transitive. Również usuń `nunomaduro/collision` jeśli był tylko dla `artisan test` formatting (Codeception ma własny output) — sprawdź czy używane gdzie indziej zanim usuniesz.

**Contract**: `composer remove --dev phpunit/phpunit`. Verify że `vendor/phpunit/phpunit` nadal istnieje (transitive z codeception/codeception). `nunomaduro/collision` — keep jeśli używane przez `php artisan` formatery (tinker/pail), remove inaczej.

#### 6. Repoint composer test alias

**File**: `composer.json`

**Intent**: Zmień `scripts.test` z wrappera `@php artisan test` na `@php vendor/bin/codecept run`. Per `coding-rules.md:88` "artisan test nie używamy".

**Contract**: `scripts.test: ["@php artisan config:clear --ansi", "@php vendor/bin/codecept run"]` — pierwszy step zapewnia że config cache nie pochodzi z innego ENV (Codeception może mieć `APP_ENV=testing` z innym DB connection).

### Success Criteria:

#### Automated Verification:

- Codeception zainstalowany: `docker compose exec app vendor/bin/codecept --version` zwraca version string.
- Suite configs istnieją: `test -f tests/Unit.suite.yml && test -f tests/Functional.suite.yml && test -f tests/Acceptance.suite.yml`.
- Laravel-default tests usunięte: `test ! -f tests/TestCase.php && test ! -f tests/Feature/ExampleTest.php && test ! -f tests/Unit/ExampleTest.php`.
- `codecept run` przeszło zielono: `docker compose exec app vendor/bin/codecept run` exit 0.
- `composer test` wywołuje codecept: `docker compose exec app composer test` exit 0.
- PHPUnit nie jest direct dep: `composer show --installed --direct | grep phpunit/phpunit` zwraca empty (nadal transitive — to OK).
- PHPStan z Phase 1 nadal zielony po cleanup tests: `docker compose exec app vendor/bin/phpstan analyse --memory-limit=512M`.

#### Manual Verification:

- Output `codecept run` listuje 3 suites i 3 passed smoke tests.
- `tests/` ma czystą strukturę bez Laravel-default residuów.
- `composer test` w terminalu produkuje czytelny Codeception output (nie PHPUnit-style).

**Implementation Note**: Po wszystkich automated checks zatrzymaj się i poczekaj na manualne potwierdzenie od człowieka.

---

## Phase 3: Code style automation (Pint + CaptainHook pre-commit)

### Overview

Instalacja CaptainHook + composer plugin (auto-install hook po `composer install`), konfiguracja `captainhook.json` z pre-commit `pint --dirty`, weryfikacja że hook blokuje brudny commit.

### Changes Required:

#### 1. Composer dev dependencies — CaptainHook

**File**: `composer.json`

**Intent**: Dodaj `captainhook/captainhook` (engine) + `captainhook/plugin-composer` (auto-instaluje hooki w `.git/hooks/` po każdym `composer install`/`update` — zero manual step dla freshly-cloned repo).

**Contract**: `composer require --dev captainhook/captainhook captainhook/plugin-composer`. `extra.captainhook.config` w `composer.json` wskazuje plugin gdzie szukać `captainhook.json` (domyślnie root, więc dodatkowa konfig opcjonalna).

#### 2. CaptainHook configuration

**File**: `captainhook.json`

**Intent**: Konfiguracja pre-commit hook który wywołuje `vendor/bin/pint --dirty` przez `docker compose exec -T app`. Ograniczenie do `--dirty` (tylko staged files) zapewnia że hook startuje <1s na typowym commit.

**Contract**: JSON z polem `pre-commit.enabled: true`, `pre-commit.actions: [{action: "docker compose exec -T app vendor/bin/pint --dirty"}]`. Jeśli `docker compose` niedostępne (np. user na hosts machine bez kontenerów up) — CaptainHook fail-fast z komunikatem; brak local PHP fallback.

#### 3. Pint dry-run sanity check

**File**: (no file — verification step)

**Intent**: Przed włączeniem hooka uruchom `vendor/bin/pint --test` żeby sprawdzić ile plików scaffold-Laravel-13 wymaga już teraz autofix. Jeśli >0 — zrób initial pass `vendor/bin/pint` żeby zatwierdzić styl baseline w osobnym commit (nie miesza się ze scope F-01 install).

**Contract**: Wynik `pint --test` raportowany jako Manual verification item; decyzja czy preliminary cleanup commit potrzebny.

### Success Criteria:

#### Automated Verification:

- CaptainHook zainstalowany: `docker compose exec app vendor/bin/captainhook --version` zwraca version.
- `captainhook.json` istnieje: `test -f captainhook.json`.
- Hook wpięty w git: `test -x .git/hooks/pre-commit` po `composer install` (plugin-composer powinien zainstalować).
- Pint baseline: `docker compose exec app vendor/bin/pint --test` exit 0 (zakłada wcześniejszy initial pass jeśli scaffold brudny).

#### Manual Verification:

- Test pozytywny: `echo "<?php  echo 'a';" > app/Tmp.php; git add app/Tmp.php; git commit -m test` → hook autofix-uje podwójną spację → commit aborted z komunikatem "Pint applied changes, please re-stage".
- Test negatywny: bez brudnego diffu commit przechodzi pod 1s.
- `cat captainhook.json` zawiera `docker compose exec -T app vendor/bin/pint --dirty` literal command.

**Implementation Note**: Po wszystkich automated checks zatrzymaj się i poczekaj na manualne potwierdzenie od człowieka.

---

## Phase 4: CI gate (GitHub Actions)

### Overview

`.github/workflows/ci.yml` z dwoma jobami (phpstan + codecept) trigger na `pull_request` i `push: branches: [master]`. Postgres 17 service container dla Codeception. Workflow status checks oznaczone jako "required" w GH branch protection (manual step user-side).

### Changes Required:

#### 1. GitHub Actions workflow file

**File**: `.github/workflows/ci.yml`

**Intent**: YAML z dwoma jobami `phpstan` i `codecept` które oba zaczynają od `setup-php` (PHP 8.4 + extensions: pdo_pgsql, bcmath, intl, zip, gd) + `composer install --no-progress --prefer-dist`. `phpstan` job uruchamia `vendor/bin/phpstan analyse --memory-limit=512M`. `codecept` job dodatkowo spinują Postgres 17 service container, ustawia `.env` z DB credentials matching service, uruchamia migrate + `vendor/bin/codecept run`.

**Contract**: Triggers `on: pull_request: {branches: [master]}` + `on: push: {branches: [master]}`. Jobs używają `ubuntu-latest`. `setup-php` action wersja v2 z `php-version: 8.4`. Postgres service: `image: postgres:17`, env `POSTGRES_USER/DB/PASSWORD` matchujące `.env.testing`. Brak Docker-in-Docker — instalujemy PHP natywnie na runnerze (szybsze niż `docker compose` w CI).

#### 2. `.env.testing` dla CI

**File**: `.env.testing`

**Intent**: Plik ENV który CI workflow kopiuje do `.env` przed migrate; matchuje credentials service container Postgres. APP_ENV=testing, DB_CONNECTION=pgsql, host=localhost (service exposed jako TCP).

**Contract**: Standardowy Laravel `.env` format. `DB_HOST=localhost`, `DB_PORT=5432`, `DB_DATABASE=podworko_test`, `DB_USERNAME=postgres`, `DB_PASSWORD=postgres`, `APP_KEY=base64:...` (dummy stable key wygenerowany raz).

#### 3. Brak Docker-in-Docker w CI

**File**: `docker/railway/*` (no change — just verification)

**Intent**: Production Dockerfile (`docker/railway/Dockerfile`) NIE jest używany w CI — CI uruchamia PHP natywnie na runnerze przez `shivammathur/setup-php`. To wybór performance (Docker build w CI jest powolne, layer cache w GH Actions nieperfekcyjny).

**Contract**: Workflow YAML nie wywołuje `docker build` ani `docker compose`.

### Success Criteria:

#### Automated Verification:

- Workflow file istnieje: `test -f .github/workflows/ci.yml`.
- YAML syntactically valid: `yamllint .github/workflows/ci.yml` (lub equiv) bez errors.
- Push do brancha + open PR triggeruje workflow: GitHub Actions UI pokazuje 2 jobs running.
- Oba joby zielone: `gh run list --workflow=ci.yml --limit=1` raportuje `success`.
- `gh pr checks <PR>` listuje `phpstan` i `codecept` jako passed.

#### Manual Verification:

- Symulacja czerwonego CI: dodaj intencjonalnie błąd PHPStan (np. `int $x = "string";` gdzieś w app/) push → PR pokazuje czerwony check → revert → zielony.
- Symulacja czerwonego CI dla Codeception: dodaj failujący assert w `tests/Unit/SmokeCest.php` push → czerwony codecept job → revert.
- Pierwszy zielony PR-merge ma wpisany `phpstan` i `codecept` w "all checks passed".
- Branch protection rules (GitHub Settings → Branches → master): manualnie oznacz `phpstan` i `codecept` jako required status checks. Notowane jako post-F-01 manual TODO, NIE blokujące zamknięcia changeu.

**Implementation Note**: Po wszystkich automated checks zatrzymaj się i poczekaj na manualne potwierdzenie od człowieka.

---

## Testing Strategy

### Unit Tests:

- F-01 sam nie produkuje domain logiki — testów do napisania nie ma. Smoke Cest per suite (Phase 2) jest test infrastructure smoke, nie domain test.

### Integration Tests:

- Phase 2 smoke Cest weryfikuje że Codeception bootstrap-uje Laravel framework correctly (Functional suite Laravel module dispatch dispatch test bez 500 from container).

### Manual Testing Steps:

1. **Po Phase 1**: uruchom `docker compose exec app vendor/bin/phpstan analyse` i potwierdź `[OK] No errors`.
2. **Po Phase 2**: uruchom `docker compose exec app vendor/bin/codecept run`, potwierdź 3 suites + 3 smoke tests passed; uruchom `composer test`, potwierdź ten sam wynik.
3. **Po Phase 3**: zrób intentionally-bad commit (echo dirty PHP do pliku, stage, commit), potwierdź że hook autofix-uje; usuń plik, commit pusty diff, potwierdź że hook nie blokuje.
4. **Po Phase 4**: push branch z testową zmianą, otwórz PR, czekaj na CI; verify że dwa joby wykonują się + zielone; merge → potwierdź że master też ma zielony workflow.

## Performance Considerations

- Pre-commit hook musi być <1s żeby nie generować presji `--no-verify`. `pint --dirty` ogranicza analizę do staged files → typowy commit <500ms.
- CI workflow nie cache'uje composer ani PHPStan w v1 — initial build ~2-3 min. Optymalizacja (composer-cache action, `--composer-cache-key`) w follow-up gdy CI uciążliwy.
- `--memory-limit=512M` na PHPStan wystarczy dla obecnego rozmiaru codebase (PHPStan defaults na 256M crashują na większych projektach); 512M jest bezpieczna granica.

## Migration Notes

- `php artisan test` przestaje być wspierane (zostaje fizycznie dostępne bo Laravel scaffold ma `tests/` że PHPUnit traktuje, ale `tests/Feature` i `tests/Unit/ExampleTest.php` usunięte → `artisan test` zwróci "No tests executed"). Nie usuwamy explicite `console.test` z `bootstrap/app.php` bo to Laravel core.
- Jeśli na CI runner-ze `setup-php` v2 wybiera PHP 8.4-RC zamiast stable — pinujemy `php-version: '8.4'` jawnie (cytaty string, nie liczba).

## References

- Roadmap: `context/foundation/roadmap.md` § F-01
- Coding rules (autorytatywny brief): `context/foundation/coding-rules.md` § Tests + § Static analysis
- Docker rules: `context/foundation/docker-rules.md` (UID/GID guard + `docker compose exec` convention)
- Git rules: `context/foundation/git-rules.md` (commit dopiero po manualnej weryfikacji)
- Tech-stack rationale (compensation `typed: false`): `context/foundation/tech-stack.md`

## Progress

> Convention: `- [ ]` pending, `- [x]` done. Append ` — <commit sha>` when a step lands. Do not rename step titles. See `references/progress-format.md`.

### Phase 1: Static analysis (PHPStan + Larastan)

#### Automated

- [x] 1.1 `composer require` przeszło bez konfliktów: brak `php composer.phar` errors — ff38b39
- [x] 1.2 `phpstan.neon` istnieje w root: `test -f phpstan.neon` — ff38b39
- [x] 1.3 PHPStan analiza zwraca exit 0: `docker compose exec app vendor/bin/phpstan analyse --memory-limit=512M` — ff38b39
- [x] 1.4 `composer phpstan` script działa: `docker compose exec app composer phpstan` — ff38b39

#### Manual

- [x] 1.5 Output PHPStan zawiera `[OK] No errors` — ff38b39
- [x] 1.6 `cat phpstan.neon` matchuje `coding-rules.md:102-115` (no drift) — ff38b39
- [x] 1.7 `composer show --installed | grep -E 'phpstan|larastan'` listuje obie paczki — ff38b39

### Phase 2: Testing framework (Codeception)

#### Automated

- [x] 2.1 Codeception zainstalowany: `vendor/bin/codecept --version` zwraca version string — ea6c476
- [x] 2.2 Suite configs istnieją: `test -f tests/Unit.suite.yml && test -f tests/Functional.suite.yml && test -f tests/Acceptance.suite.yml` — ea6c476
- [x] 2.3 Laravel-default tests usunięte: `test ! -f tests/TestCase.php && test ! -f tests/Feature/ExampleTest.php && test ! -f tests/Unit/ExampleTest.php` — ea6c476
- [x] 2.4 `codecept run` przeszło zielono: exit 0 — ea6c476
- [x] 2.5 `composer test` wywołuje codecept: exit 0 — ea6c476
- [x] 2.6 PHPUnit nie jest direct dep: `composer show --installed --direct | grep phpunit/phpunit` empty — ea6c476
- [x] 2.7 PHPStan z Phase 1 nadal zielony po cleanup tests — ea6c476

#### Manual

- [x] 2.8 Output `codecept run` listuje 3 suites i 3 passed smoke tests — ea6c476
- [x] 2.9 `tests/` ma czystą strukturę bez Laravel-default residuów — ea6c476
- [x] 2.10 `composer test` w terminalu produkuje czytelny Codeception output — ea6c476

### Phase 3: Code style automation (Pint + CaptainHook pre-commit)

#### Automated

- [ ] 3.1 CaptainHook zainstalowany: `vendor/bin/captainhook --version` zwraca version
- [ ] 3.2 `captainhook.json` istnieje
- [ ] 3.3 Hook wpięty w git: `test -x .git/hooks/pre-commit`
- [ ] 3.4 Pint baseline: `vendor/bin/pint --test` exit 0

#### Manual

- [ ] 3.5 Test pozytywny: brudny commit autofix-owany przez hook + commit aborted dla re-stage
- [ ] 3.6 Test negatywny: czysty commit przechodzi pod 1s
- [ ] 3.7 `cat captainhook.json` zawiera `docker compose exec -T app vendor/bin/pint --dirty` literal

### Phase 4: CI gate (GitHub Actions)

#### Automated

- [ ] 4.1 Workflow file istnieje: `test -f .github/workflows/ci.yml`
- [ ] 4.2 YAML syntactically valid (yamllint lub equiv)
- [ ] 4.3 Push do brancha + open PR triggeruje workflow (2 jobs running)
- [ ] 4.4 Oba joby zielone: `gh run list --workflow=ci.yml --limit=1` raportuje `success`
- [ ] 4.5 `gh pr checks <PR>` listuje `phpstan` i `codecept` jako passed

#### Manual

- [ ] 4.6 Symulacja czerwonego PHPStan: błąd push → PR czerwony → revert → zielony
- [ ] 4.7 Symulacja czerwonego Codeception: failujący assert push → czerwony → revert
- [ ] 4.8 Pierwszy zielony PR-merge ma `phpstan` i `codecept` w "all checks passed"
- [ ] 4.9 Branch protection rules manualnie oznaczone (post-F-01 TODO, nie blokuje closure)
