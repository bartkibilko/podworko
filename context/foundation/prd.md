---
project: "Podwórko"
version: 1
status: draft
created: 2026-05-24
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
---

## Vision & Problem Statement

Właściciele nieruchomości zarządzający wspólnymi sprawami bez profesjonalnego zarządcy nie mają jednego miejsca do koordynacji. Dotyczy to co najmniej dwóch odrębnych segmentów: (1) zabudowy bliźniaczej i szeregowej — od pary właścicieli po większe osiedla — gdzie właściciele sami organizują przeglądy i rozliczają wspólne koszty; (2) osiedli zamkniętych po odejściu dewelopera, gdzie prywatne drogi, szlabany, oświetlenie i zieleń wymagają samodzielnego finansowania i koordynacji przez właścicieli. W obu przypadkach dokumenty giną w mailach, terminy przeglądów są zapominane, a rozliczenia prowadzone w arkuszach kalkulacyjnych lub wcale — rodząc konflikty i straty finansowe.

Istniejące narzędzia do zarządzania wspólnotami są projektowane dla dużych wspólnot z zarządcą i księgowym — są zbyt ciężkie, zbyt drogie i zbyt formalne dla sąsiadów działających na zasadzie zaufania bez formalnej struktury. Luka na prostą, nieformalną aplikację dla tej skali jest realna i niezagospodarowana.

## User & Persona

### Podstawowa persona

**Właściciel nieruchomości na osiedlu bez zarządcy** — osoba posiadająca dom bliźniacowy, w zabudowie szeregowej lub na osiedlu zamkniętym po odejściu dewelopera, dzieląca wspólne koszty i obowiązki z innymi właścicielami bez profesjonalnego zarządcy. Zarządza wspólnymi sprawami (przeglądy, naprawy, rozliczenia) w wolnym czasie, obok pracy zawodowej. Moment sięgania po produkt: zbliża się przegląd kominiarski i nikt nie pamięta kiedy był ostatni; sąsiad zapłacił za naprawę i chce to rozliczyć z pozostałymi. Część właścicieli posiada nieruchomości na więcej niż jednym osiedlu (np. dom rodzinny + nieruchomość inwestycyjna lub letniskowa) i potrzebuje przełączania kontekstu osiedla w aplikacji.

### Secondary persona

**Gość (np. współmałżonek/partner właściciela)** — osoba powiązana z konkretnym domem na osiedlu. Ma dostęp do przeglądania danych osiedla i rozliczeń własnego domu bez możliwości edycji. Widzi to, co widzi właściciel jej domu, ale nie wprowadza zmian.

## Success Criteria

### Primary
- Właściciel tworzy osiedle → zaprasza sąsiadów kodem → dodaje koszt wspólny (np. 300 zł) z wskazaniem domów uczestniczących → każdy uczestniczący dom widzi swój udział → oznacza zapłatę → płacący potwierdza odbiór → rozliczenie oznaczone jako zamknięte.

### Secondary
- Historia rozliczeń widoczna dla członków uczestniczących domów — przeglądanie poprzednich kosztów i ich statusów.

### Guardrails
- Dane finansowe (rozliczenia, kwoty, statusy płatności) dostępne wyłącznie dla zalogowanych i zatwierdzonych członków osiedla z ważnym kodem dostępu. Żaden niezaproszony użytkownik nie może zobaczyć danych.
- Rozliczenia są widoczne wyłącznie dla domów uczestniczących w danym koszcie — domy nieuczestniczące nie widzą prywatnych rozliczeń między innymi domami.
- Suma udziałów uczestników kosztu jest zawsze dokładnie równa kwocie całkowitej — żadnych rozbieżności wynikających z zaokrągleń.

## User Stories

### US-01: Rozliczenie kosztu wspólnego między domami

- **Given** zalogowany właściciel osiedla z co najmniej jednym innym domem na osiedlu
- **When** dodaje koszt wspólny: 300 zł za konsultację prawną, wskazuje domy uczestniczące, dodaje notatkę "przelew na konto X"
- **Then** każdy uczestniczący dom widzi swój udział (np. 50 zł przy 6 uczestniczących domach) z opisem i instrukcją płatności

