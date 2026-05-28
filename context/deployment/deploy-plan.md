---
project: podworko
deployed_at: 2026-05-27T17:30:33Z
platform: railway
region: europe-west4-drams3a
public_url: https://app-production-c40b.up.railway.app
status: ok
inputs:
  - context/foundation/infrastructure.md
  - context/foundation/tech-stack.md
railway:
  project_id: da537496-fe0a-4ee8-935f-889ef40a7826
  environment: production
  environment_id: 5d341621-f538-47d6-8c05-6080832d4c6e
  app_service_id: b02bf4c2-0a3b-40e2-98b8-3c47adec769b
  postgres_service: managed (railway postgres-ssl:18)
  final_deployment_id: c2decaa4-4ae5-48ce-85bb-ce809fc1e3a8
---

## Plan (approved 2026-05-27)

Pierwszy production deploy Laravela na Railway zgodnie z hand-off-em z `context/foundation/infrastructure.md` (Railway 5/5 agent-friendly criteria, anti-bias check passed, $10-18/mo estymowane). 5-etapowy plan:

1. **Repo preparation** — utworzenie production-shaped Dockerfile dla Railway (osobny od dev `docker/app/Dockerfile`), nginx + php-fpm + supervisord bundled w jednym container-ze (Railway single-process-per-service model). Trust proxies w `bootstrap/app.php`. `.dockerignore` + `railway.json` IaC.
2. **Local verify** — build i run obrazu lokalnie zanim cokolwiek dotknie Railway billing.
3. **Railway setup** — user-side: install CLI, login, `railway init`, `railway add` Postgres.
4. **First deploy + migrate + domain** — env vars (z reference vars na Postgres), `railway up`, weryfikacja przez `railway ssh` lub entrypoint, generowanie public domain.
5. **Persist plan + verify** — ten plik + curl test.

## Execution log

### Stage 1 — Repo preparation (committed pre-deploy)

Nowe pliki:
- `docker/railway/Dockerfile` — multi-stage: composer:2.9 → php:8.4-fpm + nginx + supervisor + opcache + pdo_pgsql/gd/bcmath/intl/zip/exif + gettext-base
- `docker/railway/nginx.conf.template` — production nginx config; envsubst na `${PORT}` przy boot
- `docker/railway/supervisord.conf` — nodaemon=true, php-fpm priority 5 + nginx priority 10, oba do stdout/stderr
- `docker/railway/php.ini` — OPcache enabled (memory 256MB, 20000 files, validate_timestamps=0), expose_php=0, upload_max_filesize=25M
- `docker/railway/entrypoint.sh` — envsubst PORT → uruchom `php artisan migrate --force --no-interaction` (fail-fast) → exec supervisord
- `railway.json` — `dockerfilePath: docker/railway/Dockerfile`, `healthcheckPath: /up`, restart ON_FAILURE max 3 retries
- `.dockerignore` (root) — wyklucza `.git/`, `vendor/`, `node_modules/`, `.env`, `docker/{app,web}/`, `docker-compose.yml`, `context/`, `.claude/`, tests, editor configs

