# Deploy Demo ke Vercel

Panduan deploy aplikasi Laravel + Inertia + Vue ini sebagai **demo** di Vercel.

> ⚠️ **Catatan penting**: Vercel = serverless + filesystem read-only (kecuali `/tmp`).
> Cocok untuk **demo / showcase**, **tidak cocok untuk production** dengan data
> persisten karena SQLite di `/tmp` akan hilang saat cold start.
> Untuk production gunakan Railway, Fly.io, Forge, atau VPS biasa.

---

## 1. File yang sudah disiapkan

| File | Fungsi |
|------|--------|
| `vercel.json` | Konfigurasi build (PHP + Vite) dan routing |
| `api/index.php` | Entry point serverless – setup `/tmp` storage, seed SQLite, bootstrap Laravel |
| `package.json` → `build` script | Dijalankan otomatis Vercel (`vite build`) |

Routing di `vercel.json`:
- `/build/*` → asset Vite hasil build
- `/assets/*`, `/images/*`, `/favicon.ico`, `/robots.txt` → static dari `public/`
- semua URL lain → `api/index.php` (Laravel)

---

## 2. Persiapan repo

1. Pastikan sudah commit perubahan:
   ```bash
   git add api vercel.json VERCEL_DEPLOY.md
   git commit -m "chore: setup vercel demo deploy"
   git push
   ```
2. Push ke GitHub / GitLab / Bitbucket.

---

## 3. Import project di Vercel

1. Buka https://vercel.com/new
2. **Import Git Repository** → pilih repo ini.
3. **Framework Preset**: pilih **Other** (jangan pilih Laravel preset bawaan).
4. **Root Directory**: biarkan default (`./`).
5. **Build & Output Settings**: kosongkan semua — diatur oleh `vercel.json`.
6. Klik **Deploy** (akan gagal pertama kali sebelum env diisi — itu wajar).

---

## 4. Environment Variables (WAJIB)

Buka **Project Settings → Environment Variables**, lalu tambahkan satu per satu
(centang semua environment: Production, Preview, Development).

### 4a. Wajib

| Key | Value | Keterangan |
|-----|-------|------------|
| `APP_NAME` | `Warehouse Demo` | Nama aplikasi |
| `APP_ENV` | `production` | |
| `APP_KEY` | _generate baru_ | Lihat cara di bawah ⬇️ |
| `APP_DEBUG` | `false` | Set `true` sementara saat troubleshoot |
| `APP_URL` | `https://nama-project-kamu.vercel.app` | URL hasil deploy |
| `LOG_CHANNEL` | `stderr` | Log ke stdout Vercel |
| `LOG_LEVEL` | `error` | |

**Generate APP_KEY** (jalankan lokal):
```bash
php artisan key:generate --show
```
Copy output (formatnya `base64:......`) → tempel ke value `APP_KEY` di Vercel.

### 4b. Database (SQLite di /tmp – demo only)

| Key | Value |
|-----|-------|
| `DB_CONNECTION` | `sqlite` |
| `DB_DATABASE` | `/tmp/database.sqlite` |
| `DB_FOREIGN_KEYS` | `true` |

File `database/database.sqlite` akan otomatis di-copy ke `/tmp` saat cold start
(lihat `api/index.php`). **Data tidak persisten** — setiap cold start kembali ke
seed awal. Untuk demo, commit dulu `database/database.sqlite` yang sudah ada
data dummy.

### 4c. Session / Cache / Queue (jangan pakai database)

| Key | Value |
|-----|-------|
| `SESSION_DRIVER` | `cookie` |
| `SESSION_LIFETIME` | `120` |
| `SESSION_ENCRYPT` | `true` |
| `CACHE_STORE` | `array` |
| `QUEUE_CONNECTION` | `sync` |
| `BROADCAST_CONNECTION` | `log` |
| `FILESYSTEM_DISK` | `local` |

### 4d. Mail (opsional – kalau ada fitur kirim email)

| Key | Value |
|-----|-------|
| `MAIL_MAILER` | `log` |
| `MAIL_FROM_ADDRESS` | `demo@example.com` |
| `MAIL_FROM_NAME` | `${APP_NAME}` |

### 4e. Vite (opsional)

| Key | Value |
|-----|-------|
| `VITE_APP_NAME` | `${APP_NAME}` |

---

## 5. Trigger ulang deploy

Setelah env terisi:
- Buka tab **Deployments** → klik deploy paling atas → **Redeploy**
  → centang **Use existing Build Cache** (off) → **Redeploy**.

Atau push commit kosong:
```bash
git commit --allow-empty -m "trigger redeploy"
git push
```

---

## 6. Verifikasi

Setelah deploy sukses (status: **Ready**):

1. Buka URL `https://<project>.vercel.app/up` → harus return JSON health check Laravel.
2. Buka root URL → harus tampil aplikasi (Inertia + Vue).
3. Cek **Runtime Logs** di Vercel kalau ada error 500.

---

## 7. Troubleshooting cepat

| Gejala | Penyebab umum | Fix |
|--------|---------------|-----|
| 500 langsung di root | `APP_KEY` belum diisi | Tambahkan env var, redeploy |
| 500 saat akses route | Session/cache driver salah | Pastikan `SESSION_DRIVER=cookie`, `CACHE_STORE=array` |
| "could not find driver" | Driver DB salah | Set `DB_CONNECTION=sqlite`, `DB_DATABASE=/tmp/database.sqlite` |
| Asset Vue/CSS 404 | Build Vite gagal | Cek **Deployments → Build Logs** apakah `vite build` sukses |
| "permission denied .../storage" | Storage path belum di-redirect | Pastikan `api/index.php` versi terbaru ter-deploy |
| Halaman blank putih | `APP_DEBUG=true` sementara untuk lihat error | Lalu balikkan ke `false` |

Cek log realtime:
- **Vercel dashboard → Project → Logs** (Runtime Logs).
- Atau via CLI: `vercel logs <deployment-url>`.

---

## 8. Limitasi Vercel untuk app ini

- ❌ **Queue/jobs background** tidak jalan (gunakan `QUEUE_CONNECTION=sync`).
- ❌ **Upload file** ke `storage/app/public` hilang setiap cold start.
- ❌ **`php artisan schedule:run`** tidak ada (pakai Vercel Cron terpisah).
- ❌ **Websocket / Livewire long-poll** tidak optimal.
- ❌ **`php artisan migrate`** tidak otomatis — DB di-seed dari file commit.
- ✅ Cocok untuk: **demo read-only**, **showcase UI**, **portfolio**.

---

## 9. Update / redeploy

Cukup push ke branch yang sudah di-connect (biasanya `main`):
```bash
git push origin main
```
Vercel auto-deploy. Preview deploy juga otomatis untuk setiap PR.
