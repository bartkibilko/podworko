---
project: "Podwórko"
context_type: greenfield
product_type: web-app
target_scale:
  users: large
  qps: low
  data_volume: small
timeline_budget:
  mvp_weeks: 12
  hard_deadline: null
  after_hours_only: true
created: 2026-05-24
updated: 2026-05-24
version: 1
status: draft
checkpoint:
  current_phase: 8
  phases_completed: [1, 2, 3, 4, 5, 6, 7]
  frs_drafted: 17
  quality_check_status: accepted
  gray_areas_resolved:
    - topic: "główny ból"
      decision: "rozliczenia między sąsiadami — najczęstsze źródło konfliktów"
    - topic: "persona i dostęp"
      decision: "wszyscy właściciele (pełny dostęp) + goście np. współmałżonek (ograniczony dostęp)"
    - topic: "dyżury sprzątania w MVP"
      decision: "nice-to-have, przenoszone do v2"
    - topic: "insight konkurencyjny"
      decision: "istniejące narzędzia HOA są za ciężkie dla osiedli bez zarządcy — od 2 właścicieli domu bliźniaczego po większe osiedla"
    - topic: "auth method"
      decision: "magic link (email + kod osiedla), bez hasła"
    - topic: "model ról"
      decision: "Właściciel (pełny dostęp) + Gość (tylko odczyt) + Oczekujący (pending, zatwierdzany przez właściciela)"
    - topic: "dołączanie do osiedla"
      decision: "jeden niezmienny kod per osiedle; wszyscy wchodzą jako Oczekujący, właściciel zatwierdza"
    - topic: "podział kosztów"
      decision: "checkboxy uczestnictwa przy każdym koszcie — nie zawsze wszyscy właściciele uczestniczą"
    - topic: "terminy — głębokość MVP"
      decision: "tylko rekordy + obliczanie następnego terminu w UI; zero schedulera/crona"
---

## Vision & Problem Statement

Właściciele nieruchomości zarządzający wspólnymi sprawami bez profesjonalnego zarządcy nie mają jednego miejsca do koordynacji. Dotyczy to co najmniej dwóch odrębnych segmentów: (1) zabudowy bliźniaczej i szeregowej — od pary właścicieli po większe osiedla — gdzie właściciele sami organizują przeglądy i rozliczają wspólne koszty; (2) osiedli zamkniętych (gated communities) po odejściu dewelopera, gdzie prywatne drogi, szlabany, oświetlenie i zieleń wymagają samodzielnego finansowania i koordynacji przez właścicieli. W obu przypadkach dokumenty giną w mailach, terminy przeglądów są zapominane, a rozliczenia prowadzone w Excelu lub wcale — rodząc konflikty i straty finansowe.

Istniejące narzędzia HOA i systemy zarządzania wspólnotami są projektowane dla dużych wspólnot z zarządcą i księgowym — są zbyt ciężkie, zbyt drogie i zbyt formalne dla sąsiadów działających na zasadzie zaufania bez formalnej struktury. Luka na prostą, nieformalną aplikację dla tej skali jest realna i niezagospodarowana.

## User & Persona

### Podstawowa persona

**Właściciel nieruchomości na osiedlu bez zarządcy** — osoba posiadająca dom bliźniacowy, w zabudowie szeregowej lub na osiedlu zamkniętym po odejściu dewelopera, dzieląca wspólne koszty i obowiązki z innymi właścicielami bez profesjonalnego zarządcy. Zarządza wspólnymi sprawami (przeglądy, naprawy, rozliczenia) w wolnym czasie, obok pracy zawodowej. Moment sięgania po produkt: zbliża się przegląd kominiarski i nikt nie pamięta kiedy był ostatni; sąsiad zapłacił za naprawę i chce to rozliczyć z pozostałymi.

### Persona drugorzędna

