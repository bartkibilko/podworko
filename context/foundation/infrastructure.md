---
project: podworko
researched_at: 2026-05-24
recommended_platform: Railway
runner_up: DigitalOcean App Platform
context_type: mvp
tech_stack:
  language: PHP
  framework: Laravel 13.8
  runtime: PHP 8.4 (containerized via php-fpm)
---

## Recommendation

**Deploy on Railway.** Najtańszy wybór dla naszego profilu (cost-sensitive solo MVP, ~$10-18/mo vs $25-50 dla alternatyw), 5/5 pass na agent-friendly criteria, najlepsza w segmencie integracja MCP (official server z OAuth, destructive ops excluded), llms.txt + markdown-on-demand docs. Postgres GA + Storage Buckets pokrywają co-location preference; Amsterdam (eu-west) wystarczy dla single-region PL deploymentu. Wybór nadpisuje wcześniejszy `deployment_target: fly` z `tech-stack.md` — tamten był defaultem ze starter card, nie świadomą decyzją.

## Platform Comparison

Hard filter zastosowany na samym początku: **wymóg PHP runtime** wykluczył Cloudflare Workers, Vercel i Netlify (serverless functions Node/Go/Python only). Zbadane 4 kandydatów obsługujących Docker-based PHP w EU:

| Platform | CLI-first | Managed/serverless | Agent-readable docs | Stable deploy API | MCP / first-class | Pass count |
|----------|-----------|--------------------|--------------------|--------------------|-----|----|
| **Railway** | Pass (`railway` CLI, agents-flag install) | Pass (container PaaS) | Pass (`llms.txt` + `.md` suffix) | Pass (rollback via redeploy) | Pass (**best-in-class** MCP) | **5P** |
| **DO App Platform** | Pass (`doctl apps`) | Pass (buildpacks + Docker) | Pass (`llms.txt` + `llms-index.json`) | Pass (`.do/app.yaml` IaC) | Pass (DO MCP, 9 services) | **5P** |
| **Render** | Pass (CLI v2.18.0 GA) | Pass | Pass (`/llms.txt` + `/docs/llms.txt`) | Pass | Pass (Render MCP GA od Aug 2025) | **5P** |
| **Fly.io** | Pass (`flyctl` dojrzały) | Pass (managed VMs) | **Partial** (brak llms.txt) | Pass | Pass (`fly mcp server` *experimental*) | **4P + 1Partial** |

Trzy platformy z 5/5; soft-weights z wywiadu rozcinają remis: Q2 (minimize cost) penalizuje Fly.io (~$45-50/mo, Managed Postgres Basic $38/mo sam), Q5 (co-location preferowane) penalizuje Render (Object Storage **alpha, waitlist-only** od marca 2026 — wymusza external S3 dla FR-013 paragonów).

### Shortlisted Platforms

#### 1. Railway (Recommended)

- **Cost**: $5/mo Hobby plan + ~$5-13/mo usage (compute + Postgres + volume) ≈ **$10-18/mo** total
- **Co-location**: Postgres GA, Volumes GA, Storage Buckets (S3-compatible, free egress, $0.015/GB-mo — GA status nieoznaczony explicit ale działa)
- **Agent UX**: `llms.txt` published, dowolny `docs.railway.com/*` URL z `.md` suffix zwraca markdown, oficjalny Railway Docs Agent Skill, oficjalny MCP server (`npx -y @railway/mcp-server`) z OAuth i destructive ops excluded by design
- **Region**: `europe-west4-drams3a` (Amsterdam only — patrz risk register)
- **CLI**: `link`, `up`/`deploy`, `redeploy`, `logs --build`, `variable set/list/delete`, `environment`, `ssh`, `upgrade`. Brak first-class `rollback` (przez `redeploy` z prior deployment ID — scriptable)
- **Laravel-specific**: Railpack (następca Nixpacks) auto-detects Laravel + uruchamia przez php-fpm + Caddy. Mamy własny Dockerfile więc to nieistotne — recommendation: Dockerfile-first podejście niezależnie od Railpack defaults

#### 2. DigitalOcean App Platform (Runner-up)

