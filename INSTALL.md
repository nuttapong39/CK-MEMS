# 📘 คู่มือการติดตั้ง CK-MEMS สำหรับมือใหม่

> คู่มือนี้เขียนสำหรับคนที่ **ไม่เคยใช้ Laravel/Vue มาก่อน** ทำตามทีละขั้นได้เลย
> ใช้เวลา **30–45 นาที** สำหรับการติดตั้งครั้งแรก (ครั้งถัดไป ~5 นาที)

---

## 📋 สรุปขั้นตอน (Overview)

```
1. ติดตั้ง XAMPP             (PHP + MySQL ในก้อนเดียว)
2. ติดตั้ง Composer          (ตัวจัดการ package ของ PHP)
3. ติดตั้ง Node.js           (สำหรับ frontend Vue + Tailwind)
4. ติดตั้ง Git               (สำหรับ clone โปรเจกต์)
5. Clone โปรเจกต์ CK-MEMS    จาก GitHub
6. เปิด PHP extensions       ในไฟล์ php.ini
7. composer install          ดาวน์โหลด PHP packages
8. ตั้งค่า .env              เชื่อมต่อฐานข้อมูล
9. สร้างฐานข้อมูล + migrate
10. npm install + build      ดาวน์โหลด JS packages และคอมไพล์
11. เปิดใช้งาน                php artisan serve
12. Login ด้วย admin           admin@ck-mems.local / admin1234
```

---

## 1️⃣ ติดตั้ง XAMPP (PHP + MySQL)

XAMPP คือชุดโปรแกรมที่รวม **PHP**, **MySQL/MariaDB**, **Apache** ไว้ในตัวเดียว ติดตั้งง่ายที่สุด

### 1.1 ดาวน์โหลด

ไปที่ https://www.apachefriends.org/download.html

เลือกเวอร์ชัน **PHP 8.2.x** ขึ้นไป (Windows / macOS / Linux)

### 1.2 ติดตั้ง