#### Acceptance Criteria
- Suma udziałów wszystkich uczestniczących domów jest równa kwocie całkowitej, bez utraty groszy z zaokrągleń
- Każdy właściciel uczestniczącego domu może niezależnie oznaczyć udział swojego domu jako zapłacony
- Płacący (dom który wyłożył 300 zł) widzi status każdej płatności i może ją potwierdzić
- Rozliczenie jest oznaczone jako zamknięte dopiero gdy płacący potwierdził wszystkie płatności od uczestniczących domów
- Domy nieuczestniczące nie widzą tego rozliczenia

## Functional Requirements

### Onboarding i osiedle

- FR-001: Właściciel może stworzyć nowe osiedle podając nazwę. Twórca osiedla otrzymuje rolę Założyciela z dodatkowym uprawnieniem wypisywania innych właścicieli (patrz FR-023). Priority: must-have
- FR-002: System generuje krótki kod dostępu osiedla (maksymalnie 6 znaków) wywodzący się z nazwy osiedla powiększonej o suffix dla unikalności. Przed zapisem osiedla właściciel widzi propozycję kodu i może wielokrotnie wygenerować nowy (np. gdy proponowany kod jest niewygodny, niezrozumiały lub wulgarny). Przy generowaniu wyświetlana jest informacja: "Po zapisie kod nie może być zmieniony". Po zapisie kod jest niezmienny. Priority: must-have
  > Socrates: Rozważano możliwość zmiany kodu po zapisie — odrzucono, bo zmiana unieważniałaby linki dla osób jeszcze nie zapisanych. Reset przed zapisem rozwiązuje przypadek wulgarnych/nieczytelnych kodów bez utraty trwałości po finalizacji.
- FR-003: Użytkownik może dołączyć do osiedla podając email i kod osiedla — system wysyła link aktywacyjny na adres email, po kliknięciu użytkownik wchodzi w stan oczekiwania na zatwierdzenie (pending). Podczas zgłaszania chęci dołączenia użytkownik wskazuje: (a) istniejący dom z listy domów osiedla, do którego chce dołączyć, lub (b) wniosek o utworzenie nowego domu. Priority: must-have
  > Socrates: Zastępuje wcześniejszy model email+hasło+dwa kody. Jeden kod + link aktywacyjny = zero haseł = prostsze dla nietechnicznych użytkowników.
- FR-004: Pending user dla istniejącego domu może być zatwierdzony przez dowolnego Właściciela tego domu lub przez Założyciela. Zatwierdzający nadaje rolę: Właściciel lub Gość. Pending user dla nowego domu może być zatwierdzony wyłącznie przez Założyciela — który tworzy nowy wpis domu i nadaje rolę. Priority: must-have
  > Socrates: Delegacja zatwierdzeń istniejącego domu na Właścicieli tego domu eliminuje wąskie gardło Założyciela dla rutynowych przypadków (małżonek, dorosły domownik). Założyciel pozostaje bramą tylko dla zmian strukturalnych osiedla (nowy dom).
- FR-005: Użytkownik posiadający dostęp do wielu osiedli (np. właściciel kilku nieruchomości) może przełączać aktywny kontekst osiedla za pomocą przełącznika w nagłówku aplikacji. System zapamiętuje ostatnio wybrany kontekst i otwiera go domyślnie przy następnym logowaniu. Priority: must-have

### Rozliczenia

- FR-006: Właściciel może zarejestrować koszt wspólny: kwota całkowita, opis, opcjonalna notatka o sposobie płatności, opcjonalny załącznik. Rejestrujący jest domyślnie uczestnikiem i jedynym płatnikiem na całą kwotę. Priority: must-have
- FR-007: Rejestrujący wskazuje checkboxami które domy uczestniczą — jednostką podziału jest dom, nie indywidualny właściciel. Domyślnie lista zawiera tylko aktualnie aktywnych właścicieli; opcjonalnie można rozszerzyć listę o byłych właścicieli (patrz FR-025). Priority: must-have
  > Socrates: Zmieniono z "właściciel" na "dom" jako jednostkę uczestnictwa — rozliczenia są między domami, nie osobami. Gość powiązany z danym domem widzi rozliczenia swojego domu.
