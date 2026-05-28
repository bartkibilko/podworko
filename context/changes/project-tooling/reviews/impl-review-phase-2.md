<!-- IMPL-REVIEW-REPORT -->
# Implementation Review: F-01 Project Tooling

- **Plan**: `context/changes/project-tooling/plan.md`
- **Scope**: Phase 2 of 4 (Codeception bootstrap)
- **Date**: 2026-05-28
- **Phase commit reviewed**: ea6c476
- **Verdict**: NEEDS ATTENTION → APPROVED (all 3 fixes applied)
- **Findings**: 0 critical | 1 warning | 2 observations

## Verdicts

| Dimension | Verdict |
|-----------|---------|
| Plan Adherence | PASS |
| Scope Discipline | PASS (3 pull-forwards documented in commit body: .env.testing, _generated exclude, .gitignore) |
| Safety & Quality | WARNING → PASS (after F1 + F2 fix) |
| Architecture | PASS |
| Pattern Consistency | PASS (no priors — foundation slice) |
| Success Criteria | PASS |

## Findings

### F1 — Hardcoded DB credentials w suite yml

- **Severity**: ⚠️ WARNING
- **Impact**: 🔬 HIGH — architectural stakes; portable lokalnie + CI
- **Dimension**: Safety & Quality
- **Location**: `tests/Functional.suite.yml:11-13`, `tests/Acceptance.suite.yml:13-15`
- **Detail**: Db DSN/user/password literal w suite yml. Lokalnie OK, ale P4 CI Postgres service container będzie używać innych credentials → connection refused.
- **Fix A ⭐ Applied**: Codeception `params: [.env.testing]` w `codeception.yml` + `%DB_HOST%` / `%DB_DATABASE%` / `%DB_USERNAME%` / `%DB_PASSWORD%` interpolation w obu suite yml.
- **Decision**: FIXED via Fix A

### F2 — .env.testing dzieli APP_KEY z .env

- **Severity**: 🔍 OBSERVATION
- **Impact**: 🏃 LOW
- **Dimension**: Safety & Quality
- **Location**: `.env.testing:3`
- **Detail**: APP_KEY skopiowany 1:1 z .env (dev). Laravel konwencja: separate test env key.
- **Fix Applied**: Wygenerowany nowy 32-byte base64 random key dedykowany testing env.
- **Decision**: FIXED

### F3 — Acceptance smoke uruchamia migracje

- **Severity**: 🔍 OBSERVATION
- **Impact**: 🏃 LOW
- **Dimension**: Architecture
- **Location**: `tests/Acceptance.suite.yml:11`
- **Detail**: `run_database_migrations: true` per-test daje multiplier overhead na 50+ Cest w F-03+. Smoke testuje tylko `/up` (DB-less).
- **Fix Applied**: Flipped do `false` + komentarz "re-enable when first domain migration lands (F-03)".
- **Decision**: FIXED

## Triage summary

```
Fixed via Fix A:  F1
Fixed now:        F2, F3
Skipped:          —
Accepted:         —
```

All 3 findings resolved. Post-fix `codecept run` → 3 tests, 3 assertions, 0 failures.