- กด Next ไปเรื่อย ๆ — ใช้ค่า default ทั้งหมด
- ที่อยู่การติดตั้ง: `C:\xampp\` (Windows) หรือ `/opt/lampp/` (Linux) — **อย่าเปลี่ยน**

### 1.3 เปิดใช้งาน

- เปิด **XAMPP Control Panel**
- กด **Start** ที่บรรทัด **Apache** และ **MySQL**

> ⚠️ ถ้า MySQL ไม่ start อาจมีโปรแกรมอื่นใช้ port 3306 อยู่ — ตรวจดูว่าไม่มี MySQL Server ตัวอื่นรันอยู่

### 1.4 ตรวจสอบว่าใช้งานได้

เปิดเบราว์เซอร์ไปที่ http://localhost/phpmyadmin/ — ถ้าเห็นหน้า phpMyAdmin = สำเร็จ ✓

---

## 2️⃣ ติดตั้ง Composer

Composer คือตัวจัดการ packages ของ PHP (เหมือน npm ของ JavaScript)

### 2.1 ดาวน์โหลด installer

ไปที่ https://getcomposer.org/Composer-Setup.exe (Windows)

หรือ macOS/Linux:
```bash
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php --install-dir=/usr/local/bin --filename=composer
php -r "unlink('composer-setup.php');"
```

### 2.2 ติดตั้ง (Windows)

- รัน `Composer-Setup.exe`
- เมื่อถามที่อยู่ของ `php.exe` → เลือก `C:\xampp\php\php.exe`
- กด Next ไปเรื่อย ๆ

### 2.3 ตรวจสอบ

เปิด **Command Prompt** หรือ **PowerShell** แล้วพิมพ์:

```bash
composer --version
```

ถ้าเห็น `Composer version 2.x.x` = สำเร็จ ✓

---

## 3️⃣ ติดตั้ง Node.js

Node.js คือ runtime สำหรับ JavaScript ที่จำเป็นสำหรับ build frontend Vue

### 3.1 ดาวน์โหลด

ไปที่ https://nodejs.org/en/download

เลือก **LTS version** (v20 ขึ้นไป) — ติดตั้งโดยใช้ค่า default

### 3.2 ตรวจสอบ

เปิด terminal ใหม่:
```bash
node -v    # ควรแสดง v20.x.x หรือใหม่กว่า
npm -v     # ควรแสดง 10.x.x หรือใหม่กว่า
```

---

## 4️⃣ ติดตั้ง Git

Git ใช้สำหรับ clone โปรเจกต์ลงเครื่อง

### 4.1 ดาวน์โหลด

- **Windows:** https://git-scm.com/download/win
- **macOS:** ติดตั้ง Xcode Command Line Tools — `xcode-select --install`
- **Linux:** `sudo apt install git` (Ubuntu) หรือ `sudo yum install git` (CentOS)

### 4.2 ตั้งค่าครั้งแรก

```bash
git config --global user.name "ชื่อของคุณ"
git config --global user.email "your_email@example.com"
```

---

## 5️⃣ Clone โปรเจกต์ CK-MEMS

เปิด terminal/Command Prompt ที่โฟลเดอร์ `C:\xampp\htdocs\` (หรือ `/opt/lampp/htdocs/` บน Linux)

```bash
cd C:\xampp\htdocs
git clone https://github.com/nuttapong39/CK-MEMS.git
cd CK-MEMS
```

โฟลเดอร์ใหม่ชื่อ `CK-MEMS` จะถูกสร้างขึ้น

---

## 6️⃣ เปิด PHP Extensions

CK-MEMS ใช้ extensions หลายตัวที่ XAMPP ปิดไว้ default ต้องเปิดก่อน

### 6.1 เปิดไฟล์ php.ini

ไปที่ `C:\xampp\php\php.ini` เปิดด้วย Notepad++ หรือ VS Code

### 6.2 ค้นหาและเปิด extensions ต่อไปนี้

ใช้ Ctrl+F ค้นหาแต่ละบรรทัด แล้ว **ลบเครื่องหมาย `;`** ที่อยู่หน้าบรรทัด:

```ini
;extension=zip          →  extension=zip
;extension=gd           →  extension=gd
;extension=intl         →  extension=intl
;extension=sodium       →  extension=sodium
;extension=openssl      →  extension=openssl
;extension=exif         →  extension=exif
;extension=bcmath       →  extension=bcmath
```

ตรวจสอบว่าบรรทัดต่อไปนี้ **ไม่มี `;`** อยู่ข้างหน้า:

```ini
extension=curl
extension=fileinfo
extension=mbstring
extension=pdo_mysql
```

### 6.3 บันทึกไฟล์ + Restart Apache

กลับไปที่ **XAMPP Control Panel** → กด **Stop** ที่ Apache แล้วกด **Start** ใหม่

### 6.4 ตรวจสอบ extensions

```bash
C:\xampp\php\php.exe -m
```

ถ้าเห็น `zip`, `gd`, `intl`, `sodium`, `openssl`, `exif`, `bcmath`, `curl`, `pdo_mysql` ในรายการ = สำเร็จ ✓

---

## 7️⃣ Composer Install

ดาวน์โหลด PHP packages ทั้งหมดที่โปรเจกต์ใช้:

```bash
cd C:\xampp\htdocs\CK-MEMS
composer install
```

จะใช้เวลา **3-5 นาที** ขึ้นกับความเร็วเน็ต

> 💡 ถ้า terminal บอกว่า `composer` ไม่ใช่คำสั่งที่รู้จัก ให้ปิด-เปิด terminal ใหม่ หรือใช้:
> `C:\xampp\php\php.exe C:\ProgramData\ComposerSetup\bin\composer.phar install`

---

## 8️⃣ ตั้งค่า .env

`.env` คือไฟล์เก็บการตั้งค่าโปรเจกต์ (รหัสฐานข้อมูล, JWT key, MOPH key ฯลฯ)

### 8.1 คัดลอก template

```bash
copy .env.example .env       # Windows
# หรือ
cp .env.example .env         # macOS/Linux
```

### 8.2 สร้าง APP_KEY และ JWT_SECRET

```bash
php artisan key:generate
php artisan jwt:secret --force
```

ทั้งสองคำสั่งนี้จะใส่ random key ลงใน `.env` ให้อัตโนมัติ

### 8.3 (ถ้าจำเป็น) แก้ค่าฐานข้อมูล

เปิด `.env` ตรวจดูว่าค่าต่อไปนี้ตรงกับเครื่องของคุณ:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ck_mems
DB_USERNAME=root
DB_PASSWORD=
```