- FR-008: Rejestrujący może dodać dodatkowych płatników (oprócz siebie) — wskazuje który dom-uczestnik również wyłożył pieniądze i jaką kwotę. Każdy płatnik musi być również uczestnikiem. Priority: must-have
  > Socrates: Pierwotny model "jeden płacący + pre-settlement" łamał symetrię gdy dwóch sąsiadów płaciło wspólnie różne kwoty. Lista płatników z kwotami obsługuje ten przypadek wprost. Pojęcie "pre-settlement" zostało wchłonięte przez listę płatników (płatnik = ktoś, kto wyłożył, niezależnie od momentu).
- FR-009: System waliduje przed obliczeniem: (a) suma kwot wszystkich płatników jest dokładnie równa kwocie całkowitej kosztu, (b) lista uczestników nie jest pusta, (c) każdy płatnik jest również uczestnikiem. Niespełnienie którejkolwiek z reguł blokuje zapis kosztu z czytelnym komunikatem błędu wskazującym konkretną regułę. Priority: must-have
- FR-010: System deterministycznie wylicza zobowiązania: dla każdego uczestnika oblicza udział (równy podział kwoty całkowitej), porównuje z kwotą wyłożoną w roli płatnika i tworzy listę instrukcji płatności w postaci par (dom-dłużnik → dom-wierzyciel, kwota). Algorytm minimalizuje liczbę instrukcji widocznych dla pojedynczego dłużnika — większość dłużników otrzymuje dokładnie jedną instrukcję; rozbicie na dwie pojawia się tylko gdy końcówka dopasowania tego wymaga. Priority: must-have
- FR-011: Właściciel domu-dłużnika może oznaczyć płatność z podaniem konkretnej kwoty wpłaconej (pełna lub częściowa). System pokazuje status danej pozycji: "zapłacono X zł z Y zł, zalega Z zł". Wiele częściowych płatności na tę samą instrukcję jest dozwolone. Priority: must-have
- FR-012: Właściciel domu-wierzyciela potwierdza odbiór każdej zgłoszonej płatności (pełnej lub częściowej) niezależnie. Instrukcja płatności jest zamknięta dopiero gdy suma potwierdzonych płatności = należna kwota. Priority: must-have
  > Socrates: Podwójne potwierdzenie uznane za krytyczne dla zaufania w systemie. Brak kontr-argumentu.
- FR-013: Właściciel może dołączyć plik (zdjęcie paragonu, skan faktury) do kosztu jako dowód zakupu — z aparatu telefonu lub z galerii urządzenia. Załącznik jest widoczny dla wszystkich uczestników kosztu. Priority: must-have
- FR-014: Rejestrujący koszt może dodać wpis zwrotu (refundu) przypisany do oryginalnego kosztu — kwota i opcjonalna notatka. Zwrot proporcjonalnie zmniejsza udziały uczestników i aktualizuje obowiązujące instrukcje płatności. Suma zwrotów dla pojedynczego kosztu nie może przekroczyć kwoty całkowitej tego kosztu (walidacja). Priority: must-have
- FR-015: Koszt staje się zablokowany do edycji gdy ktokolwiek potwierdził swoją płatność. Wyjątek: rejestrujący koszt może wymusić edycję zablokowanego kosztu po ręcznym potwierdzeniu (np. po ustnym uzgodnieniu z uczestnikami). Wymuszona edycja resetuje status potwierdzeń pozostałych uczestników i wymaga ponownego potwierdzenia każdej instrukcji płatności. Priority: must-have
- FR-016: Rozliczenie i jego instrukcje płatności widoczne wyłącznie dla właścicieli i gości domów uczestniczących w danym koszcie. Właściciele i goście domów nieuczestniczących nie widzą tego rozliczenia ani w aktywnych, ani w historii. Założyciel nie posiada uprawnienia audytowego do cudzych rozliczeń. Priority: must-have

