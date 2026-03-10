# Land Manager — Gestión Inmobiliaria SaaS

> **Source of Truth** — This document serves as the definitive architectural reference for the Land Manager platform. All future development, whether by human engineers or AI agents, must align with the conventions, patterns, and business rules described herein.

---

## Table of Contents

- [1. Project Overview](#1-project-overview)
- [2. Tech Stack](#2-tech-stack)
- [3. Core Business Logic](#3-core-business-logic)
- [4. Database Schema](#4-database-schema)
- [5. Role-Based Access Control (RBAC)](#5-role-based-access-control-rbac)
- [6. Project Structure](#6-project-structure)
- [7. UI/UX Standards](#7-uiux-standards)
- [8. API & Route Map](#8-api--route-map)
- [9. Development Setup](#9-development-setup)
- [10. Conventions & Guidelines](#10-conventions--guidelines)

---

## 1. Project Overview

**Land Manager** is a multi-tenant SaaS platform designed for Colombian construction companies and real estate agencies specializing in **land lot sales** (*loteos*). The system manages the full lifecycle of a lot — from initial project setup through reservation, financing, and final sale.

The inaugural deployment target is **Proyecto La Cumbre** (Flandes, Tolima, Colombia), a 7-hectare rural land subdivision project with 210+ lots organized across multiple *manzanas* (blocks).

### Key Capabilities

| Module | Description |
|---|---|
| **Projects** | Create and manage real estate developments, each with blocks (Manzanas) and lots |
| **Inventory** | Visual lot grid with real-time status tracking (Available → Pending → Reserved → Sold) |
| **Clients** | Customer directory with document management (ID copies, contracts, receipts) |
| **Reservations** | Full reservation workflow with payment proof upload and admin approval |
| **Finances** | Linear amortization engine, payment tracking, PDF receipt generation |
| **Settings** | Tenant-level configuration (corporate identity, billing info) |

---

## 2. Tech Stack

### Backend

| Technology | Version | Purpose |
|---|---|---|
| **PHP** | ^8.2 | Server-side runtime |
| **Laravel** | ^12.0 (Laravel 12) | MVC framework, routing, ORM, auth |
| **Inertia.js** | ^2.0 (server) | Server-driven SPA bridge |
| **PostgreSQL** | Latest | Primary relational database |
| **Laravel Sanctum** | ^4.0 | API token / session authentication |
| **DomPDF** (barryvdh) | ^3.1 | PDF generation for payment receipts |
| **Ziggy** (tightenco) | ^2.0 | Laravel route sharing with JavaScript |

### Frontend

| Technology | Version | Purpose |
|---|---|---|
| **Vue.js** | ^3.4 | Reactive UI framework |
| **Inertia.js** | ^2.0 (client) | SPA navigation without an API layer |
| **Tailwind CSS** | ^3.2 | Utility-first styling framework |
| **Vite** | ^7.0 | Frontend build tool & dev server |
| **oh-vue-icons** | ^1.0.0-rc3 | Icon library (Material Design + Remix Icons) |

### Infrastructure

| Concern | Solution |
|---|---|
| **Multi-tenancy** | Row-level isolation via `tenant_id` foreign key on `projects`, `clients`, and `users` |
| **File Storage** | Laravel `public` disk (`storage/app/public/`) for payment proofs, documents, and project maps |
| **Locale** | Spanish (`es`), with `es_CO` Faker locale |

---

## 3. Core Business Logic

### 3.1 Multi-Tenant / Multi-Project Architecture

The platform supports **multiple construction companies** (tenants), each managing **multiple real estate projects**. Data isolation is enforced at the controller level by comparing `tenant_id` on every read/write operation.

```
Tenant (Company)
├── Users (admin, accountant, sales_agent)
├── Clients  
└── Projects
    └── Blocks (Manzanas)
        └── Lots
            └── Reservations
                └── PaymentPlan
                    └── Payments
```

### 3.2 Lot Lifecycle — State Machine

Lots follow a strict state machine. Transitions are enforced in `ReservationController` and `FinanceController`.

```
┌─────────────┐     Sales Agent     ┌────────────────────┐
│  AVAILABLE   │ ──── reserves ────▶ │  PENDING APPROVAL  │
│  (available) │                     │ (pending_approval)  │
└─────────────┘                     └────────────────────┘
       ▲                                     │
       │                           Admin/Accountant
       │                             approves │
       │                                     ▼
       │ cancel                      ┌──────────┐
       │◀────────────────────────────│ RESERVED  │
       │                             │(reserved) │
       │                             └──────────┘
       │                                     │
       │                        First payment │
       │                          recorded    │
       │                                     ▼
       │                              ┌──────┐
       └──────────────────────────────│ SOLD  │
              (only by cancel)        │(sold) │
                                      └──────┘
```

**State Definitions:**

| Status | DB Value | Trigger | Color (UI) |
|---|---|---|---|
| Available | `available` | Default / Released by Admin | White/Neutral |
| Pending Approval | `pending_approval` | Sales Agent creates a reservation | Amber/Orange |
| Reserved | `reserved` | Admin/Accountant approves the reservation | Blue |
| Sold | `sold` | First payment recorded OR manual confirmation | Red |

**Key Rules:**

- When an **Admin or Accountant** creates a reservation, the lot skips `pending_approval` and goes directly to `reserved`.
- Only **Admins and Accountants** can approve, confirm, or cancel reservations.
- Payment proof (image/PDF) is **mandatory** when creating a reservation.

### 3.3 Financial Rules

| Rule | Value | Notes |
|---|---|---|
| **Interest Rate** | **0%** | Hard-coded in `AmortizationService`. No interest is charged. |
| **Late Fees** | **0%** | No penalties for late payments in current implementation. |
| **Amortization Type** | **Linear** | `financed_amount / total_installments` with rounding remainder on last installment |
| **Down Payment (Enganche)** | $500,000 COP minimum | Non-refundable reservation fee |
| **Installment Frequency** | Monthly | Each installment due date = `start_date + N months` |
| **Max Installments** | 120 (10 years) | Validated in `ReservationController::store` |

### 3.4 Reservation Policy

1. The initial **$500,000 COP reservation fee** (enganche) is **non-refundable**.
2. A **payment proof** file (PDF/image) must be uploaded at the time of reservation.
3. Reservations created by a Sales Agent require explicit **approval** from an Admin or Accountant.

### 3.5 Release & Arrears Policy

- **No automatic release**. If the buyer fails to make the first payment within 20–30 days, the lot remains in its current state.
- An **Administrator must manually release** the lot (cancel the reservation), returning it to `available`.
- There is no automated cron job or scheduler for expiration — this is a deliberate design decision to give management full control over lot status.

### 3.6 Payment Lifecycle

When a payment is recorded in `FinanceController::recordPayment`:

1. If this is the **first payment** on an `active` reservation, the reservation transitions to `confirmed` and the lot becomes `sold`.
2. If **all installments** are paid, the `PaymentPlan` status becomes `completed`.
3. A downloadable **PDF receipt** is generated via DomPDF (`pdf.receipt` Blade view).

---

## 4. Database Schema

### Entity Relationship Overview

```
tenants ─┬── users (tenant_id, role)
         ├── clients (tenant_id, user_id)
         └── projects (tenant_id, map_file)
              └── blocks (project_id)
                   └── lots (block_id, status)
                        └── reservations (lot_id, client_id, user_id, payment_proof)
                             └── payment_plans (reservation_id)
                                  └── payments (payment_plan_id, received_by)

clients ──── documents (client_id, uploaded_by)
```

### Migrations (in order)

| Migration | Table | Purpose |
|---|---|---|
| `000000_create_users_table` | `users` | Authentication, `tenant_id`, `role` |
| `000001_create_tenants_table` | `tenants` | Company/organization entities |
| `000002_create_projects_table` | `projects` | Real estate developments |
| `000003_create_blocks_table` | `blocks` | Manzanas within a project |
| `000004_create_lots_table` | `lots` | Individual lots with pricing and status |
| `000005_create_clients_table` | `clients` | Buyer records |
| `000006_create_reservations_table` | `reservations` | Lot reservations with proof |
| `000007_create_payment_plans_table` | `payment_plans` | Amortization plan metadata |
| `000008_create_payments_table` | `payments` | Individual installment records |
| `000009_create_documents_table` | `documents` | Client file attachments |
| `update_lots_and_reservations_*` | `lots`, `reservations` | Added `pending_approval` status and `payment_proof` |
| `add_user_id_to_clients_table` | `clients` | Client ownership by sales agent |
| `add_map_file_to_projects_table` | `projects` | Project plan/map file (PDF/image) |

### Key Model Relationships

| Model | Key Relationships |
|---|---|
| `Tenant` | hasMany `Users`, `Projects`, `Clients` |
| `User` | belongsTo `Tenant` |
| `Project` | belongsTo `Tenant`, hasMany `Blocks`, hasManyThrough `Lots` |
| `Block` | belongsTo `Project`, hasMany `Lots` |
| `Lot` | belongsTo `Block`, hasMany `Reservations`, hasOne `activeReservation` |
| `Client` | belongsTo `Tenant`, belongsTo `User`, hasMany `Reservations`, `Documents` |
| `Reservation` | belongsTo `Lot`, `Client`, `User`, hasOne `PaymentPlan` |
| `PaymentPlan` | belongsTo `Reservation`, hasMany `Payments` |
| `Payment` | belongsTo `PaymentPlan`, belongsTo `User` (receiver) |

---

## 5. Role-Based Access Control (RBAC)

The system implements a simple but strict role-based permission model. Roles are stored as a `string` column on `users`.

### Role Definitions

| Role | DB Value | Scope |
|---|---|---|
| **Administrator** | `admin` | Full access to all modules and actions |
| **Accountant** | `accountant` | Same effective permissions as Admin (finances, approvals, project management) |
| **Sales Agent** | `sales_agent` | Restricted to their own clients, can create reservations but cannot approve/confirm/cancel |

### Permission Matrix

| Action | Admin | Accountant | Sales Agent |
|---|---|---|---|
| View Dashboard | ✅ | ✅ | ✅ |
| View Projects | ✅ | ✅ | ✅ |
| Create/Edit/Delete Projects | ✅ | ✅ | ❌ |
| Upload Project Map | ✅ | ✅ | ❌ |
| View All Lots | ✅ | ✅ | ✅ |
| Reserve a Lot (→ `reserved`) | ✅ | ✅ | ❌ (→ `pending_approval`) |
| Approve Reservation | ✅ | ✅ | ❌ |
| Confirm/Release Reservation | ✅ | ✅ | ❌ |
| View All Clients | ✅ | ✅ | ❌ (only own) |
| Create Clients | ✅ | ✅ | ✅ (auto-assigned `user_id`) |
| Access Finances Module | ✅ | ✅ | ❌ (hidden in sidebar) |
| Record Payments | ✅ | ✅ | ❌ |
| Access Settings | ✅ | ✅ | ❌ |

**Enforcement Layers:**

1. **Backend**: Controller methods call `authorizeAdminAction()` or check `request()->user()->role` via `in_array()`.
2. **Frontend**: Buttons/actions are conditionally rendered using `isAdmin` computed properties (`usePage().props.auth.user.role`).
3. **Sidebar**: The `AppLayout.vue` filters navigation items marked with `adminOnly: true`.

---

## 6. Project Structure

```
land-manager-saas/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── DashboardController.php      # Summary stats, recent activity
│   │   │   ├── ProjectController.php        # CRUD projects + map upload
│   │   │   ├── LotController.php            # Lot details + client list for reservation
│   │   │   ├── ClientController.php         # Client CRUD + document management
│   │   │   ├── ReservationController.php    # Reserve, approve, confirm, cancel
│   │   │   ├── FinanceController.php        # Plans, payment recording, PDF receipts
│   │   │   └── ProfileController.php        # User profile management
│   │   └── Middleware/
│   │       └── HandleInertiaRequests.php     # Shares auth.user + flash messages
│   ├── Models/
│   │   ├── Tenant.php       # Company entity
│   │   ├── User.php         # Auth user with role helpers
│   │   ├── Project.php      # Development with computed lot counts
│   │   ├── Block.php        # Manzana with computed lot counts
│   │   ├── Lot.php          # State machine (status, color, label)
│   │   ├── Client.php       # Buyer with full_name accessor
│   │   ├── Reservation.php  # Links lot ↔ client with status_label
│   │   ├── PaymentPlan.php  # Amortization metadata with progress
│   │   ├── Payment.php      # Individual installment
│   │   └── Document.php     # Client file attachment
│   └── Services/
│       └── AmortizationService.php  # 0% linear amortization engine
├── resources/
│   └── js/
│       ├── app.js               # Vue app bootstrap + icon registry
│       ├── Layouts/
│       │   ├── AppLayout.vue    # Main app shell (sidebar, nav, toast)
│       │   ├── AuthenticatedLayout.vue
│       │   └── GuestLayout.vue
│       ├── Components/
│       │   ├── Toast.vue         # Custom notification component
│       │   ├── ConfirmModal.vue  # Confirmation dialog component
│       │   └── Modal.vue         # Reusable modal wrapper
│       └── Pages/
│           ├── Dashboard.vue           # KPI cards + recent activity
│           ├── Projects/
│           │   ├── Index.vue           # Project list + creation modal
│           │   └── Show.vue            # Lot grid + map viewer + edit modal
│           ├── Lots/
│           │   └── Show.vue            # Lot detail + reservation form
│           ├── Clients/
│           │   ├── Index.vue           # Client directory with search
│           │   ├── Show.vue            # Client detail + documents
│           │   ├── Create.vue          # Full-page form (legacy)
│           │   └── Partials/
│           │       └── CreateClientModal.vue  # Modal-based client creation
│           ├── Finances/
│           │   ├── Index.vue           # Payment plans overview
│           │   └── PaymentPlan.vue     # Amortization table + payment recording
│           └── Settings/
│               └── Index.vue           # Tenant configuration
├── routes/
│   ├── web.php       # All application routes (auth-protected)
│   └── auth.php      # Laravel Breeze auth routes
└── database/
    └── migrations/   # 15 migration files (schema documented above)
```

---

## 7. UI/UX Standards

### Dark Mode Aesthetic

The entire application uses a **strict dark mode** palette inspired by premium fintech interfaces. There is **no light mode**.

| Token | Hex Value | Usage |
|---|---|---|
| `bg-base` | `#121212` | Page background |
| `bg-surface` | `#18181a` | Cards, sidebar, elevated surfaces |
| `bg-elevated` | `#1e1e1e` | Buttons, badges, inner cards |
| `bg-input` | `#141414` | Form field backgrounds |
| `border-default` | `#2a2a2a` | Standard border/divider |
| `border-hover` | `#3f3f46` | Hover state borders |
| `text-primary` | `#ffffff` / `#ededed` | Primary text and headings |
| `text-secondary` | `#a1a1aa` | Supporting text |
| `text-muted` | `#71717a` | Labels, placeholders, hints |

### Status Color Coding

| Context | Color | Tailwind Class | Example |
|---|---|---|---|
| Available lots | White/Neutral | `text-white` | Default lot card |
| Pending Approval | Amber/Orange | `text-amber-500`, `bg-amber-500/10` | Pending lot card |
| Reserved | Blue | `text-blue-500`, `bg-blue-500/10` | Reserved lot card |
| Sold | Red | `text-red-500`, `bg-red-500/10` | Sold lot card |
| Payment Overdue | Red tones | `text-rose-*` / `text-red-*` | Finance module indicators |
| Success Actions | White on black | `btn-primary` | Primary action buttons |

### Typography

- **Labels**: `text-[10px]` or `text-[9px]`, `uppercase`, `tracking-wider`, `font-semibold`
- **Values**: `text-sm` or `text-xs`, `font-bold`, `text-white`
- **Cards**: `rounded-2xl`, `border border-[#2a2a2a]`, `p-8`

### Component Patterns

1. **Modals** (`Modal.vue`): Used for client creation, project editing, and confirmations. Background uses `bg-black/80` with `backdrop-blur`.
2. **Toast Notifications** (`Toast.vue`): Custom notification system replacing native `alert()`. Supports `success`, `error`, and `info` types.
3. **Confirm Dialogs** (`ConfirmModal.vue`): Used for destructive or critical actions (approve, cancel, release). Replaces `window.confirm()`.
4. **Icons**: All icons come from `oh-vue-icons` (Material Design and Remix Icon sets). Icons must be imported in `app.js` and registered via `addIcons()`.

---

## 8. API & Route Map

All routes are defined in `routes/web.php` and protected by `auth` + `verified` middleware.

| Method | URI | Controller@Action | Purpose |
|---|---|---|---|
| `GET` | `/dashboard` | `DashboardController@index` | Main dashboard |
| `GET` | `/projects` | `ProjectController@index` | List all projects |
| `POST` | `/projects` | `ProjectController@store` | Create project (Admin) |
| `GET` | `/projects/{project}` | `ProjectController@show` | Project detail + lot grid |
| `PUT` | `/projects/{project}` | `ProjectController@update` | Update project (Admin) |
| `DELETE` | `/projects/{project}` | `ProjectController@destroy` | Delete project (Admin) |
| `GET` | `/lots/{lot}` | `LotController@show` | Lot detail + reservation form |
| `PUT` | `/lots/{lot}` | `LotController@update` | Update lot info |
| `GET` | `/clients` | `ClientController@index` | Client directory |
| `POST` | `/clients` | `ClientController@store` | Register a client |
| `GET` | `/clients/{client}` | `ClientController@show` | Client detail + assets |
| `PUT` | `/clients/{client}` | `ClientController@update` | Update client info |
| `POST` | `/clients/{client}/documents` | `ClientController@uploadDocument` | Upload client document |
| `DELETE` | `/clients/{client}/documents/{doc}` | `ClientController@deleteDocument` | Delete client document |
| `POST` | `/reservations` | `ReservationController@store` | Create reservation |
| `POST` | `/reservations/{reservation}/approve` | `ReservationController@approve` | Approve pending (Admin) |
| `POST` | `/reservations/{reservation}/confirm` | `ReservationController@confirm` | Confirm sale (Admin) |
| `POST` | `/reservations/{reservation}/cancel` | `ReservationController@cancel` | Cancel/Release (Admin) |
| `GET` | `/finances` | `FinanceController@index` | Finance overview |
| `GET` | `/finances/plans/{plan}` | `FinanceController@showPlan` | Amortization detail |
| `POST` | `/finances/payments/{payment}/record` | `FinanceController@recordPayment` | Record a payment |
| `GET` | `/finances/payments/{payment}/receipt` | `FinanceController@generateReceipt` | Download PDF receipt |
| `GET` | `/settings` | Closure (Inertia) | Tenant settings page |

---

## 9. Development Setup

### Prerequisites

- **PHP** >= 8.2 with extensions: `pgsql`, `mbstring`, `openssl`, `fileinfo`, `gd`
- **Composer** >= 2.x
- **Node.js** >= 18.x with npm
- **PostgreSQL** >= 14

### Installation

```bash
# 1. Clone the repository
git clone <repository-url> land-manager-saas
cd land-manager-saas

# 2. Install PHP dependencies
composer install

# 3. Configure environment
cp .env.example .env
php artisan key:generate

# 4. Configure database connection in .env
# DB_CONNECTION=pgsql
# DB_HOST=127.0.0.1
# DB_PORT=5432
# DB_DATABASE=land_manager
# DB_USERNAME=postgres
# DB_PASSWORD=your_password

# 5. Run migrations
php artisan migrate

# 6. Create the storage symlink
php artisan storage:link

# 7. Install frontend dependencies
npm install

# 8. Start development servers (two terminals)
php artisan serve        # Terminal 1 — Laravel server at :8000
npm run dev              # Terminal 2 — Vite dev server at :5173

# OR use the concurrent script:
composer dev
```

### Useful Commands

```bash
php artisan migrate:fresh --seed   # Reset DB with seeders
php artisan tinker                 # Interactive REPL
php artisan route:list             # View all routes
npm run build                      # Production bundle
```

---

## 10. Conventions & Guidelines

### Backend

- **Controller Authorization**: Use private `authorizeProject()` / `authorizeClient()` / `authorizeAdminAction()` helper methods for tenant isolation and role checks. Do **not** use Laravel Policies for now.
- **Inertia Props**: Transform Eloquent models to plain arrays in the controller before passing to `Inertia::render()`. Never pass raw model instances.
- **File Uploads**: Always store to the `public` disk. Validate mimes and max size (10MB standard).
- **Currency**: Format as Colombian Peso (`COP`), 0 decimal places, using `Intl.NumberFormat('es-CO')` on the frontend.

### Frontend

- **Icon Registration**: Every icon used in templates **must** be imported and registered in `resources/js/app.js` via `addIcons()`. Missing registrations will cause render failures.
- **Modals over Navigation**: Prefer modal-based creation/editing flows (e.g., `CreateClientModal.vue`) over navigating to dedicated pages.
- **State-Driven Styling**: Use computed functions like `getStatusClasses()` / `getStatusLabelColor()` instead of inline conditional classes.
- **Notifications**: Use `Toast.vue` (auto-reads Inertia flash messages). Never use `window.alert()` or `window.confirm()`.

### Git & Versioning

- Commit messages should reference the module: `[Finances] Add receipt PDF generation`
- Database changes must always use migrations — never alter tables manually.

---

> **Last Updated**: March 10, 2026  
> **Maintained by**: Development Team — Land Manager SaaS
