# Docker rules

Setup-gotchas wychwycone w trakcie bootstrapu. Pierwotne ślady błędów: `context/changes/bootstrap-verification/verification.md`.

## macOS UID/GID guard w Dockerfile

`docker/app/Dockerfile` używa `if ! getent group ${GID} >/dev/null` przed `groupadd`, bo macOS GID 20 (`staff`) koliduje z pre-istniejącą grupą w `php:8.4-fpm` (Debian). Bez tego guard-a `docker compose build` pęka na `groupadd: GID '20' already exists`. **Nie usuwaj guard-a przy edycji Dockerfile.**

## Credentials DB to jedno źródło

`DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` żyją w `.env` (gitignored, prawdziwe wartości) i `.env.example` (placeholdery). Service `db` w `docker-compose.yml` czyta je przez auto-substytucję compose'a:

```yaml
environment:
  POSTGRES_DB: ${DB_DATABASE}
  POSTGRES_USER: ${DB_USERNAME}
  POSTGRES_PASSWORD: ${DB_PASSWORD}
```

Single source of truth — Laravel i Postgres dostają te same wartości z `.env`. **Nie duplikuj credentiali inline w `docker-compose.yml`** — to droga do dryftu między tym, co Laravel myśli a tym, na czym faktycznie stoi Postgres.

## Nie odpalaj `composer dev`

Skrypt `composer dev` z `composer.json` to łańcuch `concurrently` z `php artisan serve` + queue + pail + vite. Napisany dla setupu nie-Dockerowego — `php artisan serve` próbuje wstać na porcie 8000, gdzie już słucha nasz nginx container → kolizja.

Kontenerowe odpowiedniki:
- HTTP serwer → `docker compose up -d` (już działa)
- Log stream → `docker compose exec app php artisan pail`
- Queue worker → `docker compose exec app php artisan queue:listen` (tylko gdy `has_background_jobs` faktycznie zostanie aktywowane)
- Vite dev server → osobne `npm run dev` z hosta (jeśli front-end potrzebny — MVP jest server-side renderowany Bladem, vite nie jest krytyczny do startu)

## Reset bazy

```bash
docker compose down -v && docker compose up -d && docker compose exec app php artisan migrate
```

Flaga `-v` w `down` dropuje named volume `postgres-data` (schema + dane znikają). Bez `-v` baza przeżywa restart.

## Rebuild po zmianie Dockerfile

```bash
docker compose up -d --build
```

Build cache'uje warstwy — sam `apt install` + `docker-php-ext-install` (najwolniejszy etap, ~30-40s) idzie z cache jeśli zmieniasz tylko końcowe `RUN`-y.
