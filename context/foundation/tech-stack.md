---
starter_id: laravel
package_manager: composer
project_name: podworko
hints:
  language_family: php
  team_size: solo
  deployment_target: railway
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
---

## Why this stack

Solo developer budujący after-hours przez 12 tygodni multi-tenant web-appkę dla wspólnot mieszkaniowych bez zarządcy. Laravel to recommended default dla pary `(web, php)`; clears trzy z czterech agent-friendly bramek (convention-based, popular_in_training, well_documented), pęka na `typed: false` — kompensata przez PHPStan/Larastan w CLAUDE.md, do dograć w bootstrapperze. Magic-link auth (FR-003) zbudowany w Fortify; Storage facade pokrywa załączniki paragonów (FR-013, Open Question #3); Eloquent policies mapują naturalnie na role Założyciel/Właściciel/Gość/Oczekujący z sekcji Access Control. Bootstrapper confidence: verified — scaffolding będzie gładki. Deployment na Railway (researched decyzja przez `/10x-infra-research` — patrz `context/foundation/infrastructure.md` dla scoring matrix i anti-bias check; Fly.io był starter-card defaultem, ale Railway wygrał na soft-weights cost + co-location), CI na GitHub Actions z auto-deploy on merge. Auth flag ustawiony; payments/realtime/AI/background-jobs out-of-scope per PRD Non-Goals (powiadomienia odłożone do v2 per Open Question #2). Multi-tenant context switching (FR-005) nie jest OOTB w Laravel — wymaga spatie/laravel-multitenancy lub ręcznego global scope per tenant_id, surfaced jako friction w konwersacji wyboru.
