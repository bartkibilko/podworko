# Coding rules

Zasady stylu kodu dla projektu Podworko. Profil: **rygorystyczny, niemainstream-Laravel** (strict types + Codeception + PHPStan od dnia zero). Wybory podjęte świadomie — patrz `tech-stack.md` (kompensata `typed: false`) i historia konwersacji bootstrap chain.

Adaptacja Karpathy-style guidance (`andrej-karpathy-skills/CLAUDE.md`) do Laravel 13.8 / PHP 8.4 / Podworko-domain.

## Code style

- `declare(strict_types=1);` jako pierwsza dyrektywa w **każdym** pliku `.php` pod `app/`, `database/`, `tests/`. Pint w trybie autofix nie dorzuca tego sam — odpowiedzialność dewelopera/agenta.
- **Native typing wszędzie**: parametry, zwroty, properties klas, **w tym Eloquent models**. Nie używaj `@property` w docblockach na Eloquencie — deklaruj kolumny przez native typed properties z `protected` / `public` widzialnością tam gdzie sensowne, plus `casts()` metoda dla typów których atrybuty nie wyrażą bezpośrednio (datetime, enum, json). Świadomie odchodzimy od mainstream-Laravel patternu `@property-only`, bo native types daje PHPStan + IDE silne kontrakty bez interpretacji docblock-ów.
- PSR-12 base. `vendor/bin/pint` runs as pre-commit hook (do wpięcia osobno). Brak personal style overrides w `pint.json`.
- Jeden class per plik (autoload Laravela i tak to wymusza).
- Trailing newline at EOF.
- Imports posortowane alfabetycznie (Pint to robi); grupy `use App\…` / `use Illuminate\…` / `use Vendor\…` rozdzielone pustą linią opcjonalnie.

## Naming

- **Język**: angielski w kodzie, DB, route segments, asset paths. Polski **tylko** w UI strings (`resources/views/`, `resources/lang/pl/`), error messages renderowanych userowi, oraz w PRD / komentarzach domenowych jeśli wyjaśniają polskie pojęcia (np. "Założyciel = founder, see PRD § Access Control").
- Klasy: `PascalCase`, pełne słowa (`SettlementEntry`, nie `SetlEnt`).
- Metody: `camelCase`, czasownikowe (`calculatePayout`, nie `payout`).
- Properties: `camelCase` jeśli native; snake_case dla Eloquent column attributes (Laravel convention dla DB-mapping).
- Constants / enum cases: `UPPER_SNAKE_CASE`.
- Database tables: `snake_case` plural (`households`, `cost_participations`, `payment_instructions`).
- DB columns: `snake_case` (`registered_at`, `confirmed_amount_grosze`).
- Routes: kebab-case, RESTful (`POST /neighbourhoods/{neighbourhood}/costs`).
- Migration filenames: `<timestamp>_<verb>_<subject>.php` (`create_households_table`, `add_archived_at_to_costs`).

## Abstraction

- **No premature service layer.** Controller → Model jest OK dopóki repetycja nie wymusi ekstrakcji. Nie twórz `app/Services/CostService.php` "na zapas".
- **No repository pattern.** Eloquent jest naszą warstwą danych. `Cost::query()->where(...)` w kontrolerze jest poprawne; opakowywanie w `CostRepository::findByX()` to bezsensowny middleman dla solo-MVP.
- **Action classes** (single `__invoke()` method, w `app/Actions/`) tylko gdy ta sama operacja biznesowa jest wywoływana z 2+ entry points (np. controller + console command + queue job). Inaczej inline w kontrolerze.
- **Form Requests** dla walidacji która powtarza się w 2+ kontrolerach. Inaczej `$request->validate([...])` inline.
- **DTOs / Value Objects** dla domain primitives które mają zachowanie poza data-holdingiem. `Money` (integer grosze + arithmetic + format) — TAK. "UserDTO" które tylko duplikuje User model — NIE.

## Defensive coding

- **Brak `try/catch` w app code.** Laravel ma globalny exception handler (`bootstrap/app.php → withExceptions()`) który loguje i renderuje. Łap wyjątek tylko gdy konwertujesz third-party exception na domain exception (np. `Stripe\StripeException` → `PaymentFailedException`).
- **Brak null-checków na rzeczy które framework gwarantuje że istnieją.** Route model binding hits przed kontrolerem; `function show(Cost $cost)` daje non-null `$cost`. Nie pisz `if ($cost === null) { abort(404); }`.
- **Validate na boundaries** (Form Requests, queue payloads, console arguments), trust internal flow. Po przejściu walidacji nie dubluj sprawdzeń w warstwie poniżej.
- **Jeśli invariant złamany — rzucaj.** Brak silent degradation typu "no co tu poradzić, zwracam pustą listę". Domain exception → user widzi 422 / 500 / dedicated error page. To jest sygnał do naprawy, nie do ukrycia.

## Money

- **Nigdy float na wartościach monetarnych.** Storage: `bigint` w DB (kolumny suffixowane `_grosze`, np. `amount_grosze`, `confirmed_amount_grosze`). 1 PLN = 100 grosze.
- **Arytmetyka w grosze.** Konwersja na PLN tylko w display layer (Blade helper / API resource).
- **Centralna `Money` klasa** w `app/Domain/Money.php`: immutable, integer-backed, methods `add()`, `subtract()`, `multiply(int)`, `divide(int): array` (zwraca podzielone equal shares + remainder), `format(): string`. Wszystkie operacje pieniężne idą przez nią — nie liczy nikt inny.
- **Strategia zaokrąglenia per FR-010**: debtor płaci kwoty zaokrąglone w górę do grosza; creditor otrzymuje sumę pomniejszoną o reszty. Encoduj w jednej klasie `SettlementAllocator` (lub similar) — nie rozsiewaj logiki zaokrąglenia po kontrolerach.

