---
project: Podwórko
version: 1
status: draft
created: 2026-05-28
updated: 2026-05-28
prd_version: 1
main_goal: low-complexity
top_blocker: time
---

# Roadmap: Podwórko

> Derived from `context/foundation/prd.md` (v1) + auto-researched codebase baseline + 3-anchor framing interview (2026-05-28).
> Edit-in-place; archive when superseded.
> Slices below are listed in dependency order. The "At a glance" table is the index.

## Vision recap

Właściciele nieruchomości zarządzający wspólnymi sprawami bez profesjonalnego zarządcy (zabudowa bliźniacza/szeregowa od pary właścicieli wzwyż, osiedla zamknięte po odejściu dewelopera) nie mają jednego miejsca do koordynacji kosztów i terminów. Istniejące narzędzia HOA są zbyt ciężkie, zbyt formalne, zbyt drogie dla sąsiadów działających na zaufaniu. Podwórko wypełnia tę lukę prostą, nieformalną aplikacją skupioną na rozliczeniach między domami i obliczaniu terminów przeglądów.

**North star (gwiazda przewodnia)** — *najmniejszy end-to-end slice, który po pomyślnym dostarczeniu udowadnia, że rdzeń produktu działa; sekwencjonowany tak wcześnie, jak pozwalają Prerequisites, bo wszystko inne ma sens tylko jeśli to działa.* W naszym przypadku: S-03 (poniżej).

## North star

**S-03: End-to-end koszt-settlement single-payer z dwustronnym potwierdzeniem** — najmniejszy slice, który jednocześnie udowadnia że (a) financial-correctness rdzenia (suma udziałów = kwota, FR-009) działa, (b) two-sided confirmation (FR-012) tworzy zaufanie między sąsiadami, (c) visibility scope (FR-016) izoluje rozliczenia od domów nieuczestniczących. Bez tego slice'u produkt nie ma "why".

## At a glance

| ID    | Change ID                            | Outcome (user can …)                                                                          | Prerequisites          | PRD refs                                  | Status   |
| ----- | ------------------------------------ | --------------------------------------------------------------------------------------------- | ---------------------- | ----------------------------------------- | -------- |
| F-01  | project-tooling                      | (foundation) PHPStan/Larastan level 5 + Codeception suites + Pint pre-commit wpięte           | —                      | (per coding-rules.md)                     | ready    |
| F-02  | auth-scaffold-magic-link             | (foundation) Magic-link auth (Fortify) + 4-role User model + pending state                    | F-01                   | FR-003, FR-004, Access Control            | proposed |
| F-03  | domain-primitives                    | (foundation) Neighbourhood + Household + Membership models + Money VO + visibility global scope | F-01, F-02             | FR-016, NFR (sum=total)                   | proposed |
| S-01  | founder-creates-neighbourhood        | Założyciel tworzy osiedle i otrzymuje krótki niezmienny kod dostępu                            | F-02, F-03             | FR-001, FR-002                            | proposed |
| S-02  | neighbour-joins-via-magic-link       | Sąsiad dołącza przez email+kod (magic-link), ląduje jako pending, Założyciel zatwierdza        | S-01                   | FR-003, FR-004                            | proposed |
| S-03  | cost-settlement-single-payer         | Właściciel rejestruje koszt single-payer, system liczy udziały, dwustronne potwierdzenie zamyka rozliczenie | S-02                   | FR-006, FR-007, FR-009, FR-010, FR-011, FR-012, FR-016 | proposed |
| S-04  | cost-settlement-multi-payer          | Rejestrujący koszt może wskazać dodatkowych płatników z konkretnymi kwotami; algorytm dopasowuje debtor→creditor | S-03                   | FR-008, FR-009 (full), FR-010 (full)      | proposed |
| S-05  | cost-attachments                     | Właściciel dołącza zdjęcie paragonu/faktury do kosztu (Storage facade local→S3 przed public)   | S-03                   | FR-013                                    | proposed |
| S-06  | cost-refunds                         | Rejestrujący koszt dodaje wpis zwrotu, system proporcjonalnie zmniejsza udziały                | S-03                   | FR-014                                    | proposed |
| S-07  | cost-locked-force-edit               | Koszt jest read-only po pierwszym potwierdzeniu; rejestrujący wymusza edycję z resetem potwierdzeń | S-03                   | FR-015                                    | proposed |
| S-08  | multi-neighbourhood-context-switcher | Użytkownik z dostępem do wielu osiedli przełącza aktywny kontekst w nagłówku; ostatni zapamiętany | S-02                   | FR-005                                    | proposed |
| S-09  | inspections-and-deadlines            | Właściciel rejestruje przeglądy z częstotliwością, oznacza datę wykonania, system liczy następną; Gość widzi historię | F-02, F-03             | FR-017, FR-018, FR-019, FR-020, FR-021    | proposed |
| S-10  | member-offboarding-base              | Właściciel wypisuje się samodzielnie lub Założyciel wypisuje innego; historyczne rozliczenia zachowane | S-02                   | FR-022, FR-023, FR-024                    | blocked  |
| S-11  | former-owners-in-cost-history        | Rejestrujący koszt rozwija listę uczestników o byłych właścicieli; Założyciel decyduje per-pozycja przy off-boardingu | S-10                   | FR-025, FR-026                            | blocked  |

