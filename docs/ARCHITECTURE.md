# 🏗️ Architecture

## ภาพรวม

```
┌─────────────────┐    HTTP/JSON    ┌─────────────────┐    SQL    ┌──────────────┐
│    Frontend     │ ────────────▶  │    Backend      │ ───────▶ │  PostgreSQL  │
│   Vue 3 + TS    │ ◀────────────  │  Pure PHP + SC  │ ◀─────── │     16       │
└─────────────────┘                └─────────────────┘           └──────────────┘
    Port 5173                           Port 8080                   Port 5432
```

---

## Backend Architecture: Modular Clean Architecture

แต่ละ Module แบ่งเป็น 4 layers ที่ไม่ผสมกัน:

```
app/
├── Core/                     # Infrastructure framework layer
│   ├── Database/Connection   # PDO singleton
│   ├── Http/Response         # JSON response factory
│   ├── Router/Router         # Symfony Routing dispatcher
│   ├── Middleware/Auth       # JWT authentication
│   ├── Middleware/Cors       # CORS headers
│   └── Init/DefaultUser      # First-run seed
│
├── Modules/
│   └── {ModuleName}/
│       ├── Domain/           # Pure business objects, no dependencies
│       │   └── Entity/       # Value objects (no DB, no HTTP)
│       ├── Application/      # Business logic, depends only on Domain
│       │   └── UseCase/      # Orchestrates domain + repository calls
│       ├── Infrastructure/   # Data access, depends on Domain
│       │   └── Repository/   # PDO queries, returns plain arrays
│       └── Transport/        # HTTP adapter, depends on Application
│           └── HTTP/
│               └── Controllers/   # Parse request → call UseCase → return Response
│
└── Shared/                   # Cross-cutting utilities
    └── Helpers/
        ├── Sanitizer         # Input sanitization
        └── MoneyHelper       # Money formatting
```

### กฎ Layer Dependencies (ห้ามละเมิด)

```
Transport → Application → Infrastructure → (database)
Transport → Domain
Application → Domain
Infrastructure → Domain

Transport ห้ามเรียก Infrastructure โดยตรง
Domain ห้ามมี dependencies ใดๆ
```

### เมื่อไหร่ใช้ UseCase vs ทำใน Controller โดยตรง

| สถานการณ์ | แนวทาง |
|-----------|--------|
| มี business logic ซับซ้อน (validation, transaction) | ใช้ UseCase |
| ต้องการ reuse logic | ใช้ UseCase |
| DELETE ง่ายๆ ไม่มี logic พิเศษ | ทำใน Controller โดยตรงได้ |
| Simple list/read ที่ไม่ซับซ้อน | ทำใน Controller โดยตรงได้ |

ตัวอย่าง Controller ที่ทำตรงได้:
- `SizeController::destroy` — DELETE ตรงไปยัง Repository
- `TypeController::destroy` — DELETE ตรงไปยัง Repository
- `OrderController::destroy` — DELETE ตรงไปยัง Repository (CASCADE)

---

## Backend Request Flow

```
HTTP Request
    │
    ▼
public/index.php
    │  load .env, connect DB, seed default user
    ▼
CorsMiddleware::handle()
    │  set CORS headers, handle OPTIONS preflight
    ▼
Router::dispatch()
    │  match URL → find controller + action
    │  if _auth: AuthMiddleware::authenticate() → inject $authUser
    ▼
Controller::action($request, $authUser, $id)
    │  sanitize input
    │  call UseCase (or Repository directly for simple ops)
    ▼
UseCase::execute()
    │  business validation
    │  call Repository
    ▼
Repository::method()
    │  PDO query
    ▼
Response::json() / success() / error() / paginate()
    │
    ▼
HTTP Response (JSON)
```

---

## Frontend Architecture

```
src/
├── assets/css/theme.css    # Single source of truth for all colors
├── main.ts                 # App entry: Vue + Pinia + Router
├── App.vue                 # Root component (renders RouterView + AppAlert)
│
├── router/                 # Vue Router (auth guards)
├── stores/                 # Pinia stores (global state)
│   ├── auth.store          # JWT token + user profile
│   ├── cart.store          # POS cart items
│   └── alert.store         # Global alert modal state
│
├── services/               # API communication layer (Axios)
│   ├── api.ts              # Axios instance (token injection + 401 handling)
│   └── *.service.ts        # Per-domain API functions
│
├── composables/            # Reusable Vue composition functions
│   ├── useAlert.ts         # Shortcut to alertStore
│   └── useMoney.ts         # Money input state management
│
├── types/index.ts          # All TypeScript interfaces
├── utils/                  # Pure utility functions (no Vue/Pinia)
│   ├── money.ts            # formatMoney, parseMoney
│   └── date.ts             # Thai date formatting
│
├── components/
│   ├── ui/                 # Generic reusable UI components
│   │   ├── AppModal        # Base modal (Teleport + backdrop)
│   │   ├── AppAlert        # Global alert modal
│   │   ├── AppButton       # Button variants
│   │   ├── MoneyInput      # Money input (focus/blur formatting)
│   │   ├── AppPagination   # Pagination control
│   │   └── AppTable        # Table wrapper
│   └── layout/             # Layout-specific components
│       ├── AppSidebar      # Navigation sidebar
│       └── AppNavbar       # Top navigation bar
│
├── layouts/
│   ├── AuthLayout          # Centered card layout (Login)
│   └── AppLayout           # Sidebar + Navbar + Content
│
└── pages/                  # Route-level page components
    ├── auth/LoginPage
    ├── dashboard/DashboardPage
    ├── pos/PosPage
    ├── orders/OrdersPage
    ├── products/ProductsPage
    ├── sizes/SizesPage
    ├── types/TypesPage
    ├── toppings/ToppingsPage
    └── users/UsersPage
```

### Data Flow

```
User Action (click/input)
    │
    ▼
Page Component
    │  calls service function
    ▼
*.service.ts
    │  Axios request via api.ts
    │  (auto-attaches JWT token)
    ▼
Backend API (JSON response)
    │
    ▼
Service returns typed data
    │
    ▼
Component updates local state / Pinia store
    │
    ▼
Vue renders updated UI
```

### Alert System

```
Component calls: useAlert().success('message')
    │
    ▼
alertStore.success() sets visible=true + options
    │
    ▼
AppAlert.vue (in App.vue) reads alertStore
    │
    ▼
Modal appears with backdrop (no browser alert())
```

---

## Authentication Flow

```
1. User submits login form
2. POST /api/auth/login {username, password}
3. Backend validates → returns {token, user}
4. Frontend stores token in localStorage + Pinia auth.store
5. All subsequent API calls: Authorization: Bearer <token>
6. Router guard checks isAuthenticated before each navigation
7. On 401 response: Axios interceptor clears token + redirects to /login
```

---

## Docker Network

```
docker network: mini_pos_default (internal)

frontend (5173) ──── proxy /api ───▶ backend:8080
backend  (8080) ──── PDO ──────────▶ db:5432

External access:
  localhost:5173 → frontend
  localhost:8080 → backend
  localhost:5432 → db (for DB tools)
```
