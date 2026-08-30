# Laravel Backend — Full Beginner Guide (Me Project)

Me project eke backend eka = **`Tech-Hub-Backend`** folder eke thiyena **Laravel 13** app ekak. Eka web page server ekak nemei — **API server** ekak (JSON data frontend ekata denna).

---

## 1. Big picture — System eka kohomada

```
[ Browser / React Frontend = Tech-Hub ]
              |
              | HTTP requests (JSON)
              v
[ Laravel Backend = Tech-Hub-Backend ]  ← php artisan serve (port 8000)
              |
              v
[ Database = SQLite file: database/database.sqlite ]
```

- **Frontend** (`npm run dev`) = UI
- **Backend** (`php artisan serve`) = business logic + DB
- Frontend eka backend eken products, login, AI chat data gannawa

---

## 2. Laravel eka mokadda?

PHP framework ekak. Folder structure + rules thiyena nisa:

- URL ekak enawa → **Route** eka match wenawa
- Route eka → **Controller** ekata yawanawa
- Controller → **Model** eken DB read/write karanawa
- Result eka → **JSON** response return karanawa

Me project eke controllers:

| Controller | Job |
|------------|-----|
| `AuthController` | register, login, vendors, profile |
| `ProductController` | products list/create/update |
| `CategoryController` | categories |
| `OrderController` | orders / checkout |
| `AiController` | AI health + chat recommendations |

---

## 3. Important folders (meka mathak karanna)

```
Tech-Hub-Backend/
├── app/
│   ├── Http/Controllers/   ← request handle karana code
│   ├── Models/             ← DB tables = PHP classes (User, Product…)
│   ├── Services/           ← business logic
│   └── Repositories/       ← DB access layer
├── bootstrap/app.php       ← app start + routes register
├── config/                 ← app, database, cors settings
├── database/
│   ├── migrations/         ← table structure (create users, products…)
│   ├── seeders/            ← sample data
│   └── database.sqlite     ← actual DB file (me project eke)
├── public/index.php        ← web entry point (every HTTP request)
├── routes/
│   ├── api.php             ← API URLs (main for this project)
│   └── web.php             ← browser pages (almost empty)
├── storage/                ← logs, cache
├── vendor/                 ← Composer packages (Laravel itself)
├── .env                    ← secrets + config (DB, APP_KEY)
├── artisan                 ← CLI tool (serve, migrate, seed…)
└── composer.json           ← PHP dependencies list
```

---

## 4. Request ekak kohomada travel karanne?

Example: Browser/frontend eka call karanawa  
`GET http://127.0.0.1:8000/api/products`

1. **`php artisan serve`** HTTP server eka request eka catch karanawa
2. Request eka **`public/index.php`** ekata yanawa
3. Laravel boot wenawa (`bootstrap/app.php` + `vendor/`)
4. **`routes/api.php`** eke match:  
   `Route::get('/products', [ProductController::class, 'index']);`
5. Laravel automatically prefix **`/api`** add karanawa → full path `/api/products`
6. `ProductController@index` run wenawa → DB eken products gannawa
7. JSON return → frontend eka display karanawa

Protected routes (login one) eka:

```php
Route::middleware('auth:sanctum')->group(function () {
    // token nathiwa meka call karoth 401
});
```

**Sanctum** = login token system. Login → token denna → next requests eke `Authorization: Bearer <token>` danna.

---

## 5. `.env` file eka — settings

Laravel eke config values code eke hardcode karanne nathiwa `.env` eken gannawa.

Me project eke important:

| Key | Meaning |
|-----|---------|
| `APP_KEY` | encryption key (`php artisan key:generate`) |
| `APP_URL` | app URL |
| `DB_CONNECTION=sqlite` | SQLite use karanawa (MySQL one na) |
| `DB_DATABASE` | (sqlite default = `database/database.sqlite`) |

`.env` git eke commit karanna epa (secrets). Template = `.env.example`.

---

## 6. Database — migrate + seed

### Migrations

Table structure define karana PHP files.

```powershell
php artisan migrate
```

Users, products, orders, tokens… tables create wenawa.

### Seeders

Sample data (products, categories, test users) danna:

```powershell
php artisan db:seed
```

**First time setup order:**

1. `composer install` → `vendor/` create
2. `.env` thiyenna one + `APP_KEY`
3. `database.sqlite` file thiyenna one
4. `migrate` → tables
5. `db:seed` → data
6. `php artisan serve` → server

---

## 7. Backend eka run karana widiya (daily)

Aluth terminal ekak:

```powershell
cd "c:\Users\mihisara\Desktop\DISPUTE-AI\Tech-Hub-Backend"
php artisan serve
```

Output wage:

```
Server running on [http://127.0.0.1:8000]
```

| URL | Meaning |
|-----|---------|
| http://127.0.0.1:8000 | Laravel root |
| http://127.0.0.1:8000/api/ai/health | AI health check |
| http://127.0.0.1:8000/api/products | Products list |
| http://127.0.0.1:8000/api/categories | Categories |
| http://127.0.0.1:8000/up | Laravel health |

**Stop karanna:** terminal eke `Ctrl + C`

**PHP note:** PATH eke `C:\php84` set karala thiyena nisa `$env:Path = ...` gahanna one na. `php -v` → 8.4 pennanna one.

---

## 8. `php artisan serve` vs XAMPP Apache

| | `php artisan serve` | XAMPP Apache |
|--|---------------------|--------------|
| Use | Local Laravel API | phpMyAdmin / old PHP sites |
| Port | 8000 | usually 80 |
| Me project | ✅ Meeka use karanna | Optional (MySQL onnam) |