## Streams

Navigation aid — grupuje items dzielące Prerequisites chain. Canonical ordering wciąż w dependency graph poniżej; tabela poniżej to proposed reading order across parallel tracks.

| Stream | Theme                          | Chain                                                                  | Note                                                                                  |
| ------ | ------------------------------ | ---------------------------------------------------------------------- | ------------------------------------------------------------------------------------- |
| A      | Foundations + Auth + Onboarding | `F-01` → `F-02` → `F-03` → `S-01` → `S-02`                            | Krytyczny prerequisite chain dla wszystkich domain-slice'ów. Sekwencyjne, brak parallelization. |
| B      | Cost settlement core           | `S-03` → `S-04` / `S-05` / `S-06` / `S-07`                            | Po `S-03` (north star) — `S-04`, `S-05`, `S-06`, `S-07` są wzajemnie parallel (każdy depends tylko od S-03). |
| C      | Multi-neighbourhood UX         | `S-08`                                                                 | Joins Stream A at `S-02`. Standalone slice — parallel-with cały Stream B.            |
| D      | Inspections & deadlines        | `S-09`                                                                 | Joins Stream A at `F-03`. Domain niezależny od cost domain — parallel-with cały Stream B i C. |
| E      | Off-boarding (BLOCKED)         | `S-10` → `S-11`                                                        | Joins Stream A at `S-02`. **Oba blocked** przez Open Roadmap Question #1 (sukcesja domu). |

## Baseline

What's already in place w codebase as of 2026-05-28 (auto-researched z session context + user-confirmed).
Foundations below assume these are present and do NOT re-scaffold them.

- **Frontend:** partial — Laravel 13 ships Blade + Vite + Tailwind v4 (vite.config.js, resources/views/welcome.blade.php). Zero domain-specific views/components.
- **Backend / API:** per `tech-stack.md` — Laravel 13.8 (PHP 8.4). `bootstrap/app.php` z `trustProxies`, `routes/web.php` (tylko welcome). Zero domain controllers/models/policies.
- **Data:** per `tech-stack.md` — Postgres 17.7 (Railway managed, EU West/AMS). Default Laravel scaffold (users, password_reset_tokens, sessions, cache, jobs). Zero domain schema.
- **Auth:** absent — `users` table jest, Fortify nie zainstalowany, magic-link flow nie napisany. Build-from-zero.
- **Deploy / infra:** per `tech-stack.md` — Railway (deployed 2026-05-27, EU West). `docker/railway/*` + `railway.json` + `/up` healthcheck. Brak `.github/workflows`.
- **Observability:** minimal — `LOG_CHANNEL=stderr` → Railway aggregated logs. Brak APM/metrics/error tracking.

