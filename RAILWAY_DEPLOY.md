# Deploy ke Railway

Panduan deploy Laravel + Inertia + Vue ini ke Railway.

> ✅ Lebih cocok daripada Vercel: always-on, MySQL built-in, storage persisten,
> queue/schedule jalan, tanpa hack serverless.

---

## 1. File yang sudah disiapkan

| File | Fungsi |
|------|--------|
| `nixpacks.toml` | Build config (PHP 8.3, Node 20, Composer, npm) |
| `railway.json` | Deploy config (start command, health check, restart policy) |

---

## 2. Persiapan

### Generate APP_KEY
Di terminal lokal:
```bash
php artisan key:generate --show
```
Catat output `base64:......` untuk dipakai nanti.

### Commit & push ke GitHub
```bash
git add nixpacks.toml railway.json RAILWAY_DEPLOY.md
git commit -m "chore: add railway deploy config"
git push
```

Pastikan repo ada di GitHub.

---

## 3. Buat project di Railway

1. Buka https://railway.app → **Login with GitHub**
2. Verifikasi email (kalau diminta) dan tambahkan kartu kredit untuk verifikasi anti-abuse (tidak dicharge selama dalam free $5 credit/bulan)
3. Dashboard → **New Project** → **Deploy from GitHub repo**
4. Pilih repo `warehouse` → klik **Deploy Now**
5. Railway mulai build (gagal di percobaan pertama itu wajar — env belum diisi)

---

## 4. Tambahkan MySQL

1. Di project yang sama → tombol **+ New** (pojok kanan atas) → **Database** → **Add MySQL**
2. Railway buat service MySQL otomatis dengan variable `MYSQLHOST`, `MYSQLPORT`, `MYSQLDATABASE`, `MYSQLUSER`, `MYSQLPASSWORD`

---

## 5. Set environment variables Laravel

Klik service Laravel (bukan MySQL) → tab **Variables** → **Raw Editor** → paste:

```
APP_NAME=Warehouse Demo
APP_ENV=production
APP_KEY=base64:GANTI_DENGAN_HASIL_GENERATE
APP_DEBUG=false
APP_URL=https://${{RAILWAY_PUBLIC_DOMAIN}}
APP_LOCALE=id
APP_FALLBACK_LOCALE=en

LOG_CHANNEL=stderr
LOG_LEVEL=info

DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_DATABASE=${{MySQL.MYSQLDATABASE}}
DB_USERNAME=${{MySQL.MYSQLUSER}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false

CACHE_STORE=database
QUEUE_CONNECTION=database
BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local

MAIL_MAILER=log
MAIL_FROM_ADDRESS=demo@example.com
MAIL_FROM_NAME=${APP_NAME}

VITE_APP_NAME=${APP_NAME}
```

**Klik Update Variables**. Railway auto-redeploy.

> 🔗 Syntax `${{MySQL.MYSQLHOST}}` adalah **reference variable** Railway —
> otomatis terisi dari service MySQL. Jangan diketik literal value-nya.

---

## 6. Generate domain publik

Service Laravel → **Settings** → scroll ke **Networking** → **Generate Domain**.

Dapat URL seperti `warehouse-production-xxxx.up.railway.app`.

---

## 7. Verifikasi

Setelah build hijau (icon ✅ di Deployments):

1. Buka `https://your-app.up.railway.app/up` → JSON health check
2. Buka root URL → aplikasi tampil
3. Cek **Logs** tab kalau ada error

---

## 8. Seed data demo (opsional)

Kalau punya seeder, jalankan via Railway **Web Shell**:

1. Service Laravel → tombol **⋯** di pojok kanan atas → **Shell**
2. Jalankan:
   ```bash
   php artisan db:seed --force
   ```

Atau tambahkan ke `start command` (di `railway.json` atau `nixpacks.toml`):
```
php artisan migrate --force && php artisan db:seed --force && php artisan serve ...
```
⚠️ Hati-hati: ini akan re-seed setiap restart. Lebih baik manual sekali via shell.

---

## 9. Troubleshooting

| Gejala | Fix |
|--------|-----|
| Build gagal di `composer install` | Cek `composer.json` valid, PHP version cocok (8.3) |
| Build gagal di `npm run build` | Set Node 20 di `nixpacks.toml` (sudah default), cek `vite.config.js` |
| 500 saat akses | Set `APP_DEBUG=true` sementara → buka URL → lihat error → fix env |
| "could not find driver mysql" | Tambah `pdo_mysql` di nixPkgs: `php83Extensions.pdo_mysql` |
| Migrate gagal | Pastikan variable `${{MySQL.*}}` reference tersedia |
| Asset Vue 404 | Cek `npm run build` sukses di build log → folder `public/build` ada |

Cek logs realtime di tab **Deployments → klik deploy → View Logs**.

---

## 10. Update / redeploy

Push ke branch yang ter-connect (`main`):
```bash
git push origin main
```
Railway auto-deploy.

---

## 11. Pakai credit hemat

- Free tier: **$5/bulan** = ~500 jam single small service.
- App kecil ini ~$0.50/hari kalau always-on → bisa 10 hari penuh per bulan free.
- Untuk demo singkat: deploy, demo, **lalu delete project** kalau sudah selesai
  supaya credit tidak habis untuk bulan depan.

---

## 12. Catatan: file Vercel

File `vercel.json` dan `api/index.php` tetap ada di repo dan **tidak ganggu** Railway
(Railway pakai `nixpacks.toml` + `railway.json`, ignore yang lain). Boleh kamu hapus
kalau sudah pasti tidak pakai Vercel:

```bash
git rm vercel.json
git rm -r api/
git commit -m "chore: drop vercel config"
```