## Visibility / tenancy

- **FR-016 (visibility scoped to participating households)** egzekwowane przez Eloquent global scope na `Cost` / `Settlement` modelu (`ParticipatingHouseholdScope` w `app/Models/Scopes/`). **Nigdy nie filtruj w kontrolerze** — query scope jest jedyną linią obrony. Test pokrycia: musi istnieć Acceptance test próbujący zobaczyć cudzy koszt jako non-participant i dostający 404/403.
- **FR-005 (per-neighbourhood context)** — gdy podejmiemy decyzję (otwarte pytanie w PRD: spatie/laravel-multitenancy vs hand-rolled `tenant_id` global scope), wybrane podejście jest **jedyną** drogą scopingu. Ad-hoc `->where('neighbourhood_id', session('current'))` zabronione.
- **Założyciel NIE ma audit access** do cudzych settlement. To DELIBERATE non-power per PRD. Pokusa "ale to admin, niech widzi" — odrzucamy.

## Migrations

- **Nigdy nie edytuj scommitowanej migracji.** Nowa migracja żeby ewoluować schemat. Pojedyncze wyjątki tylko gdy migracja jeszcze nie wyszła z gałęzi feature-owej (przed merge'em).
- **Każda migracja ma działający `down()`** który czysto odwraca. Bez `Schema::dropIfExists` tam gdzie nie powinno.
- Migration names czasownikowe: `create_households_table`, `add_archived_at_to_costs`, `rename_amount_to_amount_grosze_on_costs`.
- **Seeders** wyłącznie dla danych demo / test fixtures, nigdy dla production-required reference data (te idą jako migracja — np. lista ról).

## Tests (Codeception)

Wybrane jako test framework — patrz tech-stack.md i decyzje konwersacyjne. **PHPUnit zostanie odinstalowany** po przejściu testów (codeception sam korzysta z PHPUnit pod spodem, ale jako transitive).

Pierwsze kroki (do wykonania w osobnej turze gdy bierzemy się za testy):

```bash
docker compose exec app composer require --dev codeception/codeception codeception/module-laravel codeception/module-db codeception/module-asserts
docker compose exec app vendor/bin/codecept bootstrap
```

Suite organization:

- `tests/Acceptance/` — scenariusze user-facing (przeklikania UI / API calls). BDD-style, Cest classes. Tutaj **load-bearing test coverage dla FR-011/FR-012** (dwustronne potwierdzenia płatności).
- `tests/Functional/` — testy logiki domain bez wychodzenia do warstwy HTTP. Tutaj `SettlementAllocator`, `Money`, deterministyczne wyliczenia z FR-010.
- `tests/Unit/` — czysta arytmetyka i value objects bez framework boot. Minimalna ilość, większość testów ma sens z framework-em.

Konwencje:
- Test classes: `…Cest.php` (e.g. `tests/Acceptance/CostSettlementCest.php`).
- Metody opisują zachowanie: `public function costLocksAfterFirstPaymentConfirmation(AcceptanceTester $I)`, **nie** `public function testCostSave()`.
- Jeden Cest = jeden obszar funkcjonalny (np. `CostCreationCest`, `PaymentConfirmationCest`).
- Database state per test: `\Helper\Acceptance` z `cleanup: true` (suite config).

Bundled `tests/Feature/ExampleTest.php` i `tests/Unit/ExampleTest.php` z Laravel-default scaffoldu — **usunięte** przy migracji do codeception (zachowane historycznie w git, można podejrzeć w pierwszym commicie).

`docker compose exec app php artisan test` **nie używamy** (to wrapper na PHPUnit). Primary test command: `docker compose exec app vendor/bin/codecept run` (lub `--suite Acceptance` per suite).

## Static analysis (PHPStan + Larastan)

Wpinamy od dnia kodowania, level 5. Kompensata `typed: false` z tech-stack-selector.

Pierwsze kroki:

```bash
docker compose exec app composer require --dev phpstan/phpstan larastan/larastan
```

Konfiguracja `phpstan.neon` w root:

```yaml
includes:
    - vendor/larastan/larastan/extension.neon

parameters:
    level: 5
    paths:
        - app
        - database/factories
        - database/seeders
        - tests
    excludePaths:
        - bootstrap/cache
```

CI gate (przy wprowadzaniu GitHub Actions per hand-off `ci_provider: github-actions`): `docker compose exec app vendor/bin/phpstan analyse --memory-limit=512M`. PR-y z `phpstan` errorami **nie mergeable**.

Level 5 baseline → podnosimy do 6 / 7 / 8 gdy codebase stabilny (każdy bump w osobnym PR z dedicated effort na naprawę).

## Don't

- **Don't generate placeholder/stub code w business logic.** Jeśli nie wiesz co powinno się zdarzyć — **zapytaj**, nie zostawiaj `// TODO: implement` w ścieżce którą ktoś za chwilę kliknie.
- **Don't `composer require`** bez świadomej decyzji (dependency creep jest realny; usuwanie pakietu z Laravel projektu po pół roku jest bólem). Każda nowa zależność na poziomie dyskusji "naprawdę nie ma tego w Laravel core / nie dam się napisać w 20 linijkach?".
- **Don't bypass `git-rules.md`** — commit dopiero po ręcznej weryfikacji funkcjonalności przez człowieka.
- **Don't `php artisan serve`** w żadnym workflow (mamy nginx + php-fpm w kontenerach — patrz `docker-rules.md`).
- **Don't `composer dev`** (patrz `docker-rules.md`).
