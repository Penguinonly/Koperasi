# Desktop App Koperasi Digital

Folder ini disiapkan sebagai wrapper Tauri untuk `apps/web`. Target role hanya Karyawan/Penagih, Admin, dan Master Admin.

Rencana integrasi:

1. Build `apps/web` sebagai aset statis.
2. Tauri memuat aset web tersebut untuk role Karyawan, Admin, dan Master Admin.
3. Login desktop mengirim `platform=DESKTOP` agar backend menolak akun Nasabah.
4. Fitur offline ringan dapat ditambahkan kemudian untuk cache daftar tagihan harian.
5. Source Android tidak diletakkan di sini; Android Studio memakai field di `docs/android-fields.md`.

Inisialisasi Tauri penuh bisa dilakukan dengan:

```bash
pnpm dlx create-tauri-app .
```
