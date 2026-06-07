<!-- IMPL-REVIEW-REPORT -->
# Implementation Review: S-01 Founder creates neighbourhood

- **Plan**: `context/changes/founder-creates-neighbourhood/plan.md`
- **Scope**: 2 phases (implementation complete; 2.5 CI pending push)
- **Date**: 2026-06-07
- **Commits reviewed**: 1885166, 6134de3
- **Verdict**: NEEDS ATTENTION → TRIAGED (3 fixed, 1 accepted-as-risk, 2 skipped)
- **Findings**: 0 critical | 4 warnings | 2 observations
- **Triage**: Fixed F1, F2, F5 · Accepted-as-risk F3 (TOCTOU, MVP) · Skipped F4 (flake), F6 (suite placement)

## Verdicts

| Dimension | Verdict |
|-----------|---------|
| Plan Adherence | PASS |
| Scope Discipline | PASS |
| Safety & Quality | WARNING |
| Architecture | PASS |
| Pattern Consistency | WARNING |
| Success Criteria | WARNING |

## Findings

### F1 — User.php without declare(strict_types=1)

- **Severity**: ⚠️ WARNING
- **Impact**: 🏃 LOW — quick decision; fix is obvious and narrowly scoped
- **Dimension**: Pattern Consistency
- **Location**: app/Models/User.php:1-3
- **Detail**: Violates the zero-day coding-rule (strict_types in every .php under app/). Pre-existing scaffold, but S-01 touched the file (added memberships()), so the fix is in-scope and cheap.
- **Fix**: Add `declare(strict_types=1);` as the first directive.
- **Decision**: FIXED (added declare(strict_types=1) to User.php)

### F2 — Length contract: regex {1,6} vs generator always-6

- **Severity**: ⚠️ WARNING
- **Impact**: 🏃 LOW — quick decision; fix is obvious and narrowly scoped
- **Dimension**: Safety & Quality
- **Location**: app/Http/Controllers/NeighbourhoodController.php:42
- **Detail**: store() validates access_code with `^[A-Z0-9]{1,6}$` but the generator always produces 6 chars. Harmless but a latent inconsistency.
- **Fix**: Tighten the regex to `^[A-Z0-9]{6}$`.
- **Decision**: FIXED (regex tightened to {6})

### F3 — TOCTOU on access_code (preview→store) → 500

- **Severity**: ⚠️ WARNING
- **Impact**: 🔎 MEDIUM — real tradeoff; pause to reason through it
- **Dimension**: Safety & Quality (Reliability)
- **Location**: app/Http/Controllers/NeighbourhoodController.php:42 + 45-58
- **Detail**: Between the unique validation and create() another request could insert the same code → DB unique violation → unhandled QueryException (no try/catch rule) → 500, losing input. Probability negligible (6-char space, solo MVP).
- **Fix A ⭐ Recommended**: Accept as MVP risk (document).
  - Strength: no-try/catch rule precludes a clean catch→re-preview; user can just regenerate; collision ~never at this scale.
  - Tradeoff: theoretical 500 under an extreme race.
  - Confidence: HIGH — large keyspace, tiny scale.
  - Blind spot: no real concurrency to observe.
- **Fix B**: Catch the unique violation and re-render preview.
  - Strength: graceful UX instead of 500.
  - Tradeoff: bends the "no try/catch in app code" rule.
  - Confidence: MED — needs a coding-rules exception.
  - Blind spot: where to place the try/catch boundary.
- **Decision**: ACCEPTED-AS-RISK via Fix A (MVP scale; no-try/catch rule kept; user can regenerate; collision negligible in a 6-char keyspace at solo scale)

### F4 — Regenerate test is flaky

- **Severity**: ⚠️ WARNING
- **Impact**: 🏃 LOW — quick decision; fix is obvious and narrowly scoped
- **Dimension**: Success Criteria (test quality)
- **Location**: tests/Acceptance/CreateNeighbourhoodCest.php:42-54
- **Detail**: Asserts two random codes differ; with a 3-char random suffix (~1/46656) it will occasionally fail with no real defect.
- **Fix**: Make deterministic (loop regenerations + assert ≥1 differs, or mock Str::random), or accept the negligible flake.
- **Decision**: SKIPPED (accept negligible ~1/46k flake)

### F5 — Weak membership assertion in Acceptance

- **Severity**: 🔍 OBSERVATION
- **Impact**: 🏃 LOW — quick decision; fix is obvious and narrowly scoped
- **Dimension**: Success Criteria (test quality)
- **Location**: tests/Acceptance/CreateNeighbourhoodCest.php:36
- **Detail**: seeRecord('memberships', user_id+role) doesn't assert neighbourhood_id (FK to the created neighbourhood) or household_id=null — would pass even with wrong linkage.
- **Fix**: Grab the neighbourhood id and add neighbourhood_id + household_id=null to the assertion.
- **Decision**: FIXED (grabRecord neighbourhood; assert membership neighbourhood_id + household_id=null)

### F6 — Store coverage in Acceptance instead of Functional

- **Severity**: 🔍 OBSERVATION
- **Impact**: 🏃 LOW — quick decision; fix is obvious and narrowly scoped
- **Dimension**: Plan Adherence
- **Location**: tests/Functional/NeighbourhoodAccessCodeCest.php
- **Detail**: Plan listed transactional store coverage under Functional; realized in Acceptance instead (behaviour verified, different suite).
- **Fix**: Accept (covered) or add a Functional store test.
- **Decision**: SKIPPED (behaviour covered end-to-end in Acceptance; only suite placement differs)