**Gość (np. współmałżonek/partner właściciela)** — ma dostęp do przeglądania danych (dokumenty, rozliczenia, terminy) bez możliwości edycji. Widzi stan osiedla, ale nie wprowadza zmian.

## Access Control

**Logowanie:** magic link — użytkownik podaje email i kod osiedla, otrzymuje link na skrzynkę, kliknięcie = zalogowany. Brak haseł.

**Role:**
- `Właściciel` — pełny dostęp: tworzenie, edycja i usuwanie terminów, rozliczeń; zatwierdzanie nowych użytkowników.
- `Gość` — tylko odczyt: przeglądanie danych osiedla i rozliczeń swojego domu bez możliwości edycji. Gość jest powiązany z konkretnym domem — widzi to co widzi właściciel tego domu. Goście NIE uczestniczą w rozliczeniach jako odrębna strona (dom uczestniczy, nie osoba).
- `Oczekujący (pending)` — stan tymczasowy po pierwszym wejściu kodem: widzi tylko nazwę osiedla i listę członków; brak dostępu do rozliczeń i terminów. Właściciel musi zatwierdzić i przypisać rolę.

**Dołączanie do osiedla:** jeden unikalny kod per osiedle, niezmienny. Nowy użytkownik wpisuje email + kod → magic link → wchodzi jako Oczekujący → Właściciel zatwierdza i nadaje rolę.

**Niezalogowany użytkownik** trafiający na chronioną trasę jest przekierowany do strony logowania.

## Success Criteria

### Primary
- Właściciel tworzy osiedle → zaprasza sąsiadów kodem → dodaje koszt wspólny (np. 300 zł) → system dzieli równo na właścicieli → każdy widzi swój udział → oznacza że zapłacił → płacący potwierdza odbiór → rozliczenie oznaczone jako zamknięte.

### Secondary
- Historia rozliczeń widoczna dla wszystkich członków osiedla — przeglądanie poprzednich kosztów i ich statusów.

### Guardrails
- Dane finansowe (rozliczenia, kwoty, statusy płatności) dostępne wyłącznie dla zalogowanych członków osiedla z ważnym kodem dostępu. Żaden niezaproszony użytkownik nie może zobaczyć danych.

## Functional Requirements

### Onboarding i osiedle

- FR-001: Właściciel może stworzyć nowe osiedle podając nazwę. Priority: must-have
- FR-002: System automatycznie generuje unikalny, niezmienny kod dostępu osiedla na podstawie nazwy. Priority: must-have
  > Socrates: Rozważano możliwość zmiany kodu — odrzucono, bo zmiana kodu po zaproszeniu sąsiadów uniemożliwiałaby dołączenie tym, którzy jeszcze nie skorzystali z linku. Niezmienny kod eliminuje ten problem.
- FR-003: Użytkownik może dołączyć do osiedla podając email i kod osiedla — system wysyła magic link, po kliknięciu użytkownik wchodzi w tryb odczytu (pending). Priority: must-have
  > Socrates: Zastępuje wcześniejszy model email+hasło+dwa kody. Jeden kod + magic link = zero haseł = prostsze dla nietech użytkowników.
- FR-004: Właściciel z pełnym dostępem może zatwierdzić oczekującego użytkownika i nadać mu rolę Właściciela lub Gościa. Priority: must-have

### Rozliczenia

- FR-005: Właściciel może dodać koszt wspólny (kwota całkowita, opis, opcjonalna notatka o sposobie płatności). Priority: must-have
- FR-006: Przy dodawaniu kosztu właściciel wskazuje checkboxami które **domy** uczestniczą — jednostką podziału jest dom, nie indywidualny właściciel. Opcjonalnie podaje ile każdy dom już zapłacił (pre-settlement). Domyślnie dom dodający koszt ma swój udział wstępnie oznaczony jako uregulowany. Priority: must-have
  > Socrates: Zmieniono z "właściciel" na "dom" jako jednostkę uczestnictwa — rozliczenia są między domami, nie osobami. Gość powiązany z danym domem widzi rozliczenia swojego domu.
