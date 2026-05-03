# 🧾 Mini POS

ระบบขายหน้าร้าน (Point of Sale) ขนาดเบา สำหรับจัดการสินค้า รับออเดอร์ และดูรายงานการขาย

---

## 📦 Tech Stack

| ส่วน | เทคโนโลยี |
|------|-----------|
| Frontend | Vue 3 + TypeScript + Vite + Pinia + TailwindCSS 4 |
| Backend | PHP 8.3 + Symfony Components (pure PHP, no framework) |
| Database | PostgreSQL 16 |
| Container | Docker + Docker Compose |

---

## 🐳 รันด้วย Docker (แนะนำ)

### ข้อกำหนดเบื้องต้น
- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (รวม Docker Compose)

### เริ่มระบบ

```bash
# 1. Clone หรือเข้าไปในโฟลเดอร์โปรเจกต์
cd mini_pos

# 2. เริ่มระบบทั้งหมด (ครั้งแรกจะใช้เวลา build image)
docker compose up -d

# 3. ดู logs ระหว่าง build/start
docker compose logs -f
```

หลังจากรันแล้ว:
| บริการ | URL |
|--------|-----|
| Frontend (Vue) | http://localhost:5173 |
| Backend (PHP API) | http://localhost:8080 |
| PostgreSQL | localhost:5432 |

### หยุดระบบ

```bash
# หยุดระบบ (เก็บข้อมูลไว้)
docker compose down

# หยุดและลบข้อมูล database ทั้งหมด
docker compose down -v
```

### Rebuild เมื่อเปลี่ยน Dockerfile หรือ dependencies

```bash
docker compose up -d --build
```

### ดู Logs

```bash
# ดู logs ทุก service
docker compose logs -f

# ดูเฉพาะ backend
docker compose logs -f backend

# ดูเฉพาะ frontend
docker compose logs -f frontend
```

---

## 🖥️ รันโดยไม่ใช้ Docker

### ข้อกำหนดเบื้องต้น
- PHP 8.2+ พร้อม extensions: `pdo`, `pdo_pgsql`
- Composer
- Node.js 18+ และ npm
- PostgreSQL 16

### 1. ติดตั้ง PostgreSQL

**macOS (Homebrew)**
```bash
brew install postgresql@16
brew services start postgresql@16
```

**Ubuntu/Debian**
```bash
sudo apt install postgresql postgresql-contrib
sudo systemctl start postgresql
```

**Windows**
ดาวน์โหลดจาก https://www.postgresql.org/download/windows/

### 2. สร้าง Database

```bash
# เข้า psql
psql -U postgres

# สร้าง user และ database
CREATE USER mini_pos WITH PASSWORD 'mini_pos_password';
CREATE DATABASE mini_pos OWNER mini_pos;
GRANT ALL PRIVILEGES ON DATABASE mini_pos TO mini_pos;
\q

# Import schema
psql -U mini_pos -d mini_pos -f backend/database/schema.sql
```

### 3. ตั้งค่า Backend

```bash
cd backend

# Copy .env
cp .env.example .env

# แก้ไขค่าใน .env ให้ตรงกับ local setup ของคุณ
# DB_HOST=localhost
# DB_PORT=5432
# DB_NAME=mini_pos
# DB_USER=mini_pos
# DB_PASSWORD=mini_pos_password

# ติดตั้ง PHP dependencies
composer install

# สร้างโฟลเดอร์ที่จำเป็น
mkdir -p public/uploads/products public/uploads/users storage

# รัน PHP development server
php -S localhost:8080 -t public
```

### 4. ตั้งค่า Frontend

```bash
cd frontend

# ติดตั้ง Node dependencies
npm install

# รัน Vite dev server
npm run dev
```

เข้าใช้งานที่ http://localhost:5173

---

## 🔐 บัญชีเริ่มต้น

ระบบจะสร้างบัญชี admin อัตโนมัติเมื่อรันครั้งแรก:

| | |
|--|--|
| **Username** | `mini_pos` |
| **Password** | `pass123` |
| **Role** | admin |

---

## 📁 โครงสร้างโปรเจกต์

```
mini_pos/
├── docker-compose.yml     # Docker Compose configuration
├── .gitignore
├── .editorconfig
├── README.md              # ไฟล์นี้
├── docs/
│   ├── SYSTEM.md          # อธิบายระบบและฟีเจอร์
│   ├── ARCHITECTURE.md    # อธิบาย architecture
│   └── DATABASE.md        # อธิบาย database schema
├── backend/               # PHP Backend
│   ├── app/
│   ├── config/
│   ├── database/
│   ├── public/            # Web root (entry point: index.php)
│   ├── storage/
│   ├── composer.json
│   └── Dockerfile
└── frontend/              # Vue 3 Frontend
    ├── src/
    ├── index.html
    ├── package.json
    ├── vite.config.ts
    └── Dockerfile
```

---

## 🔧 คำสั่งที่มีประโยชน์

```bash
# รีเซ็ต default user (ลบ lock file)
docker compose exec backend rm -f storage/init.lock

# เข้า PHP container
docker compose exec backend sh

# เข้า database
docker compose exec db psql -U mini_pos -d mini_pos

# รัน frontend build (production)
docker compose exec frontend npm run build
```

---

## 🌐 Environment Variables

### Backend (`.env`)

| Variable | Default | คำอธิบาย |
|----------|---------|----------|
| `APP_ENV` | `development` | Environment |
| `APP_DEBUG` | `true` | Debug mode |
| `JWT_SECRET` | - | Secret key สำหรับ JWT (ต้องเปลี่ยนใน production) |
| `DB_HOST` | `localhost` | PostgreSQL host |
| `DB_PORT` | `5432` | PostgreSQL port |
| `DB_NAME` | `mini_pos` | Database name |
| `DB_USER` | `mini_pos` | Database user |
| `DB_PASSWORD` | `mini_pos_password` | Database password |
| `UPLOAD_PATH` | `uploads` | โฟลเดอร์สำหรับ upload ไฟล์ |
| `MAX_UPLOAD_SIZE` | `12288` | ขนาดไฟล์สูงสุด (KB) |
