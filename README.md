# CK-MEMS — Medical Equipment Management System

ระบบบริหารจัดการเครื่องมือทางการแพทย์ของ **โรงพยาบาลเชียงกลาง อ.เชียงกลาง จ.น่าน** ออกแบบเป็น **multi-tenant** รองรับโรงพยาบาลอื่น

## 🏗 Tech Stack

- **Backend:** Laravel 12.58 · PHP 8.2 · MySQL/MariaDB
- **Frontend:** Vue 3 · Tailwind CSS v4 · Vite · Pinia · Vue Router 4 · SweetAlert2
- **Auth:** JWT (php-open-source-saver/jwt-auth) + role/permission (spatie/laravel-permission)
- **Audit:** spatie/laravel-activitylog
- **Excel:** maatwebsite/excel
- **PDF:** barryvdh/laravel-dompdf
- **QR Code:** endroid/qr-code v6

## ✨ Features

| Module | Status |
|---|:---:|
| Authentication (JWT + Role/Permission + Provider ID stub) | ✅ |
| Equipment CRUD (auto ID, modal code picker, soft delete) | ✅ |
| Repair workflow — 7-state machine + timeline | ✅ |
| Calibration + auto next-due + cron alert | ✅ |
| Dashboard with hero card + stat cards | ✅ |
| MOPH Alert — service + flex designer + logs | ✅ |
| QR Code Designer + public scan page + batch PDF | ✅ |
| Reports — Equipment/Repair/Calibration (PDF + Excel) | ✅ |
| Calibration Certificate PDF (formal layout) | ✅ |
| User Management (Admin) | ✅ |
| Activity log (spatie) on key models | ✅ |

## 🚀 Setup

```bash
# 1. Clone & install
git clone https://github.com/nuttapong39/CK-MEMS.git
cd CK-MEMS
composer install
npm install

# 2. Configure
cp .env.example .env
php artisan key:generate
php artisan jwt:secret --force
# edit .env — DB credentials, MOPH keys, hospital info

# 3. Database
mysql -u root -e "CREATE DATABASE ck_mems CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan migrate --seed

# 4. (Optional) Import the source Excel data
# Place the JSON dumps in database/data/seed/ first, then:
php artisan ckmems:seed-from-excel

# 5. Build frontend
npm run build
# OR for development with HMR:
# npm run dev

# 6. Serve
php artisan serve
```

Open http://127.0.0.1:8000 — default credentials: `admin@ck-mems.local` / `admin1234`

## 📐 Architecture

```
app/
├── Http/Controllers/Api/V1/    # 12 controllers (Auth, Equipment, Repair, Calibration, ...)
├── Http/Resources/              # API resources (UserResource, EquipmentResource, ...)
├── Http/Requests/               # FormRequest validation
├── Http/Middleware/RoleGate.php # role-based gate
├── Models/                      # 14 Eloquent models
├── Services/                    # business logic (RepairWorkflow, MophAlert, …)
├── Exports/                     # Maatwebsite Excel exports
└── Console/Commands/            # ckmems:seed-from-excel, notify-calibration-due

resources/
├── js/
│   ├── pages/                   # Vue pages (auth, dashboard, equipment, repair, …)
│   ├── components/              # reusable components
│   ├── stores/                  # Pinia stores (auth, master)
│   ├── api/                     # axios API modules
│   ├── composables/             # repairStatus tokens
│   ├── layouts/                 # AuthLayout, AppLayout
│   └── router/                  # Vue Router
└── views/
    ├── app.blade.php            # SPA shell
    ├── pdf/                     # DomPDF templates (qrcode-batch, equipments-list, ...)
    └── qr/equipment.blade.php   # public scan page (no auth)

database/
├── migrations/                  # 22 tables
├── seeders/                     # Hospital, Roles, Departments, EquipmentCodes, …
└── data/seed/                   # JSON fixtures from source Excel
```

## 🔐 Roles

| Role | Capabilities |
|---|---|
| `admin` | Everything — User/QR/MOPH management, full CRUD |
| `staff` | Equipment + Repair + Calibration + Reports |
| `user` | Repair report + Dashboard only (login via Provider ID) |

## ⏰ Cron

Add to crontab / Windows Task Scheduler (daily):
```bash
php artisan ckmems:notify-calibration-due
# Optional flags:
#   --days=30        Look-ahead window (default 30)
#   --dry-run        Print without sending
```

## 📜 License

This project was built for hospital internal use. Adapt as needed.

---
🤖 Generated with [Claude Code](https://claude.com/claude-code)
