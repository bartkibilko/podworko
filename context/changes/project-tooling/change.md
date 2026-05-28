---
change_id: project-tooling
roadmap_id: F-01
title: Project tooling — PHPStan/Larastan level 5 + Codeception 3 suites + Pint pre-commit + CI gate
status: implementing
created: 2026-05-28
updated: 2026-05-28
prerequisites: []
unlocks: [auth-scaffold-magic-link, domain-primitives, cost-settlement-single-payer]
---

# F-01: Project tooling

Foundation slice — wpina quality-gate tooling kompensujący `typed: false` Laravela: PHPStan/Larastan level 5, Codeception 3 suites (Unit / Functional / Acceptance), Pint pre-commit hook przez CaptainHook, plus minimal GitHub Actions CI gate.

Bezpośrednie konsekwencje dla następnych slice'ów: każdy zmiana w `app/`, `database/factories|seeders`, `tests/` przechodzi przez PHPStan level 5; testy domain dispatch przez `vendor/bin/codecept run`; commit blokuje styl-drift; PR-y nie merge'owalne bez zielonego CI.

Źródło prawdy decyzji: `context/foundation/coding-rules.md` § Tests + § Static analysis. Roadmap pozycja: `context/foundation/roadmap.md` § F-01.