### Terminy i przeglądy

- FR-017: Właściciel może dodać rekord przeglądu (nazwa, częstotliwość w miesiącach lub latach). Priority: must-have
- FR-018: Właściciel może oznaczyć datę ostatniego wykonania przeglądu. Priority: must-have
- FR-019: System oblicza i wyświetla przewidywaną datę następnego przeglądu (data ostatniego + częstotliwość). Priority: must-have
  > Socrates: Automatyczne powiadomienia wymagają dedykowanej warstwy harmonogramującej — zbędny overhead infrastrukturalny na MVP. Wystarczy obliczenie i wyświetlenie w UI.
- FR-020: Właściciel może dodać notatki do przeglądu (kontakt do wykonawcy, orientacyjny koszt). Priority: must-have
- FR-021: Właściciel i Gość mogą przeglądać historię wykonanych przeglądów i status kolejnych. Priority: must-have

### Off-boarding i zmiana właściciela

- FR-022: Właściciel może samodzielnie wypisać się z osiedla. Operacja wymaga potwierdzenia (checkbox z komunikatem o utracie dostępu i o pozostawieniu historycznych rozliczeń). Jeśli wypisujący się ma aktywne zobowiązania (jako dłużnik lub wierzyciel), wypisanie jest blokowane do czasu rozstrzygnięcia ich przez Założyciela (patrz FR-026). Priority: must-have
- FR-023: Założyciel osiedla może wypisać innego właściciela z osiedla. Operacja wymaga potwierdzenia. Pozostali Właściciele (nie-Założyciele) nie mogą wypisywać innych. Priority: must-have
- FR-024: Wypisany właściciel pozostaje widoczny w historycznych rozliczeniach których był uczestnikiem — historia jest niemodyfikowalna i zachowuje pełne dane o uczestnikach. Wypisany właściciel nie ma już dostępu do logowania w kontekście tego osiedla. Priority: must-have
- FR-025: Przy tworzeniu nowego kosztu domyślnie pokazują się tylko aktualnie aktywni właściciele. Rejestrujący koszt może rozwinąć listę uczestników o byłych właścicieli (np. gdy pojawia się nieuregulowane zobowiązanie z wcześniejszego okresu, w którym dany dom należał do poprzedniego właściciela). Priority: must-have
- FR-026: Podczas wypisywania właściciela z osiedla (samodzielnie lub przez Założyciela) Założyciel decyduje per pozycja dla każdego aktywnego zobowiązania: (a) zachować jako otwarte do uregulowania poza systemem, (b) oznaczyć jako rozwiązane (z obowiązkową notatką), (c) przenieść na nowego właściciela tego samego domu (dostępne tylko jeśli nowy właściciel już dołączył do osiedla). Decyzja jest niemodyfikowalna po zakończeniu wypisania. Priority: must-have
  > Socrates: Rozważano globalny "portfel" salda netto między domami — odrzucono jako przedwczesną abstrakcję. Decyzja per pozycja zachowuje audytowalność każdego konkretnego kosztu i odzwierciedla intuicję użytkownika (każda sytuacja off-boardingu jest inna).

## Non-Functional Requirements

- Dane rozliczeń, kwot i statusów płatności dostępne wyłącznie dla zalogowanych i zatwierdzonych członków osiedla — żaden niezaproszony użytkownik nie widzi tych danych.
- Aplikacja działa poprawnie i czytelnie na smartfonach — pozostaje w pełni używalna na ekranach o szerokości od 320 px wzwyż.
- Suma udziałów wszystkich uczestników kosztu jest zawsze dokładnie równa kwocie całkowitej — aplikacja nie produkuje błędów zaokrąglenia prowadzących do rozbieżności widocznych dla użytkownika.
- Aplikacja przyjmuje załączniki obrazowe do kosztów (zdjęcia paragonów, skany faktur) zarówno z pamięci urządzenia, jak i bezpośrednio z aparatu telefonu.

