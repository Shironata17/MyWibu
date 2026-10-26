# MyWibu

MyWibu adalah website katalog dan streaming video anime/movie. Pengguna dapat
mencari tontonan, melihat sinopsis, menulis komentar, serta mengelola akun dan
library. Admin dapat menerbitkan video yang hak distribusinya dimiliki atau
telah diizinkan.

## Tentang proyek

Proyek ini dibuat dengan bantuan AI dan bersifat open source. Kode sumber asli
proyek dilisensikan di bawah MIT; lihat [LICENSE](./LICENSE). Lisensi ini tidak
mencakup aset atau materi pihak ketiga.

## Menjalankan secara lokal

Persyaratan:

- PHP dengan ekstensi `mysqli` dan dukungan `mysqlnd`.
- MySQL atau MariaDB.
- Server web lokal, misalnya Laragon.

Langkah-langkah:

1. Clone atau unduh repositori ke direktori web server.
2. Buat database bernama `mywibu_db`.
3. Sesuaikan host, username, password, dan nama database di
   `view/includes/koneksi.php`.
4. Jalankan PHP dan MySQL melalui server lokal.
5. Buka `http://localhost/MyWibu/`. `index.php` di akar proyek akan meneruskan
   ke beranda di `view/`.
6. Buat akun melalui tautan **Buat akun**. Setelah signup berhasil, pengguna
   otomatis masuk dan diarahkan ke dashboard.

Tabel `users` dibuat saat aplikasi dijalankan; kolom `role` ditambahkan bila
belum ada. Tabel `anime_library`, `videos`, dan `comments` juga disiapkan oleh
aplikasi saat diperlukan. Jangan mengganti tabel `users` dengan skema yang
memiliki tipe ID tidak kompatibel dengan kolom yang merujuknya.

## Fitur

- Beranda dan katalog anime/movie dengan pencarian serta rilisan terbaru.
- Halaman tonton dengan sinopsis dan komentar untuk pengguna yang login.
- Signup, login, profil, dashboard, dan library pribadi.
- Menu dashboard, library, dan profil hanya ditampilkan kepada pengguna login;
  akses langsung ke halaman akun juga memerlukan login.
- Tema terang, gelap, atau mengikuti preferensi perangkat. Pilihan tema manual
  disimpan di browser.
- Panel admin untuk mengunggah video MP4/WebM, poster JPG/PNG/WebP, deskripsi,
  sinopsis, dan genre.

## Menyiapkan akun admin

Akun admin pertama dibuat melalui PHP CLI, bukan lewat form signup. Jalankan
PowerShell dari direktori proyek; password diminta secara tersembunyi dan tidak
boleh disimpan ke README, Git, atau repositori publik. Username dan email harus
unik, tanggal lahir menggunakan format `YYYY-MM-DD`, dan password minimal
12 karakter.

```powershell
$env:MYWIBU_ADMIN_USERNAME = Read-Host "Username admin"
$env:MYWIBU_ADMIN_EMAIL = Read-Host "Email admin"
$env:MYWIBU_ADMIN_BIRTH_DATE = Read-Host "Tanggal lahir (YYYY-MM-DD)"
$secure = Read-Host "Password admin (minimal 12 karakter)" -AsSecureString
$env:MYWIBU_ADMIN_PASSWORD = [System.Net.NetworkCredential]::new("", $secure).Password
php view\admin\create_admin.php
Remove-Item Env:MYWIBU_ADMIN_USERNAME, Env:MYWIBU_ADMIN_EMAIL, Env:MYWIBU_ADMIN_BIRTH_DATE, Env:MYWIBU_ADMIN_PASSWORD
```

Jika `php` belum tersedia di `PATH`, gunakan path lengkap ke `php.exe` Laragon.
Skrip hanya dapat dijalankan melalui CLI, menyimpan password dalam bentuk hash,
dan tidak mengubah akun jika username atau email sudah digunakan.

## Upload media

Video dibatasi hingga 200 MB dan poster hingga 5 MB. Jika konfigurasi PHP
memiliki batas lebih rendah, sesuaikan `upload_max_filesize` dan `post_max_size`
di `php.ini`, kemudian mulai ulang server PHP. Berkas media disimpan di
`uploads/` dan tidak dilacak Git. Pastikan hanya mengunggah dan mendistribusikan
video yang kamu miliki hak atau izinnya; aplikasi tidak menyediakan konten
anime secara otomatis.

## Struktur proyek

- `index.php` — pengarah ke beranda aplikasi.
- `view/index.php` — beranda.
- `view/auth/` — login, signup, dan logout.
- `view/account/` — dashboard, library, dan profil.
- `view/stream/` — katalog anime/movie dan halaman pemutar.
- `view/admin/` — panel admin dan skrip pembuatan admin CLI.
- `view/includes/` — koneksi database dan helper bersama.
- `css/style.css` — palet utama empat warna dan token tema.
- `css/auth.css` — gaya komponen aplikasi.
- `js/theme.js` — kontrol tema; `img/` berisi aset gambar.
- `uploads/` — media unggahan yang tidak disimpan di Git.

## Aset pihak ketiga

Lisensi MIT berlaku untuk kode sumber asli proyek, bukan untuk nama, logo,
karakter, poster, gambar, atau materi pihak ketiga lain yang mungkin ditampilkan.
Materi tersebut tetap menjadi milik pemegang hak masing-masing dan tunduk pada
ketentuan lisensi mereka.