## Foundations

### F-01: Project tooling — PHPStan + Codeception + Pint

- **Outcome:** (foundation) PHPStan/Larastan level 5 i Codeception suites (`Unit`/`Functional`/`Acceptance`) wpięte do projektu; Pint z pre-commit hookiem. CI gate placeholder przygotowany do wpięcia w późniejszym slice.
- **Change ID:** `project-tooling`
- **PRD refs:** brak bezpośrednich; required przez `context/foundation/coding-rules.md` ("PHPStan + Codeception from day zero" — kompensata `typed: false` z tech-stack-selector)
- **Unlocks:** verification path dla wszystkich slice'ów (Codeception Acceptance/Functional/Unit dispatch + PHPStan na każdy commit); `S-03` north star wymaga tej infrastruktury żeby walidować deterministycznie wyliczanie udziałów.
- **Prerequisites:** —
- **Parallel with:** —
- **Blockers:** —
- **Unknowns:** —
- **Risk:** Bez tej foundacji każdy następny slice testuje się ad-hoc, drift styl/typu narasta. Sekwencjonowane jako pierwsze bo niski koszt + multiplikator jakości.
- **Status:** ready

### F-02: Auth scaffold — Magic-link + 4 role + pending state

- **Outcome:** (foundation) Magic-link sign-in (Fortify lub hand-rolled) działa: user wpisuje email + kod osiedla, klika link z maila, ląduje zalogowany. 4-rolowy User model (Założyciel / Właściciel / Gość / Oczekujący) z policies wpiętymi do middleware. Pending state widoczny w pipeline approval.
- **Change ID:** `auth-scaffold-magic-link`
- **PRD refs:** FR-003, FR-004, sekcja `## Access Control` (cała tabela ról), NFR (visibility scoping zaczyna się od authenticated user)
- **Unlocks:** `S-01` (Założyciel musi się uwierzytelnić zanim utworzy osiedle), `S-02` (sąsiad join flow = magic-link + pending), wszystkie późniejsze slice'y user-facing
- **Prerequisites:** F-01
- **Parallel with:** —
- **Blockers:** —
- **Unknowns:**
  - First-time user flow przed istnieniem osiedla — sign-up email-only (utwórz osiedle po zalogowaniu) vs sign-up z nazwą nowego osiedla? — Owner: implementation. Block: no (decyzja /10x-plan).
- **Risk:** Auth scaffold jest najbardziej "load-bearing" foundation — błąd tutaj kompromituje guardrail "żaden niezaproszony użytkownik nie widzi danych". Sekwencjonowany przed wszystkimi user-facing slice'ami.
- **Status:** proposed

### F-03: Domain primitives — Neighbourhood + Household + Membership + Money + visibility scope

- **Outcome:** (foundation) Schema + Eloquent models: `Neighbourhood`, `Household`, `Membership` (łączy User z Household w danym Neighbourhood, z rolą). `Money` value object (integer grosze, per `coding-rules.md` § Money). `ParticipatingHouseholdScope` Eloquent global scope dla wszystkich cost-related models (FR-016 enforced at query layer, nigdy w kontrolerze).
- **Change ID:** `domain-primitives`
- **PRD refs:** FR-006 (household = jednostka), FR-016 (visibility scoped), NFR (sum=total — wymaga integer money), `## Access Control` (Membership łączy User+Role+Household+Neighbourhood)
- **Unlocks:** `S-01` (Neighbourhood model), `S-02` (Membership + Household), `S-03` (cała cost domain łączy się przez Membership + visibility scope), wszystkie cost-related slice'y, `S-09` (Inspection model dziedziczy podobny scope)
- **Prerequisites:** F-01, F-02
- **Parallel with:** —
- **Blockers:** —
- **Unknowns:**
  - Multi-tenancy approach: `spatie/laravel-multitenancy` package vs hand-rolled `neighbourhood_id` global scope per model. — Owner: implementation. Block: no (decyzja /10x-plan).