## Business Logic

Aplikacja rejestruje koszty wspólne osiedla i wylicza zobowiązania między uczestniczącymi domami, wymuszając obustronne potwierdzenie każdej płatności jako warunek zamknięcia danej pozycji rozliczenia.

**Koszt jako transakcja:** Każdy koszt rejestruje fakt, który już się wydarzył (płatność została zrealizowana poza systemem — np. przelew, gotówka, BLIK). System nie służy do zatwierdzania kosztów przed ich poniesieniem; uzgodnienia merytoryczne (czy zlecać konsultację prawną, czy kupować nowe oświetlenie) odbywają się poza systemem i są jego założeniem wstępnym.

**Wejście:** kwota całkowita kosztu, lista uczestniczących domów (które dzielą koszt), lista płatników z kwotami (jeden lub więcej domów, które fizycznie wyłożyły pieniądze — domyślnie sam rejestrujący na całość; suma kwot płatników musi być równa kwocie całkowitej), opcjonalna notatka o sposobie płatności, opcjonalny załącznik (paragon, faktura).

**Wyliczenie:**

1. Każdy uczestniczący dom ma równy udział: `udział = kwota całkowita ÷ liczba uczestników`.
2. Net pozycja każdego domu = `kwota zapłacona w roli płatnika − udział`. Wartość dodatnia → dom-wierzyciel; ujemna → dom-dłużnik; zero → bilans wyrównany, brak zobowiązań.
3. System deterministycznie dopasowuje każdego dłużnika do konkretnego wierzyciela (z konkretną kwotą), dążąc do minimalizacji liczby instrukcji płatności widocznych dla pojedynczego dłużnika. W praktyce: większość dłużników otrzymuje **jedną** instrukcję "zapłać X zł do domu Y"; tylko w rzadkich przypadkach końca dopasowania pojawia się rozbicie na dwie.
4. Zaokrąglenie: domy-dłużnicy płacą kwoty zaokrąglone w górę do 1 grosza; domy-wierzyciele otrzymują kwoty pomniejszone o sumę reszt z zaokrągleń.

**Walidacje** (uruchamiane przed obliczeniem):

- Suma kwot płatników = kwota całkowita
- Każdy płatnik jest też uczestnikiem (nie można "zapłacić za" bez uczestnictwa)
- Lista uczestników niepusta

**Płatności częściowe:** uczestnik-dłużnik może oznaczyć płatność niepełną kwotą (np. zapłacono 20 zł z 25 zł). System wyświetla "zapłacono X z Y, zalega Z". Każda częściowa płatność jest osobno potwierdzana przez wierzyciela; pozycja zamyka się dopiero gdy suma potwierdzonych płatności = należny udział.

**Refundy / zwroty:** rejestrujący koszt może dodać wpis zwrotu przypisany do oryginalnego kosztu (np. zwrot części zaliczki od wykonawcy). Zwrot proporcjonalnie zmniejsza udziały uczestników i odpowiednio koryguje obowiązujące instrukcje płatności. Zwrot na koszt z potwierdzonymi już płatnościami wymaga przejścia ścieżki "wymuszonej edycji" (analogicznie do edycji zablokowanego kosztu).

**Off-boarding z otwartymi pozycjami:** wypisanie właściciela domu z aktywnymi zobowiązaniami wymaga decyzji Założyciela per pozycja: (a) zachować jako otwartą do uregulowania, (b) oznaczyć jako rozwiązaną poza systemem (notatka), (c) przenieść na nowego właściciela tego samego domu (jeśli już dołączył). Mechanizm minimalistyczny — nie wprowadzamy globalnego "portfela" salda netto między domami; zamiast tego decyzja per pozycja zachowuje audytowalność każdego konkretnego kosztu.

