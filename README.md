# Expense Tracker API

A RESTful API for team-based expense management with multi-step approval workflows, receipt uploads, commenting, and role-scoped reporting.

![PHP 8.3](https://img.shields.io/badge/PHP-8.3-blue) ![Laravel 13](https://img.shields.io/badge/Laravel-13-red) ![Tests](https://img.shields.io/badge/Tests-50%2B-green) ![License](https://img.shields.io/badge/license-MIT-lightgrey)

---

## Technical Overview

| Layer | Technology |
|-------|-----------|
| Framework | Laravel 13 |
| Language | PHP 8.3 |
| Database | MySQL 8.0 |
| Authentication | Laravel Sanctum v4 (token-based) |
| File storage | Laravel Storage (local / S3-compatible) |
| Local dev | Docker Compose |
| Deployment | Render |

**Role system:** Three tiers — Admin, Manager, Member — with different access scopes at every layer.

---

## Why This Architecture?

Controllers validate and delegate. Business logic lives in services.

```
Request → FormRequest (validates) → Controller (delegates) → Service (logic) → Model / Policy
```

**Services and what they own:**

| Service | Responsibility |
|---------|---------------|
| `AuthService` | Register, login, logout, inactive-account blocking |
| `ExpenseService` | Full expense lifecycle — create, update, delete, submit, approve, reject, list |
| `ReceiptService` | File upload to storage, metadata persistence, deletion |
| `ReportService` | Aggregated queries — team summary, by category, by member |

**Why polymorphic comments?** Comments use `MorphMany` on `Expense`. This means the comment model can attach to any future entity (approvals, receipts, etc.) without schema changes — just add the interface.

---

## TDD Approach

**50+ automated tests** across 16 feature test files.

| Suite | What it covers |
|-------|---------------|
| Auth | Register (with team_id), login, logout, inactive account blocking |
| Categories | Create (admin only), list (active filtering by role) |
| Expense CRUD | Create, read, update, delete — including role-based permission failures |
| Expense Filtering | Filter by status, category, date range, amount range |
| Workflow | Submit, approve, reject — valid and invalid transitions |
| Receipts | Upload (10MB, jpg/png/pdf), list, download, delete |
| Comments | Create, list, delete — authorization checks |
| Reports | Team summary, by-category, by-member — manager/admin only |

**Testing strategy:**

- `RefreshDatabase` on every test — SQLite in-memory, clean slate each run
- Factories for all models: `User`, `Team`, `Expense`, `Category`, `Receipt`, `Comment`
- Workflow tests verify state transitions (`draft → pending → approved`)
- Role tests verify that members can't approve, managers can't see other teams' data, etc.
- `actingAs($user)` for authenticated requests; `getJson()` / `postJson()` for HTTP assertions

Run all tests:

```bash
make test
```

---

## Security Checklist

- [x] **Token authentication** — Laravel Sanctum; token required on all protected routes
- [x] **Inactive account blocking** — `AuthService::login()` throws 401 if `is_active = false`
- [x] **Role-based policies** — `ExpensePolicy`, `ReceiptPolicy`, `CommentPolicy` — every action has an explicit policy method
- [x] **Gate for admin routes** — `Gate::authorize('admin')` in `AdminController` before every action
- [x] **Scoped queries** — members see own expenses; managers see team expenses; admins see all
- [x] **Mass-assignment protection** — explicit `$fillable` on all models
- [x] **Password hashing** — Laravel's `Hashed` cast; plain-text never stored
- [x] **File upload validation** — `mimes:jpg,jpeg,png,pdf`, max 10MB per receipt
- [x] **Soft deletes on expenses** — records preserved for audit trail; not permanently deleted

---

## Performance Wins

**Cursor pagination** on expense listings — consistent cost regardless of how far into the list you page.

**`approved()` scope** on all report queries — filters at the database level, not in PHP.

**`baseQuery()` reuse** in `ReportService` — one method builds the base query (with role scoping and date range filter) shared across all three report endpoints.

**Role-scoped queries at query build time** — managers and members get a WHERE clause added before any other filters; the database doesn't return rows it shouldn't.

---

## Expense Workflow

```
[Draft] ──submit()──▶ [Pending] ──approve()──▶ [Approved]
                           │
                        reject()
                           │
                           ▼
                       [Rejected] ──submit()──▶ [Pending]
```

- Only the **owner** can submit or resubmit
- Only **Admin** or **Manager (same team)** can approve or reject
- Only **Draft** and **Rejected** expenses can be edited or deleted
- A rejection reason is required and stored on the expense record

---

## Endpoints

### Auth

| Method | URL | Auth | Description |
|--------|-----|------|-------------|
| POST | `/api/register` | No | Register with `team_id` |
| POST | `/api/login` | No | Login, receive Bearer token |
| POST | `/api/logout` | Yes | Revoke all tokens |

### Categories

| Method | URL | Roles | Description |
|--------|-----|-------|-------------|
| GET | `/api/categories` | All | List categories (non-admins see active only) |
| POST | `/api/categories` | Admin | Create a category |

### Expenses

| Method | URL | Roles | Description |
|--------|-----|-------|-------------|
| GET | `/api/expenses` | All | List expenses (role-scoped) |
| POST | `/api/expenses` | All | Create a draft expense |
| GET | `/api/expenses/{id}` | Auth | Get a single expense |
| PUT | `/api/expenses/{id}` | Owner | Update a draft or rejected expense |
| DELETE | `/api/expenses/{id}` | Owner | Delete a draft expense |

### Workflow

| Method | URL | Roles | Description |
|--------|-----|-------|-------------|
| POST | `/api/expenses/{id}/submit` | Owner | Submit for review |
| POST | `/api/expenses/{id}/approve` | Admin, Manager | Approve a pending expense |
| POST | `/api/expenses/{id}/reject` | Admin, Manager | Reject with a reason |

### Receipts

| Method | URL | Roles | Description |
|--------|-----|-------|-------------|
| POST | `/api/expenses/{id}/receipts` | Owner | Upload receipt (jpg/png/pdf, max 10MB) |
| GET | `/api/expenses/{id}/receipts` | Auth | List receipts for an expense |
| GET | `/api/receipts/{id}/download` | Auth | Download a receipt file |
| DELETE | `/api/receipts/{id}` | Owner | Delete a receipt |

### Comments

| Method | URL | Roles | Description |
|--------|-----|-------|-------------|
| POST | `/api/expenses/{id}/comments` | Auth | Add a comment to an expense |
| GET | `/api/expenses/{id}/comments` | Auth | List comments (newest first) |
| DELETE | `/api/comments/{id}` | Author | Delete a comment |

### Reports

| Method | URL | Roles | Description |
|--------|-----|-------|-------------|
| GET | `/api/reports/team-summary` | Admin, Manager | Total amount, count, and average |
| GET | `/api/reports/by-category` | Admin, Manager | Breakdown by category |
| GET | `/api/reports/by-member` | Admin, Manager | Breakdown by team member |

### Admin

| Method | URL | Roles | Description |
|--------|-----|-------|-------------|
| PATCH | `/api/admin/users/{user}/toggle-active` | Admin | Activate or deactivate a user |
| PATCH | `/api/admin/teams/{team}/toggle-active` | Admin | Activate or deactivate a team |
| PATCH | `/api/admin/categories/{category}/toggle-active` | Admin | Activate or deactivate a category |

### Query Parameters — `GET /api/expenses`

| Parameter | Type | Description |
|-----------|------|-------------|
| `status` | string | `draft`, `pending`, `approved`, `rejected` |
| `category_id` | integer | Filter by category |
| `date_from` | date | Expenses on or after this date |
| `date_to` | date | Expenses on or before this date |
| `amount_min` | numeric | Minimum amount |
| `amount_max` | numeric | Maximum amount |
| `sort` | string | Sort field; prefix `-` for descending (e.g. `-amount`) |
| `per_page` | integer | Max 50, default 15 |
| `cursor` | string | Cursor token from previous response |

### Query Parameters — Reports

| Parameter | Type | Description |
|-----------|------|-------------|
| `date_from` | date | Include approved expenses on or after this date |
| `date_to` | date | Include approved expenses on or before this date |

---

## Roles & Authorization

| Role | What they can do |
|------|-----------------|
| `member` | Create/manage their own expenses; view own receipts and comments |
| `manager` | All member actions + approve/reject expenses from their team; access team reports |
| `admin` | Full access to all expenses, all teams, reports, and admin controls |

---

## Setup Instructions

### Prerequisites

Docker and Docker Compose

### Installation

```bash
# Clone the repo
git clone <repo-url> expense-tracker-api
cd expense-tracker-api

# Copy environment config
cp .env.example .env
# Edit .env and set DB_DATABASE, DB_USERNAME, DB_PASSWORD

# Build and start containers
make build
make up

# Run database migrations
make migrate

# Create test users via Tinker
winpty make shell
php artisan db:seed
exit
```

### Running Tests

```bash
make test
```

### Make Commands

| Command | Description |
|---------|-------------|
| `make build` | Build Docker images |
| `make up` | Start containers |
| `make down` | Stop containers |
| `make migrate` | Run database migrations |
| `make fresh` | Drop all tables, re-migrate, and reseed |
| `make test` | Run the full test suite |
| `make shell` | Open a bash shell in the app container |

The API runs at `http://localhost:8090/api`.

---

## Documentation

API documentation is generated from `postman_collection.json` and rendered via [Redoc](https://github.com/Redocly/redoc).

**Generate the OpenAPI spec locally:**

```bash
npm run docs
```

This reads `postman_collection.json` and outputs `openapi.yaml` at the project root.

**View the docs locally:**

Open `docs/index.html` in a browser (or use VS Code Live Server). Redoc fetches `../openapi.yaml` and renders the full interactive spec.

**Live docs (GitHub Pages):**

1. Push to `main` — GitHub Actions runs automatically, generates `openapi.yaml`, and commits it back
2. Go to repository Settings → Pages → Source: Deploy from branch → `main` → `/` (root)
3. Docs are live at: `https://<username>.github.io/<repo>/docs/`

---

## License

MIT — see [LICENSE](LICENSE)