- **Risk:** Visibility scope na poziomie Eloquent global scope, nie w kontrolerach (per `coding-rules.md`). Błąd tutaj = wyciek danych między osiedlami — krytyczne dla zaufania.
- **Status:** proposed

## Slices

### S-01: Założyciel tworzy osiedle

- **Outcome:** Użytkownik tworzy nowe osiedle podając nazwę, system generuje krótki kod dostępu (≤6 znaków), użytkownik widzi propozycję kodu i może wygenerować nowy przed zapisem, po zapisie kod niezmienny. Twórca otrzymuje rolę Założyciela.
- **Change ID:** `founder-creates-neighbourhood`
- **PRD refs:** FR-001, FR-002
- **Prerequisites:** F-02, F-03
- **Parallel with:** S-09 (po F-02+F-03 oba mogą startować równolegle)
- **Blockers:** —
- **Unknowns:**
  - Algorytm generowania kodu osiedla z nazwy + suffix dla unikalności — PRD §Open Q #4. — Owner: implementation. Block: no.
- **Risk:** Kod osiedla jest niezmienny po zapisie — jeden niewłaściwy znak / wulgarność i user się drażni. Pre-save reset (FR-002) mitiguje. Mały slice, niski risk.
- **Status:** proposed

### S-02: Sąsiad dołącza przez magic-link + Założyciel zatwierdza