**Widoczność:** rozliczenie jest widoczne wyłącznie dla właścicieli i gości uczestniczących domów. Domy nieuczestniczące nie widzą tego rozliczenia ani w aktywnych, ani w historii. Założyciel nie ma globalnego wglądu w cudze rozliczenia — Założycielstwo to uprawnienie strukturalne (tworzenie domów, wypisywanie), nie audytowe.

**Doświadczenie użytkownika:** dłużnik widzi w widoku osiedla listę swoich aktywnych zobowiązań: "Zapłać 25 zł do domu Y (konsultacja prawna, 2026-04-12)". Po zapłaceniu oznacza pozycję jako zapłaconą (z kwotą faktycznie wpłaconą — pełną lub częściową); wierzyciel potwierdza odbiór. Pozycja znika z aktywnych dopiero po potwierdzeniu pełnej kwoty.

## Access Control

Sign-in: użytkownik podaje adres email i kod osiedla. System wysyła link aktywacyjny na adres email. Kliknięcie linka loguje użytkownika — brak haseł.

Sign-up: jeden niezmienny kod per osiedle. Nowy użytkownik wpisuje email + kod → otrzymuje link aktywacyjny → po jego kliknięciu wchodzi w stan Oczekujący → istniejący Właściciel zatwierdza i nadaje rolę (Właściciel lub Gość) oraz przypisuje do konkretnego domu.

Role i uprawnienia:

| Rola         | Tworzenie/edycja kosztów | Edycja terminów | Zatwierdzanie pending na istniejący dom | Zatwierdzanie pending na nowy dom | Wypisywanie właścicieli | Widoczność rozliczeń           |
|--------------|--------------------------|-----------------|-----------------------------------------|-----------------------------------|-------------------------|--------------------------------|
| Założyciel   | tak                      | tak             | dowolny dom                             | tak                               | tak                     | rozliczenia swojego domu       |
| Właściciel   | tak                      | tak             | tylko własny dom                        | nie                               | tylko siebie            | rozliczenia swojego domu       |
| Gość         | nie                      | nie             | nie                                     | nie                               | nie                     | rozliczenia domu, do którego należy |
| Oczekujący   | nie                      | nie             | nie                                     | nie                               | nie                     | tylko nazwa osiedla + lista członków |

Założyciel to twórca osiedla — jeden na osiedle. W przypadku gdy Założyciel wypisze się z osiedla i nie pozostanie żaden Założyciel, kolejni Założyciele są ustanawiani wyłącznie przez kontakt z supportem aplikacji (procedura poza systemem). Niezalogowany użytkownik trafiający na chronioną trasę jest przekierowany do strony logowania. Goście są powiązani z konkretnym domem — widzą perspektywę swojego domu i nie są odrębną stroną rozliczenia.

Kontekst wielu osiedli: użytkownik może być członkiem wielu osiedli (np. właściciel kilku nieruchomości). Aktywny kontekst osiedla wybiera się w nagłówku aplikacji. System zapamiętuje ostatnio wybrany kontekst i przywraca go przy kolejnym logowaniu.

## Non-Goals

- **Brak integracji z bankami** — rozliczenia rejestrowane ręcznie; system nie weryfikuje czy przelew faktycznie dotarł. Celowo: upraszcza MVP i usuwa zależność od zewnętrznych API finansowych.
- **Brak formalnego zarządzania** — brak uchwał, głosowań, protokołów zebrań. Aplikacja nie jest systemem do prowadzenia formalnej wspólnoty mieszkaniowej; działa na zasadzie zaufania między sąsiadami.
- **Brak automatycznych powiadomień push lub email o terminach** — terminy są obliczane i wyświetlane na żądanie w aplikacji. Celowo: usuwa potrzebę warstwy harmonogramującej w MVP.
- **Brak modułu ogólnego przechowywania dokumentów** — upload umów, regulaminów i innych dokumentów osiedla — przesunięte do v2. Pojedyncze załączniki do kosztów (paragony, faktury) są w MVP jako dowody zakupu, ale nie jako pełnoprawna baza dokumentów.
- **Brak dyżurów sprzątania i koordynacji obowiązków rotacyjnych** — przesunięte do v2.