- FR-007: System oblicza udział każdego uczestniczącego domu: (kwota całkowita - suma pre-settlements) / liczba pozostałych domów. Zaokrąglenie: domy inne niż płacący zaokrąglają w górę do 1 gr, dom płacący dostaje resztę. Priority: must-have
- FR-008: Właściciel domu uczestniczącego w koszcie (z niezerowym zobowiązaniem) może oznaczyć udział swojego domu jako zapłacony. Priority: must-have
- FR-009: Właściciel domu który wyłożył pieniądze potwierdza odbiór płatności od konkretnego domu-sąsiada — dopiero po potwierdzeniu rozliczenie danego domu jest zamknięte. Priority: must-have
  > Socrates: Podwójne potwierdzenie uznane za krytyczne dla zaufania w systemie. Brak kontr-argumentu.
- FR-010: Właściciel może dołączyć plik (zdjęcie paragonu, skan faktury) do kosztu jako dowód zakupu. Priority: must-have
- FR-011: Koszt staje się read-only (brak możliwości edycji) gdy ktokolwiek potwierdził swoją płatność. Priority: must-have
- FR-012: Rozliczenie widoczne wyłącznie dla właścicieli i gości domów uczestniczących w danym koszcie. Właściciele i goście domów nieučestniczących nie widzą tego rozliczenia. Priority: must-have

### Terminy i przeglądy

- FR-012: Właściciel może dodać rekord przeglądu (nazwa, częstotliwość w miesiącach lub latach). Priority: must-have
- FR-013: Właściciel może oznaczyć datę ostatniego wykonania przeglądu. Priority: must-have
- FR-014: System oblicza i wyświetla przewidywaną datę następnego przeglądu (data ostatniego + częstotliwość) — bez schedulera ani powiadomień. Priority: must-have
  > Socrates: Automatyczne powiadomienia wymagają schedulera/crona — zbędny overhead infrastrukturalny na MVP. Wystarczy obliczenie i wyświetlenie w UI.
- FR-015: Właściciel może dodać notatki do przeglądu (kontakt do wykonawcy, orientacyjny koszt). Priority: must-have
- FR-016: Właściciel i Gość mogą przeglądać historię wykonanych przeglądów i status kolejnych. Priority: must-have

## Business Logic

Aplikacja oblicza i śledzi indywidualne zobowiązania finansowe każdego właściciela wobec puli kosztów wspólnych osiedla, wymuszając obustronną akceptację jako warunek zamknięcia rozliczenia.

Wejście: kwota kosztu wspólnego, lista uczestniczących właścicieli (wskazana ręcznie przy każdym koszcie), opcjonalna notatka o sposobie płatności. Wyjście: kwota zobowiązania per właściciel (podział równy tylko między zaznaczonych), status płatności każdego uczestnika, stan rozliczenia (otwarte / zamknięte). Rozliczenie jest zamknięte dopiero gdy właściciel, który wyłożył pieniądze, potwierdził odbiór płatności od każdego uczestnika — samo oznaczenie "zapłaciłem" przez sąsiada nie wystarczy.

Użytkownik napotyka regułę w dashboardzie: widzi listę aktywnych zobowiązań z kwotami i instrukcją płatności. Po zapłaceniu oznacza to w systemie; płacący potwierdza. Dopiero po tym rozliczenie znika z aktywnych i trafia do historii.

## Non-Functional Requirements

- Dane rozliczeń, kwot i statusów płatności dostępne wyłącznie dla zalogowanych i zatwierdzonych członków osiedla — żaden niezaproszony użytkownik nie widzi tych danych.
- Aplikacja działa poprawnie i czytelnie na smartfonach (layout responsywny na ekranach ≥ 320px szerokości).
- Suma udziałów wszystkich uczestników kosztu jest zawsze równa kwocie całkowitej — aplikacja nie produkuje błędów zaokrąglenia prowadzących do rozbieżności.