- **Cost**: ~$25/mo total ($5 app shared 512MB + $15 Managed Postgres 1GB Basic + $5 Spaces 250GB)
- **Co-location**: **najbardziej dojrzała w shortliście** — Managed Postgres GA od lat, Spaces (S3-compatible + built-in CDN) GA, multiple EU regions (FRA + AMS)
- **Agent UX**: `llms.txt` + `llms-index.json`, każdy doc URL z `index.html.md` zwraca markdown, oficjalny DO MCP server obsługuje App Platform/Databases/Spaces (9 services)
- **CLI**: `doctl apps create-deployment`, full IaC via `.do/app.yaml`, rollback przez redeploy prior deployment
- **Dlaczego nie #1**: ~$10/mo droższy od Railway (DB single-node $15 vs Railway's compute-based ~$3-6 dla MVP traffic), pricing bardziej "tradycyjny enterprise" niż usage-based Railway

#### 3. Fly.io (Originally hinted in tech-stack.md, demoted)

- **Cost**: ~$45-50/mo total (shared-cpu-1x $3.19 + Managed Postgres Basic 1GB **$38** + Tigris ~$0.10 + egress ~$2)
- **Co-location**: Managed Postgres (GA status **nie zadeklarowany explicit w docs**, security patches "under development"), Tigris (GA, S3-compatible, $0.02/GB free egress)
- **Agent UX**: docs strukturalne ale brak llms.txt → HTML scraping; `fly mcp server --claude` istnieje ale **experimental**; flyctl dojrzały i agent-friendly
- **Region**: ams/fra/lhr (multiple EU — najmocniejszy w shortliście)
- **Dlaczego nie #1**: cost wyłom dla Q2 ("minimize cost"). Postgres Basic alone (~$38) to 2-3x cały budżet Railway. Dla wymagającego MVP z $50/mo budżetem nadal opłacalny, ale solo + after-hours profil pcha do tańszego wyboru.

**Honorable mention not in shortlist**: Render — 5/5 criteria ale Object Storage w alpha/waitlist zmusza do external S3 (Cloudflare R2 lub Backblaze B2), co bezpośrednio łamie Q5 co-location. Mógłby wrócić jeśli Object Storage przejdzie do GA w trakcie MVP (status do śledzenia).

**Out-of-pool alternatives worth mentioning**: Laravel Forge (managed VPS na DO/Hetzner/Linode — Laravel-native, $12/mo + VPS $5-10 = ~$20/mo, nie-PaaS ale uppgrade-friendly), Laravel Vapor (serverless on AWS Lambda, Laravel-specific — najlepszy dla heavy-scale ale overhead konfiguracji dla solo MVP wysokie), Hetzner + Coolify (self-hosted PaaS na własnym VPS od $4-5/mo — najtańsze ale więcej operacyjnego owładnięcia).

## Anti-Bias Cross-Check: Railway

### Devil's Advocate — Weaknesses

1. **EU = tylko Amsterdam** (`europe-west4-drams3a`). Dla polskich userów Frankfurt byłby lepszy ~5-10ms peering. Brak fallback EU region w obrębie Railway — single-AZ outage = pełen downtime.
2. **Usage-based pricing surprise risk** — Hobby $5/mo + usage. Zostawione `queue:listen` po debugu, zalany cron loop, lub idle Postgres compute zjadają cash bez alertu. Postgres compute floor nie jest opublikowany jako fixed SKU.
3. **Single-process-per-service** — Laravel chce app + queue worker + scheduler. Railway wymusza 3 osobne services każdy z duplikatem env vars. Solo dev fatigue + 3x billing.
4. **Storage Buckets GA-status niejasny** — produkt świeży, brak explicit GA label w docs (status traktowany jako "recently shipped, działa"). Jeśli pre-GA — możliwa zmiana API/pricing tier w trakcie MVP.
5. **Vendor lock-in przez Railpack auto-detection** — proprietary Nixpacks successor. Migracja na inną platformę wymaga ręcznego Dockerfile (mamy już — patrz `docker/app/Dockerfile`).

### Pre-Mortem — How This Could Fail