Update:
- `bootstrap/app.php` — `$middleware->trustProxies(at: '*')` (Railway terminuje TLS na edge, bez tego url() generuje http://)

### Stage 2 — Local verify

```bash
docker build -f docker/railway/Dockerfile -t podworko-railway:test .
# Pierwsze pęknięcie: composer binary missing w stage-1
# Fix: COPY --from=composer:2.9 /usr/bin/composer /usr/local/bin/composer
docker build -f docker/railway/Dockerfile -t podworko-railway:test .  # SUCCESS
docker run --rm -d --name podworko-railway-test -p 18080:80 -e APP_KEY="..." -e ... podworko-railway:test
curl -sI http://localhost:18080/up   # HTTP/1.1 200
curl -sI http://localhost:18080/     # HTTP/1.1 200
```

Verification: nginx + php-fpm RUNNING state pod supervisord, oba HTTP 200, brak crashloop.

### Stage 3 — Railway setup (user-driven)

```bash
brew install railway                                    # CLI v4.65.0
railway login                                           # OAuth browser flow
railway init                                            # podworko project created (ID da537496-fe0a-4ee8-935f-889ef40a7826)
railway add                                             # Postgres template, initial provision w SFO (Railway default)
```

**Region drift detected**: Postgres provisioned w `sfo` zamiast EU (Project Default Region był nieustawiony). Per `infrastructure.md` zakładane było AMS. Fix: Project Settings → Default Region → Amsterdam, potem delete + re-add Postgres (zero data loss, baza była pusta).

### Stage 4 — First deploy + migrate + domain

```bash
# Empty app service (created z `railway add --service app` mimo network timeout)
railway add --service app

# Env vars w jednej komendzie (`--skip-deploys` żeby nie trigger redeploy per var)
APP_KEY="base64:$(openssl rand -base64 32)"
railway variables \
  --set "APP_KEY=$APP_KEY" \
  --set "APP_ENV=production" \
  --set "APP_DEBUG=false" \
  --set "APP_URL=https://placeholder.up.railway.app" \
  --set "LOG_CHANNEL=stderr" \
  --set "SESSION_DRIVER=database" \
  --set "CACHE_STORE=database" \
  --set "DB_CONNECTION=pgsql" \
  --set 'DB_HOST=${{Postgres.PGHOST}}' \
  --set 'DB_PORT=${{Postgres.PGPORT}}' \
  --set 'DB_DATABASE=${{Postgres.PGDATABASE}}' \
  --set 'DB_USERNAME=${{Postgres.PGUSER}}' \
  --set 'DB_PASSWORD=${{Postgres.PGPASSWORD}}' \
  --skip-deploys --service app
```

Reference variables (`${{Postgres.PGHOST}}` itp.) Railway rozwiązuje na server-side na real values: `postgres.railway.internal:5432`, user=`postgres`, db=`railway`. Internal DNS działa tylko w obrębie Railway private network projektu.

**Migration approach pivot**: pierwotny plan miał `railway run --service app php artisan migrate --force`, ale Railway v4 `run` wykonuje LOKALNIE z injected env (internal DNS nie wyresolvuje z hosta). `railway ssh` wymagał SSH key registration. Wybrana ścieżka: dodanie `php artisan migrate --force --no-interaction` do `docker/railway/entrypoint.sh` z fail-fast semantyką. Konsekwencja: każdy container start uruchamia migrate (idempotent w Laravelu).

```bash
# Pierwszy deploy — build ~3 min, success
railway up --service app                          # deployment 0075cf1e... (initial)
# Redeploy z migrate-in-entrypoint
railway up --service app                          # deployment c2decaa4... (final)
# Generuj publiczny URL
railway domain --service app                      # https://app-production-c40b.up.railway.app
# Update APP_URL na rzeczywisty
railway variables --set "APP_URL=https://app-production-c40b.up.railway.app" --skip-deploys --service app
```

Migrate logs z container start (final deployment):
```
INFO  Preparing database.
Creating migration table .................. 11.16ms DONE
INFO  Running migrations.
0001_01_01_000000_create_users_table ...... 25.85ms DONE
0001_01_01_000001_create_cache_table ...... 13.35ms DONE
0001_01_01_000002_create_jobs_table ....... 18.37ms DONE
```

### Stage 5 — End-to-end verification

```
GET https://app-production-c40b.up.railway.app/up      → HTTP/2 200 (Laravel health)
GET https://app-production-c40b.up.railway.app/        → HTTP/2 200 (Blade welcome page)
Body content                                            → Tailwind v4.0.7 Laravel welcome HTML
Region                                                  → EU West (Amsterdam)
TLS                                                     → Railway edge terminuje, Laravel z trust-proxies generuje https:// URLs
```

## Outstanding from infrastructure.md risk register

| Risk | Status post-deploy | Next action |
|------|--------|------|
| Idle worker / queue:listen burning compute | N/A — `has_background_jobs: false`, brak workera | Re-skoroyć gdy podejdziemy do FR-022/FR-026 lub powiadomień v2 |
| Storage Buckets pre-GA pricing change | N/A — nie używamy Buckets jeszcze | Wpiąć Flysystem `disk` abstrakcję przed implementacją FR-013 |
| Amsterdam-only EU outage | Single-AZ, akceptowane | Plan B: Fly.io FRA z naszego portable Dockerfile (już przetestowany lokalnie) |
| Railpack auto-detection drift | N/A — używamy Dockerfile, nie Railpack | Trzymać Dockerfile-first przy zmianach |
| Postgres backup restore drill | **DO ZROBIENIA** | W tym tygodniu: snapshot Postgres + test restore na staging env; udokumentować w `context/foundation/runbooks/postgres-restore.md` |
| Build cache cold-starts | First build ~3 min, redeploy ~1 min (layer cache działa) | Akceptowalne dla iteracji MVP |
| Detached postgres-volume 98MB | Pozostał z pre-migration Postgres | Cleanup w dashboard → Volumes → Delete (low priority, ~$0/mo dopóki <1GB) |

## Notes on outputs that differed from plan

1. **`railway run` nie nadawała się do migracji** — v4 wykonuje lokalnie z injected env, internal DNS host-niewidoczny. Pivot na `migrate w entrypoint`.
2. **Region default Railway = SFO**, nie EU. Project Default Region trzeba ustawić ZANIM `railway add`, w przeciwnym razie services lądują w SFO. Migracja wymaga delete + re-add (zero data, łatwe).
3. **`railway add --service` jest interactive** mimo flag — pyta o "Enter a variable" nawet bez `--variables`. Workaround: poll `railway status` żeby zobaczyć czy service został utworzony mimo timeout-u (był).
4. **Build polling pattern**: Railway pokazuje rolling state `app: ● Online · Building (15s)` podczas deploya. Grep `"app: ● Building"` nie match-uje tego dual-state stringu — poll filter musi być `"app.*Building"` żeby tolerować deploy transitions.

## Next deployment iteration TODOs (NOT in this run)

- Custom domain `podworko.pl` (lub similar) — gdy nazwa zdecydowana
- Postgres backup restore drill (z risk register)
- Cleanup detached `postgres-volume` 98MB w dashboard
- CI/CD: GitHub Actions z `railway up` na merge to main (per tech-stack `ci_default_flow: auto-deploy-on-merge`)
- Codeception + PHPStan w CI gate przed deploy
- Staging environment (Railway environment fork) z PR previews
- OPcache fine-tuning po pierwszych metrykach produkcyjnych
- Storage Buckets / R2 dla FR-013 załączników gdy implementujemy
- Trusted proxy CIDR narrow-down (obecnie `*`; lepiej Railway egress IP ranges gdy upublicznione)