> ⚠️ XAMPP default — `username=root`, password ว่าง ถ้าตั้งรหัสผ่านไว้ก็ใส่ตรง `DB_PASSWORD=`

---

## 9️⃣ สร้างฐานข้อมูล + Migrate + Seed

### 9.1 สร้างฐานข้อมูล

วิธี A — ผ่าน phpMyAdmin:
- เปิด http://localhost/phpmyadmin/
- กด **New** ทางซ้าย
- ใส่ชื่อ `ck_mems`
- เลือก collation: `utf8mb4_unicode_ci`
- กด **Create**

วิธี B — ผ่าน command line:
```bash
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE ck_mems CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 9.2 รัน Migration + Seed

```bash
php artisan migrate --seed
```

จะเห็นข้อความแบบนี้:
```
✓ Migrating 22 tables done
✓ Hospital seeded: CK - โรงพยาบาลเชียงกลาง
✓ Roles & permissions seeded
✓ Departments seeded: 15
✓ Equipment codes seeded: 46
✓ Admin user: admin@ck-mems.local / admin1234
✓ Flex templates seeded: 7
```

### 9.3 (Optional) Import ข้อมูลเครื่องมือ 55 รายการจาก Excel

```bash
php artisan ckmems:seed-from-excel
```

---

## 🔟 NPM Install + Build

### 10.1 ติดตั้ง JS packages

```bash
npm install
```

จะใช้เวลา **2-3 นาที**

### 10.2 Build frontend

**ทางเลือก A — Production build (สำหรับใช้งานจริง)**:
```bash
npm run build
```

**ทางเลือก B — Development mode (สำหรับแก้โค้ด — auto reload)**:
```bash
npm run dev
```

> 💡 สำหรับการใช้งานปกติ ใช้ A พอ ครั้งหน้าแก้โค้ดแล้วค่อย B

> ⚠️ **PowerShell 5.1 บน Windows** อาจ block การรัน `npm.ps1` ด้วย ExecutionPolicy
> ถ้าเจอ error `running scripts is disabled on this system`:
> ```powershell
> Set-ExecutionPolicy -Scope CurrentUser -ExecutionPolicy RemoteSigned
> ```
> หรือใช้ `npm.cmd` แทน `npm`:
> ```bash
> "C:\Program Files\nodejs\npm.cmd" install
> "C:\Program Files\nodejs\npm.cmd" run build
> ```

---

## 1️⃣1️⃣ เปิดใช้งาน

### 11.1 รัน Laravel server

```bash
php artisan serve
```

ระบบจะเปิด port 8000 — เห็นข้อความ:
```
INFO  Server running on [http://127.0.0.1:8000]
```

### 11.2 เปิดเบราว์เซอร์

ไปที่ http://127.0.0.1:8000

จะเห็นหน้า **Login** ของ CK-MEMS

---

## 1️⃣2️⃣ Login ครั้งแรก

| ฟิลด์ | ค่า |
|---|---|
| Email | `admin@ck-mems.local` |
| Password | `admin1234` |

เมื่อ login สำเร็จจะเห็น **Dashboard** + 6 stat cards + เมนู sidebar

---

## ✅ ติดตั้งสำเร็จ! ลองทดลองใช้

| หน้า | เมนู | สิ่งที่ทำได้ |
|---|---|---|
| `/dashboard` | หน้าแรก | ดู stat cards |
| `/equipment` | รายการเครื่องมือ | ดู 55 เครื่องมือที่ import มาจาก Excel |
| `/equipment/create` | เพิ่มเครื่องมือ | ทดลองเพิ่ม — ระบบสร้าง ID Code อัตโนมัติ |
| `/repair/create` | แจ้งซ่อม | สร้าง ticket ใหม่ |
| `/repair` | ประวัติการซ่อม | ดู timeline |
| `/calibration` | สอบเทียบ | บันทึกผลการสอบเทียบ |
| `/qrcode` | QR Designer | สร้าง QR สำหรับ print |
| `/qr/CM-LR-DEF-01` | Public scan page | สแกนจากมือถือดูได้เลย |
| `/moph/settings` | MOPH Alert | ตั้งค่า notification |
| `/reports` | รายงาน | Export PDF/Excel |
| `/users` | จัดการผู้ใช้ | เพิ่ม staff/admin คนใหม่ |

---

## 🛠 Troubleshooting

### ❌ "Class 'Illuminate\Support\Carbon' not found" หรือ extension errors
**วิธีแก้:** กลับไปขั้นตอน [6️⃣](#6%EF%B8%8F⃣-เปิด-php-extensions) ตรวจ extensions ใน php.ini

### ❌ "SQLSTATE[HY000] [2002] No connection could be made..."
**วิธีแก้:** MySQL ไม่ได้รัน — เปิด XAMPP Control Panel กด Start ที่ MySQL

### ❌ "Vite manifest not found"
**วิธีแก้:** ยังไม่ได้ build frontend — รัน `npm run build` หรือ `npm run dev`

### ❌ "Class 'PHPOpenSourceSaver\JWTAuth\...' not found"
**วิธีแก้:** vendor ขาด — รัน `composer install` ใหม่

### ❌ "การลบ ib_logfile" / InnoDB recovery error
**วิธีแก้:** หยุด MySQL → คัดลอก `C:\xampp\mysql\backup\*` ไปทับ `C:\xampp\mysql\data\` → start ใหม่

### ❌ Login ไม่ผ่าน "Unauthenticated" 401
**วิธีแก้:** JWT ยังไม่ได้สร้าง — รัน `php artisan jwt:secret --force` แล้ว clear cache:
```bash
php artisan config:clear
php artisan cache:clear
```

### ❌ NPM install ค้างนานเกิน 5 นาที
**วิธีแก้:** เน็ตช้า/timeout — ลองเปลี่ยน registry:
```bash
npm config set registry https://registry.npmjs.org/
npm install --no-audit --no-fund
```

### ❌ "Permission denied" บน Linux/macOS
**วิธีแก้:**
```bash
sudo chown -R $USER:$USER storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

---

## 🔄 ติดตั้งใหม่ครั้งถัดไป (เมื่อ pull update)

ถ้ามีคนแก้โค้ดบน GitHub แล้วคุณอยากดึงมาอัปเดต:

```bash
cd C:\xampp\htdocs\CK-MEMS
git pull
composer install                    # ถ้ามี package ใหม่
npm install                         # ถ้ามี js package ใหม่
php artisan migrate                 # ถ้ามี migration ใหม่
npm run build                       # build frontend
```

ถ้าอยากเริ่มใหม่หมด (รีเซ็ตข้อมูลทั้งหมด):
```bash
php artisan migrate:fresh --seed
php artisan ckmems:seed-from-excel  # ถ้าต้องการ
```

---

## ⏰ ตั้ง Cron / Task Scheduler (Optional)

เพื่อให้ระบบส่งแจ้งเตือนสอบเทียบใกล้ครบกำหนดอัตโนมัติ:

### Windows — Task Scheduler
1. เปิด **Task Scheduler** → Create Basic Task
2. ชื่อ: `CK-MEMS Daily Calibration Check`
3. Trigger: Daily, เวลา 8:00
4. Action: Start a program
   - Program: `C:\xampp\php\php.exe`
   - Arguments: `artisan ckmems:notify-calibration-due`
   - Start in: `C:\xampp\htdocs\CK-MEMS`

### Linux — crontab
```bash
crontab -e
# เพิ่มบรรทัด:
0 8 * * * cd /opt/lampp/htdocs/CK-MEMS && /opt/lampp/bin/php artisan ckmems:notify-calibration-due
```

---

## 📞 ขอความช่วยเหลือ

- 🐛 **Bug / ปัญหาการใช้งาน:** เปิด issue ที่ https://github.com/nuttapong39/CK-MEMS/issues
- 📖 **Laravel docs:** https://laravel.com/docs/12.x
- 📖 **Vue docs:** https://vuejs.org/
- 📖 **Tailwind docs:** https://tailwindcss.com/

---

🎉 **ขอให้ใช้งาน CK-MEMS ได้อย่างราบรื่น!**