Sześć miesięcy później Podworko team uznał Railway za kosztowny błąd. Zaczęło się niewinnie: pozostawiony po debugu `queue:listen` worker silently zjadł ~$25/mo compute przez trzy tygodnie zanim ktoś zauważył w usage dashboard. W październiku 2026 Railway przesunął Storage Buckets z "beta" do nowego tieru pricing — koszty dla 50GB nagromadzonych zdjęć paragonów się podwoiły. Realny tarł pojawił się przy implementacji FR-022 (off-boarding z aktywnymi zobowiązaniami): długa migracja oczekiwała custom `release` hooka, ale Railpack auto-build go nie akceptował — migracja ruszyła wewnątrz app containera blokując requesty na 12 minut, wkurzeni sąsiedzi w Slacku. Gorzej: Amsterdam-only EU presence oznaczał że single-AZ outage w styczniu 2027 wyłączył app na 4 godziny; team nie miał fallback regionu w Railway i panikowo zmigrował na Fly.io przez dwa weekendy, bo polegali na Railpack defaults zamiast napisać portable container od początku.

### Unknown Unknowns

- **Railpack vs Nixpacks confusion** — Nixpacks był poprzednim auto-buildem, Railpack jest następcą. Docs mogą referencjonować Nixpacks miejscami; subtelne różnice w zachowaniu. Mitigation: Dockerfile-first (już mamy).
- **`queue:listen` vs `queue:work` footgun** — `queue:listen` restartuje przy zmianie kodu (dev-friendly) ale w "separate service" modelu Railway zostawiony żyje wiecznie i bilingowany. Konwencja: zawsze `queue:work` w prod, restart przez deploy.
- **Postgres point-in-time recovery / branching** — Railway managed Postgres ma snapshoty, ale "fork DB" / Neon-style branching nie jest oczywisty. Dla financial app z audit trail backup-restore drill należy zrobić wcześnie (nie pod presją incidentu).
- **Build cache cold-starts** — `composer install` cold-start ~30-60s. Invalidate przy każdym push do `composer.json`. Większy bottleneck przy iteracyjnym dev niż się wydaje.
- **EU latency optymalizacja** — Frankfurt > Amsterdam o ~5-10ms dla PL userów (peering). Marginalne, ale przy mobile-first NFR z PRD warto wiedzieć.

## Operational Story

- **Preview deploys**: Railway PR Environments (`railway environment` per branch); auto-deploy on PR open, teardown on merge. Postgres osobny per environment albo shared (decyzja per-projekt — dla MVP shared OK z migrations `migrate:fresh --seed` w preview).
- **Secrets**: Railway "Variables" — set przez `railway variables set KEY=value --service app` lub UI; per-environment scoping; reference variables (`${{Postgres.PGHOST}}`) auto-inject DB credentials między services. Rotation manual.
- **Rollback**: brak first-class `rollback`; działa `railway redeploy --deployment <prior-deployment-id>` (deployment IDs visible przez `railway deployments` lub UI). Typowy time-to-revert ~30s-2min. DB migrations NIE odwracane automatycznie — odrębny `php artisan migrate:rollback` przed redeployem starszej wersji jeśli schema się zmieniał.
- **Approval**: production deploy auto-on-merge-to-main domyślnie; manual approval opt-in przez `deploy.preDeployCommand` lub UI toggle. Destructive ops (drop DB, delete service) wymagają potwierdzenia przez dashboard lub `--yes` flag w CLI.
- **Logs**: `railway logs --tail --service app` (runtime), `railway logs --build` (build), agent-friendly stream. Plus MCP `get_logs` tool. Retention default 7 dni na Hobby, dłużej na Pro.

## Risk Register

| Risk | Source | Likelihood | Impact | Mitigation |
|------|--------|------------|--------|------------|
| Idle worker / queue:listen burning compute | Pre-mortem | M | H | Alert na usage spike >$20/mo w Railway dashboard; konwencja "queue:work zawsze w prod, queue:listen tylko dev"; dodać do `coding-rules.md` |
| Storage Buckets pre-GA pricing change | Devil's advocate | M | M | Trzymać Flysystem `disk` jako abstrakcję — łatwy swap na external (R2/B2/Spaces) jeśli Railway zmienia tier |
| Amsterdam-only EU outage | Devil's advocate + pre-mortem | L | H | Plan B na papierze: Fly.io z Frankfurt + DO App Platform z FRA jako fallback. Dockerfile portable (już mamy). Mitigation cost: zero do incidentu |
| Railpack auto-detection drift | Unknown unknowns | M | M | Dockerfile-first od day-zero (mamy `docker/app/Dockerfile`); ignorować Railpack defaults |
| Postgres backup restore drill nie wykonany | Unknown unknowns | M | H | W pierwszym tygodniu produkcji: wykonać snapshot + test restore na staging environment; udokumentować runbook w `context/foundation/runbooks/` (do utworzenia) |
| EU latency ~5-10ms gorzej niż Frankfurt | Unknown unknowns | L | L | Akceptowalne dla MVP; monitor jeśli userzy zgłaszają; trigger re-research jeśli ≥3 skargi |
| Single-process-per-service (3x billing dla app+queue+scheduler) | Devil's advocate | L | M | Nieistotne na razie — PRD `has_background_jobs: false` więc queue + scheduler nie są w MVP; gdy podejdziemy do FR-026 lub powiadomień (Open Question #2) → re-skoroyć cost |
| Railway services API change breaking IaC | General platform risk | L | M | Pin `railway` CLI version w devcontainer / CI; track Railway changelog raz/miesiąc |

