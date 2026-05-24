# Git rules

Zasady pracy z gitem w tym projekcie. Zwięzłe i bezwyjątkowe — jeśli sytuacja wymaga odstępstwa, najpierw to ustal z właścicielem projektu.

## 1. Bez `Co-Authored-By: Claude` w commitach

Nigdy nie dodawaj footera `Co-Authored-By: Claude …` (ani analogicznych "co-authored with AI" sygnaturek). Commit ma jednego autora — człowieka, który go zatwierdził.

## 2. Opisy zwięzłe

- Subject line ≤ 72 znaków, w trybie rozkazującym (`add X`, `fix Y`, `refactor Z`), bez kropki na końcu.
- Body (opcjonalne) tylko gdy potrzebne — wyjaśnij **dlaczego**, nie **co** (diff pokazuje co). Akapitki, nie ściana tekstu.
- Brak emojis, brak ASCII-artu, brak motywacyjnych dopisków.

## 3. Conventional commits + SemVer

Format wiadomości: `<type>(<scope>): <subject>`, gdzie `type ∈ {feat, fix, chore, docs, refactor, test, perf, build, ci, style, revert}` a `scope` opcjonalny (np. `feat(auth): add magic-link sign-in`).

Tagi wersji w SemVer (`MAJOR.MINOR.PATCH`):
- `MAJOR` — breaking change w kontrakcie publicznym (schema DB, API).
- `MINOR` — nowa funkcjonalność wstecznie kompatybilna.
- `PATCH` — fix lub poprawka bez zmiany kontraktu.

Pre-release tagi (`-alpha.N`, `-beta.N`, `-rc.N`) dozwolone do oznaczania niedojrzałych wydań.

## 4. Commit dopiero po ręcznej weryfikacji funkcjonalności

Agent **nigdy** nie commituje samodzielnie. Sekwencja jest sztywna:

1. Agent zmienia kod.
2. Agent zostawia zmiany do weryfikacji — opisuje co zrobił i jak to przetestować (`docker compose exec app php artisan test`, ścieżka URL w przeglądarce, scenariusz manualny).
3. **Człowiek uruchamia i potwierdza, że funkcjonalność działa zgodnie z oczekiwaniem.**
4. Dopiero wtedy agent może wykonać `git add` + `git commit` (na wyraźną prośbę).

Jeśli weryfikacja wykryje regresję — agent poprawia, cykl od nowa. Brak skrótów typu "wygląda OK, commitujemy".