## Open Questions

1. **RODO i dane wrażliwe** — notatka o sposobie płatności (FR-006) i kontakt do wykonawcy w notatce do przeglądu (FR-020) mogą zawierać numery kont bankowych i dane osobowe. Konieczne sprawdzenie wymagań prawnych (umowa powierzenia, podstawa prawna przetwarzania, retencja danych byłych właścicieli widocznych w historii — patrz FR-024) przed wdrożeniem publicznym. Owner: użytkownik. By: przed wdrożeniem publicznym (target_scale: large).
2. **Minimalny system powiadomień (v2)** — bez powiadomień email/push użytkownik nie ma silnego bodźca do regularnych powrotów między kosztami. Ryzyko adopcji szczególnie dla osiedli z rzadkimi kosztami. Rozważyć w v2 minimalny system powiadomień (np. lekki tygodniowy/miesięczny dygest e-mail bez harmonogramu per-zdarzenie, lub powiadomienie wyłącznie przy zmianach dotyczących użytkownika). Zakres do określenia po obserwacji pierwszych użytkowników. Owner: użytkownik. By: ewaluacja po pierwszych użytkownikach MVP.
3. **Magazynowanie załączników (paragony, faktury)** — FR-013 wymaga przechowywania plików obrazowych. Decyzja o limicie rozmiaru, kompresji po stronie klienta, formacie i polityce retencji jest zależna od wybranej technologii storage'u i przesunięta do etapu wyboru stacku. Owner: tech-stack-selector. By: w trakcie wyboru stacku.
4. **Algorytm generowania kodu osiedla** — FR-002 określa wymóg "krótki kod ≤ 6 znaków wywodzący się z nazwy + suffix dla unikalności" oraz dopuszcza wielokrotny reset przed zapisem. Konkretna logika (które litery z nazwy, format suffixu, sposób unikania kolizji) to decyzja implementacyjna — wpływa na czytelność kodu, ale nie zmienia kontraktu produktowego. Owner: implementacja. By: przy implementacji modułu tworzenia osiedla.
5. **Algorytm dopasowania dłużnik → wierzyciel (multi-payer)** — FR-010 określa cel produktowy: minimalizacja liczby instrukcji płatności widocznych dla pojedynczego dłużnika, deterministyczne dopasowanie. Konkretna logika (greedy "największy dług → największa nadwyżka", inny wariant) to decyzja implementacyjna i nie zmienia kontraktu produktowego, ale wpływa na rozkład rozbić w przypadkach krawędziowych. Owner: implementacja. By: przy implementacji modułu rozliczeń.
6. **Zmiana właściciela domu (sukcesja po sprzedaży)** — FR-022 do FR-026 obsługują wypisanie właściciela, ale nie definiują pełnego przepływu sukcesji dla sytuacji "dom został sprzedany nowemu właścicielowi, który chce dołączyć". Czy nowy właściciel automatycznie dziedziczy historyczny dostęp do rozliczeń tego domu, czy zaczyna z czystą historią? Powiązane z FR-024, FR-026(c) i RODO (pytanie 1). Owner: użytkownik. By: przed implementacją modułu zarządzania członkami.
7. **Cykliczne i powtarzalne koszty (v2)** — w MVP każdy koszt wprowadzany jest ręcznie od zera. W v2 rozważyć szablony kosztów lub funkcję "powtórz transakcję" dla typowych powtarzających się wydatków (sprzątanie miesięczne, śmieci kwartalne). Owner: użytkownik. By: po obserwacji pierwszych użytkowników MVP.
8. **Płatności częściowe — limit liczby rat?** — FR-011 dopuszcza wiele częściowych płatności na jedną instrukcję. W skrajnym przypadku dłużnik może oznaczać po 1 zł 25 razy. Czy nakładać limit (np. max 5 rat na instrukcję)? Brak limitu = lepsza elastyczność, ale ryzyko zaśmiecenia historii. Owner: użytkownik. By: po obserwacji pierwszych użytkowników MVP.