## User Stories

### US-01: Rozliczenie kosztu wspólnego

- **Given** zalogowany właściciel osiedla z co najmniej jednym innym właścicielem
- **When** dodaje koszt wspólny: 300 zł za konsultację prawną, z notatką "przelew na konto X"
- **Then** każdy właściciel widzi swój udział (np. 25 zł przy 12 właścicielach) z opisem i instrukcją płatności

#### Acceptance Criteria
- Suma wszystkich udziałów musi być równa kwocie całkowitej (bez zaokrągleń powodujących błąd)
- Każdy właściciel może niezależnie oznaczyć swój udział jako zapłacony
- Płacący (wyłożył 300 zł) widzi status każdej płatności i może ją potwierdzić
- Rozliczenie jest oznaczone jako zamknięte dopiero gdy płacący potwierdził wszystkie płatności

## Non-Goals

- **Brak integracji z bankami** — rozliczenia rejestrowane ręcznie; system nie weryfikuje czy przelew faktycznie dotarł. Celowo: upraszcza MVP i usuwa zależność od API finansowych.
- **Brak formalnego zarządzania** — brak uchwał, głosowań, protokołów zebrań. Aplikacja nie jest systemem HOA; działa na zasadzie zaufania między sąsiadami.
- **Brak automatycznych powiadomień push/email o terminach** — terminy obliczane i wyświetlane w UI na żądanie; żadnego schedulera ani crona w MVP. Celowo: usuwa overhead infrastrukturalny.
- **Brak modułu dokumentów** — upload plików, umów, faktur — v2. MVP skupiony na rozliczeniach i terminach.
- **Brak dyżurów sprzątania** — koordynacja obowiązków rotacyjnych — v2.

## Open Questions

1. **Off-boarding właściciela** — co się dzieje gdy właściciel sprzedaje dom i chce opuścić osiedle? Jak przekazywane są otwarte rozliczenia? Czy konto może być usunięte z aktywnymi zobowiązaniami? Właściciel: do rozstrzygnięcia przed implementacją modułu zarządzania członkami.
2. **Kolizja kodów osiedli** — kod generowany z nazwy może kolidować jeśli dwa osiedla mają tę samą nazwę. Potrzebna strategia unikalności (suffix losowy? UUID?). Właściciel: decyzja techniczna, ale wpływa na UX kodu.
3. **Ostatni właściciel odchodzi** — gdy jedyny Właściciel opuszcza osiedle, nikt nie może zatwierdzać nowych członków. Brak procedury awaryjnej. Właściciel: do rozstrzygnięcia.
4. **Wiele osiedli na jeden email** — użytkownik posiadający domy na dwóch osiedlach (lub wpisany przez pomyłkę dwa razy). Czy jeden email może należeć do wielu osiedli? Właściciel: do rozstrzygnięcia.
5. **RODO i PII** — notatka o sposobie płatności może zawierać numer konta bankowego (dane wrażliwe). Konieczne sprawdzenie wymagań prawnych przed wdrożeniem publicznym. Właściciel: do rozstrzygnięcia przed wdrożeniem publicznym (large scale).
6. **Retencja bez powiadomień** — bez powiadomień email/push użytkownik nie ma powodu wracać między kosztami. Ryzyko adopcji szczególnie dla osiedli z rzadkimi kosztami. Rozważyć w v2: tygodniowe podsumowanie email (nie scheduler na każde zdarzenie).

## Timeline acknowledgment

Acknowledged on 2026-05-24: 10+-tygodniowy MVP wymaga systematycznej pracy wieczorami/weekendami przez 2,5+ miesiąca z tolerancją na okresy niewidocznego postępu; użytkownik świadomie zaakceptował ten koszt.



