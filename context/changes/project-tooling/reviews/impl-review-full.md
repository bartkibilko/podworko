<!-- IMPL-REVIEW-REPORT -->
# Implementation Review: F-01 Project Tooling (FULL PLAN)

- **Plan**: `context/changes/project-tooling/plan.md`
- **Scope**: All 4 phases
- **Date**: 2026-05-28
- **Commits reviewed**: ff38b39..1b6f640 (9 commits)
- **Verdict**: NEEDS ATTENTION → CLOSED (2 warnings + 3 observations recorded as backlog hardening, not blocking)
- **Findings**: 0 critical | 2 warnings | 3 observations

## Verdicts

| Dimension | Verdict |
|-----------|---------|
| Plan Adherence | PASS (3 intentional drifts documented in commit bodies) |
| Scope Discipline | PASS (extras justified: codecept-generated boilerplate + bundled lesson assets explicitly noted) |
| Safety & Quality | WARNING (F1 + F2 below) |
| Architecture | PASS |
| Pattern Consistency | PASS (coding-rules.md observed: strict_types, English code, final class, descriptive method names) |
| Success Criteria | PASS (local phpstan/codecept/pint green + CI run 26595181394 green) |

## Findings

### F1 — Workflow brakuje `permissions: contents: read`

- **Severity**: ⚠️ WARNING
- **Impact**: 🏃 LOW
- **Dimension**: Safety & Quality
- **Location**: `.github/workflows/ci.yml` (top level, no permissions block)
- **Detail**: GITHUB_TOKEN dziedziczy repo defaults — niektóre repo dają `contents: write`. F-01 CI to read-only quality gate, nie potrzebuje write access. Least-privilege violation.
- **Fix**: Add 2 lines: `permissions:\n  contents: read`
- **Decision**: BACKLOG (defer to security-pass / F-02 first PR commit when CI touched again)

### F2 — `sed -i` na trackowanym `.env.testing` w CI

- **Severity**: ⚠️ WARNING
- **Impact**: 🔎 MEDIUM
- **Dimension**: Reliability / CI hygiene
- **Location**: `.github/workflows/ci.yml:67`
- **Detail**: Sed mutuje tracked file `.env.testing` in-place w CI. Działa, ale: (a) artifact uploads zawierałyby CI-only DB_HOST=localhost (forensic confusion), (b) jeśli linia DB_HOST= usunięta, sed silent no-op + mylący codecept failure.
- **Fix A ⭐ Recommended**: Workflow `env: DB_HOST: localhost` na codecept job + `%env(DB_HOST)%` w suite yml zamiast `%DB_HOST%`. Brak tracked-file mutation.
- **Fix B**: Defer do F-02 — działa teraz.
- **Decision**: BACKLOG (Fix A) — recorded as recurring backlog item; defer do F-02 lub dedicated CI hygiene change

### F3 — `phpstan.neon` `(?)` suffix bez komentarza wyjaśniającego

- **Severity**: 🔍 OBSERVATION
- **Impact**: 🏃 LOW
- **Dimension**: Pattern
- **Location**: `phpstan.neon:13`
- **Detail**: `tests/Support/_generated (?)` — future maintainer może go usunąć. Bez komentarza wyjaśniającego rozumowanie się zatraca.
- **Fix**: Add `# (?) marks path optional — codecept build generates it; absent on fresh CI clone before codecept build step.`
- **Decision**: BACKLOG

### F4 — Hardcoded test DB creds w workflow YAML + .env.testing

- **Severity**: 🔍 OBSERVATION
- **Impact**: 🏃 LOW
- **Dimension**: Pattern
- **Location**: `.github/workflows/ci.yml:40-43` + `.env.testing`
- **Detail**: `podworko_user/podworko_123/podworko_test_db` zduplikowane w 2 plikach. Test-only credentials nie są secrets, ale rotacja jednego pozostawia drugi out-of-sync.
- **Fix**: Single workflow env: block + comment "test-only, mirrors .env.testing". Naturalnie rozwiąże się przy F2 Fix A (workflow env: block już potrzebny).
- **Decision**: BACKLOG (auto-resolves when F2 Fix A applied)

### F5 — Action floating major tags (`@v4`, `@v2`)

- **Severity**: 🔍 OBSERVATION
- **Impact**: 🏃 LOW
- **Dimension**: CI-specific
- **Location**: `.github/workflows/ci.yml` (actions/checkout@v4, shivammathur/setup-php@v2)
- **Detail**: Floating major tags vs SHA pinning. Trade-off: easier maintenance vs supply-chain attack vector. GitHub hardening guide rekomenduje `@<sha>`. Plus deprecation warning Node.js 20 → 24 transition Sep 2026.
- **Fix**: Defer to security hardening pass; check Node.js 24 readiness przed June 2026.
- **Decision**: BACKLOG (security-pass scope)

## Triage summary

```
Fixed:     —                       (0)
Backlog:   F1, F2, F3, F4, F5      (5)
Skipped:   —                       (0)
```

Wszystkie 5 findings przeniesione do backlog hardening. F-01 zamknięta jako implemented + CI green. Hardening może wpaść do F-02 first PR (kiedy CI workflow będzie ponownie touched) lub dedicated `ci-hygiene` change.

## Closure note

F-01 cykl: 4 phases × średnio 2 commits (impl + follow-up) + 1 epilogue = 9 commits. Pierwszy zielony CI run: 26595025048 (po dwóch P4 follow-up iteracjach). Final epilogue run: 26595181394 (success). All local quality gates (phpstan level 5, codecept 3 suites, pint --test PSR-12) zielone.

Wszystkie next slice'y (F-02 auth scaffold, F-03 domain primitives, S-01..S-11) startują z działającym local + CI gate.