Me project eke DB = **SQLite** → XAMPP MySQL **must** na.

---

## 9. Composer — ai one?

`composer install` = Laravel + packages `vendor/` ekata download.

- First clone / new PC → `composer install` once
- Daily run → `php artisan serve` withara enough
- `composer update` → versions change (careful; team ekathuwa ahanne)

---

## 10. Frontend ekathuwa connect

`Tech-Hub\.env`:

```
VITE_AI_SERVICE_URL=http://localhost:8000
VITE_CATALOG_SERVICE_URL=http://localhost:8001
VITE_COMMERCE_SERVICE_URL=http://localhost:8002
```

Frontend eka **3 ports** expect karanawa (AI / catalog / commerce).  
Backend eka **ekama Laravel server** — mainly **port 8000**, routes `/api/...`.

**Production split (Dispute AI vs Mia):**

| Concern | Env | Target |
|---------|-----|--------|
| Catalog / commerce / Sanctum | `VITE_CATALOG_*` / `VITE_COMMERCE_*` | Laravel `:8000/api` |
| Mia recommend chat | `VITE_RECOMMEND_AI_URL` | Laravel `/api/ai/chat` stub |
| Dispute AI chat + health | `VITE_AI_SERVICE_URL` (`/api/ai` proxy) | FastAPI AI-Agent (local `:8080`) |
| Policy sync webhook | `AI_POLICY_SYNC_WEBHOOK_URL` | `http://127.0.0.1:8080/webhooks/policy-sync` |

Local Dispute AI:

```powershell
cd AI-Agent
# from package README — typically uvicorn on 8080 when Laravel owns 8000
```

Do **not** point `VITE_AI_SERVICE_URL` at Laravel if you need real dispute RAG — that is only for Mia via `VITE_RECOMMEND_AI_URL`.

See section **14. Tech-Hub ↔ AI-Agent integration** below.

Full local checklist: [docs/TECHHUB_AI_INTEGRATION_RUNBOOK.md](../docs/TECHHUB_AI_INTEGRATION_RUNBOOK.md)

Local test ekata catalog/commerce often okkoma `http://localhost:8000/api` point karanna.

Frontend run:

```powershell
cd "c:\Users\mihisara\Desktop\DISPUTE-AI\Tech-Hub"
npm run dev
```

**3 terminals** for full stack:

1. Backend → `php artisan serve` (:8000)
2. AI-Agent FastAPI → `:8080`
3. Frontend → `npm run dev`

---

## 11. Useful artisan commands (cheat sheet)

```powershell
php artisan serve              # start server
php artisan migrate            # run new migrations
php artisan db:seed            # seed data
php artisan migrate:fresh --seed  # wipe DB + remake + seed (DATA DELETE)
php artisan route:list         # okkoma URLs list
php artisan key:generate       # APP_KEY
php artisan config:clear       # .env change eken pasu
php artisan cache:clear
```

---

## 12. Error ekak awoth check karanna order

1. `php -v` → 8.4 da?
2. `vendor/` folder thiyeda? nathnam `composer install`
3. `.env` + `APP_KEY` thiyeda?
4. Server running da? (`php artisan serve`)
5. URL eke `/api/...` thiyeda?
6. Log: `storage/logs/laravel.log`

---

## 13. Mental model (1 sentence)

**Route → Controller → Model/DB → JSON response.**  
`php artisan serve` eka me cycle eka port 8000 eke listen karanawa.

---

## 14. Tech-Hub ↔ AI-Agent integration

Shared **Supabase Postgres** (optional locally; required for production dispute features).

| Owner | Tables |
|-------|--------|
| Laravel | `users`, `products`, `orders`, `order_items`, `customers`, `disputes`, `vendor_policies`, `platform_policies`, … |
| Laravel migrations (AI memory) | `st_turns`, `mem_facts`, `mem_episodes`, `mem_procedures`, `chat_sessions` (+ `vector` extension) |
| Not used | Standalone AI `vendors` table — vendors are `users.role=vendor` + `users.ai_uuid` |

**Identity**

- Customer / chat `user_id` = `users.ai_uuid` (= `customers.id`)
- Vendor policy `vendor_id` = vendor’s `users.ai_uuid`
- Canonical order id for AI tools = `orders.order_number` (fallback `ai_order_id`)

**Webhook**

- Laravel → `POST {AI_POLICY_SYNC_WEBHOOK_URL}` with Supabase-style payload (`type`, `table`, `record`)
- Tables: `vendor_policies` (approved only for upsert), `platform_policies`
- Unapprove / delete emits removal so Neo4j + Qdrant stay in sync

**Auth**

- Catalog/commerce: Sanctum Bearer token
- Dispute chat: Laravel mints short-lived HMAC token (`AI_CHAT_TOKEN_SECRET`); FastAPI validates `X-AI-Chat-Token`

**Do not** run `migrate:fresh` against shared Supabase without explicit approval.

---

## Daily workflow (quick)

```powershell
cd Tech-Hub-Backend
php artisan serve
```

+ another terminal:

```powershell
cd Tech-Hub
npm run dev
```

---

## PHP setup notes (Windows)

- Project needs **PHP 8.4+** (lock file / Symfony packages)
- Recommended install path: `C:\php84`
- Set `extension_dir = "C:\php84\ext"` in `php.ini`
- Enable: `curl`, `fileinfo`, `mbstring`, `openssl`, `pdo_sqlite`, `sqlite3`
- PATH: `C:\php84` first; remove or demote `C:\xampp\php` so CLI uses 8.4
- Composer on the PC is fine — the blocker was PHP version, not Composer version
