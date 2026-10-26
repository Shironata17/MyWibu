# MyWibu

MyWibu adalah katalog anime dan film anime dengan akun pengguna, dashboard,
profil, dan library pribadi untuk menyimpan anime serta status tontonan.

## Struktur proyek

- `view/` — halaman PHP, autentikasi, dan koneksi database.
- `css/` — stylesheet.
- `js/` — skrip pencarian.
- `img/` — gambar yang digunakan oleh halaman.

## Menjalankan secara lokal

Jalankan proyek melalui server PHP (misalnya Laragon) dan buka
`http://localhost/MyWibu/view/`. Pastikan MySQL aktif dan database `mywibu_db`
tersedia. Sesuaikan kredensial database di `view/koneksi.php` bila diperlukan.
Tabel akun `users` serta tabel library `anime_library` dibuat otomatis ketika
halaman aplikasi digunakan. Pengguna baru dapat membuat akun dari halaman
signup; setelah berhasil, pengguna langsung diarahkan ke dashboard.

## Hak cipta dan penggunaan

Kode sumber dan materi asli proyek ini © 2026 Hyang Harinata. Hak cipta
dilindungi. Tidak ada lisensi yang diberikan untuk menyalin, mengubah,
menerbitkan ulang, mendistribusikan, atau menggunakan materi asli proyek ini
tanpa izin tertulis terlebih dahulu dari pemegang hak cipta.

Pemberitahuan ini tidak mengklaim kepemilikan atas nama, logo, karakter, poster,
atau aset pihak ketiga yang mungkin ditampilkan. Hak atas materi pihak ketiga
tersebut tetap dimiliki oleh pemegang hak masing-masing dan penggunaannya tunduk
pada ketentuan mereka.

Lihat [LICENSE](./LICENSE) untuk pemberitahuan penggunaan.