- **Outcome:** Sąsiad wpisuje email + kod osiedla, dostaje magic-link na maila, klika → zalogowany jako Oczekujący, wskazuje istniejący dom lub wniosek o nowy dom. Założyciel widzi pending users na liście, zatwierdza i nadaje rolę (Właściciel / Gość). Pending dla istniejącego domu może też być zatwierdzony przez dowolnego Właściciela tego domu.
- **Change ID:** `neighbour-joins-via-magic-link`
- **PRD refs:** FR-003, FR-004
- **Prerequisites:** S-01
- **Parallel with:** S-09
- **Blockers:** —
- **Unknowns:**
  - Wiele osiedli na jeden email — czy email może należeć do wielu osiedli? PRD §Open Q (z shape-notes #4). — Owner: użytkownik. Block: no (default: tak, multi-osiedle obsłużony w S-08).
- **Risk:** Magic-link flow musi działać niezawodnie (skrzynki z mail filters, link expiry). Test Acceptance suite musi pokryć: link nieklikalny, link kliknięty 2x, email pomylony.
- **Status:** proposed

### S-03: ★ NORTH STAR — End-to-end koszt-settlement single-payer

- **Outcome:** Właściciel rejestruje koszt wspólny (kwota, opis, notatka o sposobie płatności), zaznacza checkboxami które domy uczestniczą (jednostka = dom, nie osoba). System wylicza per-dom udział (równy podział, zaokrąglenie w górę do grosza, reszta na płacącego). Każdy uczestniczący dom widzi swój udział i instrukcję płatności. Właściciel domu-dłużnika oznacza zapłatę (pełną kwotą). Właściciel domu-płacącego (single payer = rejestrujący) potwierdza odbiór każdej płatności niezależnie. Rozliczenie automatycznie zamyka się gdy wszystkie potwierdzone. Visibility scope (FR-016) — domy nieuczestniczące nie widzą tego rozliczenia ani w aktywnych, ani w historii.
- **Change ID:** `cost-settlement-single-payer`
- **PRD refs:** FR-006, FR-007, FR-009 (single-payer wariant), FR-010 (single-payer wariant — uproszczony do "wszyscy dłużnicy → jeden wierzyciel"), FR-011 (pełna kwota; partial payments w follow-up nie tu), FR-012, FR-016, US-01, NFR (sum=total, no rounding errors)
- **Prerequisites:** S-02
- **Parallel with:** S-09
- **Blockers:** —
- **Unknowns:**
  - Czy partial payments (zapłacono 20 z 25, zalega 5) wchodzą w ten slice czy w follow-up? — Owner: implementation. Block: no — defaulting do "pełna kwota only w S-03, partial w S-04 lub osobnym micro-slice".
- **Risk:** Najważniejszy slice produktu — financial correctness musi być bezbłędna (PRD NFR "no rounding errors visible to user"). Codeception Acceptance test musi pokryć: full happy path, edge case (1 dom, 2 domy, 12 domów), zaokrąglenie (kwoty niepodzielne równo). FR-016 visibility test (non-participant nie widzi) jest load-bearing dla zaufania.
- **Status:** proposed

### S-04: Multi-payer extension

- **Outcome:** Rejestrujący koszt może wskazać dodatkowych płatników (oprócz siebie) z konkretnymi kwotami. System waliduje że suma kwot płatników = kwota całkowita, każdy płatnik jest też uczestnikiem. Algorytm deterministycznie dopasowuje debtor→creditor minimalizując liczbę instrukcji płatności per dłużnik (większość: 1 instrukcja; rozbicie na 2 tylko przy końcówce dopasowania).
- **Change ID:** `cost-settlement-multi-payer`
- **PRD refs:** FR-008, FR-009 (pełna walidacja: suma płatników = total, każdy płatnik = uczestnik), FR-010 (pełny algorytm matching)
- **Prerequisites:** S-03
- **Parallel with:** S-05, S-06, S-07
- **Blockers:** —
- **Unknowns:**
  - Konkretny algorytm matching (greedy "największy dług → największa nadwyżka" vs inny) — PRD §Open Q #5. — Owner: implementation. Block: no.
- **Risk:** Algorytm matching ma korner-case na końcówce (gdy reszta < grosza per dom). Test Property-based / table-driven w Codeception Functional dla różnych kombinacji liczby płatników i uczestników.
- **Status:** proposed

### S-05: Załączniki paragonów (FR-013)

- **Outcome:** Właściciel dołącza zdjęcie paragonu lub skan faktury do kosztu — z aparatu telefonu lub z galerii urządzenia. Załącznik widoczny dla wszystkich uczestników kosztu (visibility scope per FR-016). Storage: Laravel Storage facade, `local` disk w dev, swap na S3/R2 driver przed public launch (per `context/foundation/infrastructure.md`).
- **Change ID:** `cost-attachments`
- **PRD refs:** FR-013, NFR ("aplikacja przyjmuje załączniki obrazowe... z pamięci urządzenia, jak i bezpośrednio z aparatu telefonu")
- **Prerequisites:** S-03
- **Parallel with:** S-04, S-06, S-07
- **Blockers:** —
- **Unknowns:**
  - Limit rozmiaru pliku i client-side compression — implementation-level. — Owner: implementation. Block: no. Default: `client_max_body_size 25M` (już ustawione w `docker/railway/nginx.conf.template`), upload_max_filesize 25M (już w `docker/railway/php.ini`).
- **Risk:** Bezpośredni upload z aparatu telefonu wymaga `<input capture="camera">` plus poprawnej obsługi orientacji EXIF. Acceptance test musi pokryć: portrait i landscape, plik za duży, plik nieobrazowy (czy odrzucamy?).
- **Status:** proposed

### S-06: Refundy/zwroty (FR-014)

- **Outcome:** Rejestrujący koszt dodaje wpis zwrotu przypisany do oryginalnego kosztu (kwota + opcjonalna notatka). Zwrot proporcjonalnie zmniejsza udziały uczestników, aktualizuje obowiązujące instrukcje płatności. Walidacja: suma zwrotów ≤ kwota całkowita kosztu.
- **Change ID:** `cost-refunds`
- **PRD refs:** FR-014
- **Prerequisites:** S-03
- **Parallel with:** S-04, S-05, S-07
- **Blockers:** —
- **Unknowns:**
  - Zwrot na koszt z już potwierdzonymi płatnościami — PRD mówi "wymaga przejścia ścieżki wymuszonej edycji" — czy to znaczy że FR-015 (S-07) musi być przed S-06? — Owner: implementation. Block: no — można re-sekwencjonować S-06 i S-07 lub łączyć je gdyby zachowanie wymagało.
- **Risk:** Recalculation logic musi przejść przez tę samą `MoneyAllocator` klasę co S-03 — żaden duplikat logiki settlement.
- **Status:** proposed

### S-07: Force-edit zablokowanego kosztu (FR-015)

- **Outcome:** Koszt staje się read-only gdy ktokolwiek potwierdził płatność. Rejestrujący może wymusić edycję zablokowanego kosztu po ręcznym potwierdzeniu — wymuszona edycja resetuje status potwierdzeń pozostałych uczestników i wymaga ponownego potwierdzenia każdej instrukcji płatności.
- **Change ID:** `cost-locked-force-edit`
- **PRD refs:** FR-015
- **Prerequisites:** S-03
- **Parallel with:** S-04, S-05, S-06
- **Blockers:** —
- **Unknowns:**
  - Audit trail — czy zachowujemy wersję pre-force-edit kosztu do późniejszego przeglądu? PRD nie precyzuje. — Owner: użytkownik. Block: no — default: tak, w `cost_versions` tabeli, decyzja w /10x-plan.
- **Risk:** Reset potwierdzeń bez wyraźnego komunikatu UI = brak zaufania ("co ten system robi z moimi pieniędzmi?"). Acceptance test: po force-edit, każdy uczestnik widzi wyraźne "wymuszono edycję — potwierdź ponownie".
- **Status:** proposed

### S-08: Multi-neighbourhood context switcher (FR-005)

- **Outcome:** Użytkownik posiadający dostęp do wielu osiedli (np. dom + nieruchomość inwestycyjna + letniskowa) przełącza aktywny kontekst osiedla przełącznikiem w nagłówku aplikacji. System zapamiętuje ostatnio wybrany kontekst i otwiera go domyślnie przy następnym logowaniu.
- **Change ID:** `multi-neighbourhood-context-switcher`
- **PRD refs:** FR-005
- **Prerequisites:** S-02
- **Parallel with:** S-03..S-07, S-09
- **Blockers:** —
- **Unknowns:**
  - Storage current_neighbourhood_id: session vs user_settings table. — Owner: implementation. Block: no.
- **Risk:** Wymaga że `ParticipatingHouseholdScope` z F-03 honoruje "current neighbourhood" z sesji, nie tylko membership. To implication na F-03 — może wymagać refactor jeśli F-03 zostało zaprojektowane bez tego. Mitigation: opisać tę wymagalność w F-03 plan.
- **Status:** proposed

### S-09: Przeglądy / terminy (FR-017–FR-021)

- **Outcome:** Właściciel rejestruje przegląd (nazwa, częstotliwość w miesiącach lub latach), oznacza datę ostatniego wykonania, dodaje notatki (kontakt do wykonawcy, orientacyjny koszt). System wylicza i wyświetla przewidywaną datę następnego przeglądu (data ostatniego + częstotliwość). Właściciel i Gość przeglądają historię wykonanych przeglądów i status kolejnych.
- **Change ID:** `inspections-and-deadlines`
- **PRD refs:** FR-017, FR-018, FR-019, FR-020, FR-021
- **Prerequisites:** F-02, F-03
- **Parallel with:** S-01..S-08 (cała Stream B oraz S-08 nie zależą od tego slice'u — najlepszy kandydat na parallel agent run)
- **Blockers:** —
- **Unknowns:** —
- **Risk:** Najprostszy slice z PRD (brak schedulera, brak powiadomień, czysta CRUD + obliczenie daty). Dobry kandydat na "low-complexity quick win" dla okresów słabszego momentum.
- **Status:** proposed

### S-10: Member off-boarding base (FR-022–FR-024)

- **Outcome:** Właściciel może samodzielnie wypisać się z osiedla (z confirmation checkbox, jeśli brak aktywnych zobowiązań). Założyciel może wypisać innego Właściciela (z confirmation). Wypisany właściciel pozostaje widoczny w historycznych rozliczeniach których był uczestnikiem (historia niemodyfikowalna).
- **Change ID:** `member-offboarding-base`
- **PRD refs:** FR-022, FR-023, FR-024
- **Prerequisites:** S-02
- **Parallel with:** S-09
- **Blockers:** —
- **Unknowns:**
  - **Sukcesja domu po sprzedaży: nowy właściciel dziedziczy historyczny dostęp do rozliczeń tego domu, czy zaczyna z czystą historią?** PRD §Open Q #6 + Open Roadmap Question #1. — Owner: użytkownik. Block: **yes**.
- **Risk:** Off-boarding z aktywnymi zobowiązaniami jest złożony (FR-026 wymaga per-pozycja decyzji Założyciela). Bez rozstrzygnięcia sukcesji ten slice może wymagać przeprojektowania.
- **Status:** **blocked**

### S-11: Former owners in cost history (FR-025–FR-026)

- **Outcome:** Przy tworzeniu nowego kosztu rejestrujący może rozwinąć listę uczestników o byłych właścicieli (np. gdy pojawia się nieuregulowane zobowiązanie z wcześniejszego okresu). Podczas wypisywania właściciela z aktywnymi zobowiązaniami Założyciel decyduje per-pozycja: (a) zachować jako otwarte, (b) oznaczyć jako rozwiązane (z notatką), (c) przenieść na nowego właściciela tego domu.
- **Change ID:** `former-owners-in-cost-history`
- **PRD refs:** FR-025, FR-026
- **Prerequisites:** S-10
- **Parallel with:** —
- **Blockers:** —
- **Unknowns:**
  - **Mechanika "przeniesienia na nowego właściciela" — jak nowy właściciel dziedziczy zobowiązanie?** PRD §Open Q #6. Wspólne z S-10. — Owner: użytkownik. Block: **yes**.
- **Risk:** Per-pozycja decyzja Założyciela jest niemodyfikowalna po zakończeniu wypisania — błąd UI = trwała pomyłka w historii rozliczeń.
- **Status:** **blocked**

## Backlog Handoff

| Roadmap ID | Change ID                            | Suggested issue title                                                       | Ready for `/10x-plan` | Notes |
| ---------- | ------------------------------------ | --------------------------------------------------------------------------- | --------------------- | ----- |
| F-01       | project-tooling                      | Wpięcie PHPStan/Larastan level 5 + Codeception suites + Pint pre-commit     | yes                   | Run `/10x-plan project-tooling` — single-instruction starter w `coding-rules.md` |
| F-02       | auth-scaffold-magic-link             | Magic-link auth (Fortify) + 4-role User model + pending state               | no                    | Requires F-01 |
| F-03       | domain-primitives                    | Neighbourhood / Household / Membership / Money VO / visibility global scope | no                    | Requires F-01, F-02 |
| S-01       | founder-creates-neighbourhood        | Founder tworzy osiedle z generowanym kodem dostępu                          | no                    | Requires F-02, F-03 |
| S-02       | neighbour-joins-via-magic-link       | Sąsiad joins przez email+kod, pending → approved przez Founder/Owner        | no                    | Requires S-01 |
| S-03       | cost-settlement-single-payer         | ★ North star — koszt-settlement single-payer z dwustronnym potwierdzeniem   | no                    | Requires S-02 |
| S-04       | cost-settlement-multi-payer          | Multi-payer extension + algorytm dopasowania debtor→creditor                | no                    | Requires S-03 |
| S-05       | cost-attachments                     | Załączniki paragonów (Storage facade local→S3 swap path)                    | no                    | Requires S-03 |
| S-06       | cost-refunds                         | Zwroty / refundy z proporcjonalnym recalculation udziałów                   | no                    | Requires S-03 |
| S-07       | cost-locked-force-edit               | Read-only po pierwszym potwierdzeniu + wymuszona edycja z resetem           | no                    | Requires S-03 |
| S-08       | multi-neighbourhood-context-switcher | Przełącznik osiedla w nagłówku dla multi-tenant users                       | no                    | Requires S-02 |
| S-09       | inspections-and-deadlines            | Przeglądy + terminy + obliczanie następnego (zero scheduler)                | no                    | Requires F-02, F-03. Parallel-with cały Stream B. |
| S-10       | member-offboarding-base              | Wypisywanie właściciela (self / Founder) + historia zachowana               | **blocked**           | Requires S-02 + Open Roadmap Question #1 resolution |
| S-11       | former-owners-in-cost-history        | Byli właściciele w historii kosztów + Founder per-pozycja off-boarding decision | **blocked**           | Requires S-10 + Open Roadmap Question #1 resolution |

## Open Roadmap Questions

1. **Sukcesja domu po sprzedaży: nowy właściciel dziedziczy historyczny dostęp do rozliczeń tego domu, czy zaczyna z czystą historią?** — Owner: użytkownik. Block: S-10, S-11. (Łączy PRD §Open Q #1 + §Open Q #6 — to ten sam fundamentalny brak modelu sukcesji.)
2. **RODO i dane wrażliwe — notatki o płatnościach z numerami kont + kontakty do wykonawców.** — Owner: użytkownik. Block: roadmap-wide przed **public launch** (nie blokuje implementacji MVP dla pierwszych zaufanych userów).
3. **Limit liczby rat per instrukcja płatności (FR-011 partial payments).** — Owner: użytkownik. Block: S-04 (decyzja przed implementacją multi-payer + partial). (PRD §Open Q #8)

## Parked

- **Integracja z bankami / weryfikacja przelewów** — PRD §Non-Goals: rozliczenia rejestrowane ręcznie, bez bank API.
- **Formalne zarządzanie (uchwały, głosowania, protokoły zebrań)** — PRD §Non-Goals: Podwórko działa na zasadzie zaufania między sąsiadami, nie jest systemem HOA.
- **Automatyczne powiadomienia push/email o terminach** — PRD §Non-Goals: terminy obliczane i wyświetlane on-demand w UI, zero schedulera/crona w MVP.
- **Moduł ogólnego przechowywania dokumentów (umowy, regulaminy)** — PRD §Non-Goals: tylko paragony/faktury w MVP via FR-013 (S-05), nie full document base.
- **Dyżury sprzątania i koordynacja obowiązków rotacyjnych** — PRD §Non-Goals: v2.
- **Cykliczne / powtarzalne koszty (szablony, "powtórz transakcję")** — PRD §Open Q #7, v2 po obserwacji pierwszych userów.
- **Minimalny system powiadomień (tygodniowy dygest email)** — PRD §Open Q #2, v2 po obserwacji retencji.
- **Multi-region deploy / multi-AZ EU** — `infrastructure.md` risk register: Amsterdam-only akceptowane, plan B Fly.io FRA jako fallback.
- **CI/CD pipeline GitHub Actions** — chosen w `tech-stack.md hints.ci_provider: github-actions`, brak `.github/workflows` — odkładamy aż drugi/trzeci developer dołączy do projektu lub mergery zaczną być częste; do tego czasu local Pint+PHPStan+Codeception przez `coding-rules.md` wystarczą.
- **Custom domain `podworko.pl` (lub similar)** — odkładamy do decyzji o nazwie i strategii brandingu.
- **OPcache fine-tuning / production performance** — odkładamy do pierwszych metryk produkcyjnych z prawdziwych userów.
- **PR previews / staging environment** — Railway environments forking dostępne, ale dla solo-developera overhead > zysk; rozważymy gdy pojawi się drugi developer.

## Done

(Empty on first generation. `/10x-archive` appends here when a change whose `Change ID` matches a roadmap item is archived.)