## Getting Started

```bash
# 1. Konto + auth
railway login                                       # browser OAuth flow

# 2. Inicjalizacja projektu w bieżącym katalogu
railway init                                        # link to new or existing project
                                                    # wybierz "Empty Project" - dorzucimy services ręcznie

# 3. Dodanie usług
railway add                                         # interactive — wybierz "PostgreSQL" template
                                                    # (Railway provisione Managed Postgres service)

# 4. Pierwszy deploy z naszego Dockerfile
# UWAGA: Railway model = single-process-per-service. Mamy app + nginx + db w docker-compose.
# Dwie opcje:
#   (a) Bundle app + nginx w jednym Dockerfile przez supervisord (single service na Railway)
#   (b) Deploy app i nginx jako dwa osobne Railway services z internal networking
# Dla MVP rekomenduję (a) — zmodyfikować docker/app/Dockerfile żeby uruchamiał supervisord
# z php-fpm + nginx procesami. Mniej services = mniej duplikatów env + niższy bill.

railway up                                          # pierwszy deploy (z root Dockerfile)

# 5. Wstrzyknięcie credentialów Postgres przez Railway reference variables
railway variables set \
  DB_CONNECTION=pgsql \
  DB_HOST='${{Postgres.PGHOST}}' \
  DB_PORT='${{Postgres.PGPORT}}' \
  DB_DATABASE='${{Postgres.PGDATABASE}}' \
  DB_USERNAME='${{Postgres.PGUSER}}' \
  DB_PASSWORD='${{Postgres.PGPASSWORD}}' \
  APP_ENV=production \
  APP_DEBUG=false \
  --service app

# 6. Migracje schematu (jednorazowo po pierwszym deployu)
railway run --service app php artisan migrate --force

# 7. Custom domain albo auto-domena
railway domain                                      # generuje *.up.railway.app lub przypina custom
```

**Adaptacje wymagane w repo przed pierwszym deployem na Railway:**

1. **`docker/app/Dockerfile` rozszerzenie** — dodać `supervisord` + nginx w tym samym obrazie (jeśli wybierasz opcję (a) z kroku 4). Reference: https://docs.railway.com/guides/laravel
2. **`Procfile` lub `railway.json`** — opcjonalny IaC dla Railway (równoważnik docker-compose dla Railway-side configuration)
3. **`.env.production`** — Laravel `APP_ENV=production` + cache flags (`APP_KEY` z `php artisan key:generate --show` zalany do Railway variables, nie commitowany)
4. **Trwały `CACHE_STORE` i `SESSION_DRIVER`** — na Railway muszą wskazywać współdzielony store (`database` — domyślne w `.env.example` — lub `redis`), nigdy `array`/`file`. Magic-link RateLimiter (F-02) trzyma limity między workerami php-fpm tylko przez współdzielony cache; przy `array` throttle po cichu przestaje działać. To samo dotyczy trwałości sesji logowania.

## Out of Scope

Następujące NIE były ewaluowane w tym researchu:
- Docker image production refinement (multi-stage build, OPcache settings, brak dev-tools w final image)
- CI/CD pipeline setup (GitHub Actions per `tech-stack.md hints.ci_provider: github-actions` — osobny task)
- Production-scale architecture (multi-region HA, DR, dedicated egress IP)
- Cost optimization beyond $50/mo (load testing, instance right-sizing)
- Compliance / RODO requirements (PRD Open Question #1 — needs separate review with legal context)
