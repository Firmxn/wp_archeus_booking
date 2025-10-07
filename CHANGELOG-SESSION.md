# Catatan Perubahan

## 03 Oktober 2025 08:34 WIB

Lanjutan Pekerjaan
- Booking Flow: ganti submit dari `$.post` ke `$.ajax` dengan `dataType: 'text'` dan parsing JSON manual; tambahkan logging error detail dan fallback pesan ramah pengguna.
- Admin: perbarui seluruh panggilan AJAX (update status, hapus booking, filter/refresh daftar) untuk menggunakan `dataType: 'text'` + parsing JSON manual; tambahkan pesan error yang lebih jelas saat respons tidak valid.
- Catatan: tidak ada perubahan skema/DB. Perilaku penyimpanan per-flow tetap sama seperti sesi sebelumnya.

Penghapusan Tabel Utama wp_archeus_bookings
- Seluruh logika yang mengarah ke tabel utama dihapus/dikonversi ke agregasi per-flow:
  - `includes/class-booking-database.php`: tidak lagi membuat tabel utama; metode `insert_booking`, `get_bookings`, `get_booking`, `update_booking_status`, `delete_booking`, `get_booking_count_by_status`, dan `cleanup_old_bookings` kini bekerja di tabel per-flow.
  - `includes/class-booking-calendar.php`: referensi properti tabel utama dihapus.
  - `public/class-booking-public.php`: `get_booked_dates()` kini mengagregasi dari tabel per-flow; handler submit non-flow menyimpan ke per-flow default (tanpa menyentuh tabel utama).

Booking Forms: Opsi Select Kustom
- Admin Forms kini mendukung pengaturan nilai untuk field bertipe Select (satu nilai per baris).
- UI: tambah kolom "Pilihan (untuk Select)" pada tabel field di halaman Forms; saat tipe bukan Select kolom disembunyikan otomatis.
- Rendering: jika field memiliki `options`, opsi akan ditampilkan di frontend dan di langkah Form pada Booking Flow (kecuali `service_type` yang tetap mengambil dari layanan aktif).

Penamaan Field Otomatis + Pemetaan Data
- Admin Forms: saat menyimpan field baru dengan kunci sementara `custom_<angka>`, sistem otomatis membuat nama field snake_case berbahasa Inggris dari label (contoh: "Nama Hewan" -> `pet_name`) dan memastikan unik.
- Server submit (flow): jika `customer_email`/`customer_name` belum terisi, sistem akan mencari dari definisi form (tipe `email`/label mengandung "nama"/"name") atau deteksi pola email di data, sehingga email tidak lagi tersimpan di `custom_xx` dan kolom `customer_email` terisi.

## 02 Oktober 2025 00:00 WIB

Ringkasan
- UI frontend dipulihkan ke kondisi stabil (sebelum konsolidasi CSS), dan navigasi bulan pada kalender Booking Flow diperbaiki tanpa mengganggu tampilan.
 - Kalender Booking Flow sekarang menonaktifkan interaksi sementara saat memuat bulan (loading state), seragam dengan kalender admin.

Perubahan Frontend (Booking Flow)
- Kalender Booking Flow: ganti pemanggilan `$.post` menjadi `$.ajax` dengan `dataType: 'text'` lalu parse JSON secara manual (mengatasi respons yang mengandung karakter sebelum JSON).
- Update header bulan secara spesifik: `.booking-calendar-header .current-month`.
- Perbarui atribut tombol prev/next menggunakan `.attr('data-month')` dan `.attr('data-year')` agar navigasi berantai akurat.
- File terkait: `includes/class-booking-shortcodes.php` (fungsi `loadCalendar`).
 - Tambah loading state: `$('.booking-calendar').addClass('loading')` saat request dan `removeClass('loading')` pada sukses/error agar kalender non‑interaktif sementara memuat data (didukung CSS di `assets/css/calendar.css`).

Perubahan Admin (Kalender)
- Perbaiki selector rusak saat update header bulan dan gunakan scoping selector yang benar.
- AJAX admin: tambahkan `dataType: 'text'` + parsing manual dan logging error detail.
- Handler AJAX admin: gunakan `wp_send_json_error(...)` untuk kesalahan nonce/izin agar respons selalu berupa JSON.
- File terkait: `admin/class-booking-admin.php`.

CSS (Pemulihan Urutan Publik)
- Kembalikan enqueue CSS publik eksplisit (tanpa `@import`):
  - `assets/css/public.css`, `assets/css/calendar.css`, `assets/css/booking-flow.css`, `assets/css/services.css`, `assets/css/time-slots.css`.
- Hapus `@import` di `public.css` agar urutan kaskade sesuai sebelum konsolidasi.
- File terkait: `public/class-booking-public.php`, `assets/css/public.css`.

CSS Admin (Konsolidasi)
- Style admin digabung ke `assets/css/admin.css` dan hentikan pemanggilan CSS admin yang terpisah.
- File lama yang dihapus: `assets/css/admin-calendar.css`, `assets/css/admin-settings.css`, `assets/css/admin-services.css`.

Terjemahan (Admin)
- Terjemahkan label/pesan kalender admin: “Atur Ketersediaan Tanggal”, “Status Ketersediaan”, “Batas Harian”, “Perbarui Tanggal”, “Pengaturan Tampilan Kalender”, dll.

Penyimpanan Per‑Flow (Hanya tabel per‑flow)
- Tambah penyimpanan hanya ke tabel per‑flow bernama `wp_archeus_<flow_name_sanitized>`; tabel utama tidak digunakan lagi untuk submit booking flow.
- Simpan seluruh input/opsi pengguna sebagai kolom terpisah (AUTO ALTER TABLE jika kolom belum ada), serta simpan path file upload.
- Tetap menaikkan counter jadwal via `update_schedule_bookings` agar pembatasan kapasitas slot berjalan.
- Perubahan di: `includes/class-booking-database.php` (fungsi baru: `get_flow_table_name`, `ensure_flow_table`, `sanitize_column_name`, `ensure_flow_table_columns`, `insert_flow_submission`) dan `public/class-booking-public.php` (hanya insert per‑flow + email, tanpa insert ke tabel utama).

Agregasi Kalender dari Tabel Per‑Flow
- Ubah perhitungan `booked_count` kalender agar membaca dari seluruh tabel per‑flow, bukan dari tabel utama.
- Perubahan di: `includes/class-booking-calendar.php` (`get_booking_counts_by_date`, `get_availability_with_bookings`).

Catatan
- Jika diperlukan, kita dapat menonaktifkan parsing manual di Booking Flow dan Admin bila sumber respons sudah dijamin JSON murni (tidak ada output lain sebelum JSON).

## 03 Oktober 2025 10:13 WIB

Pembersihan Kompatibilitas Lama + Peningkatan Flow
- Hilangkan jalur submit/form klasik: hapus hook `submit_booking` dan `get_booked_dates`; legacy handler diblokir (tidak aktif).
- Hapus skrip legacy: `assets/js/public.js` dan enqueuing jQuery UI Datepicker dari frontend.
- AJAX URL dibuat aman (HTTPS/same-origin/relatif) untuk hindari mixed-content dan CORS.
- Toleransi error submit: jika callback error terpanggil namun HTTP 2xx/JSON sukses, diperlakukan sebagai sukses (hindari alert palsu).
- Pesan sukses diubah ke Bahasa Indonesia dan menambahkan instruksi cek email.
- Rehydrate form: isi ulang otomatis field dari `archeus_form_data` saat halaman dimuat.
- Time slots: tidak hardcoded; dimuat via `get_available_time_slots` + `nonce` dan menandai jika slot tersimpan tidak lagi tersedia.

Field & Data
- `special_requests` bukan field default lagi; hanya digunakan jika Anda menambahkannya di Booking Forms. Email template membaca `{special_requests}` dari `additional_fields` bila ada.
- Reserved fields dipangkas ke: `customer_name`, `customer_email`, `booking_date`, `booking_time`, `service_type`, `time_slot`.
- Penghapusan file cadangan: `admin/class-booking-admin.php+.bak`, `includes/class-booking-shortcodes.php.broken-backup`, dan dump SQL contoh.

Migrasi Tabel Per-Flow (Termasuk `wp_archeus_reservasi_puskeswan`)
- Tambah alat migrasi di Admin: menu `Migration` untuk menjalankan migrasi tabel per-flow.
- Migrasi memindahkan kolom legacy (`custom_*`, `name`, `date`, `special_requests`) ke `additional_fields`, mengisi `customer_email` jika kosong, lalu menghapus kolom legacy.
- Implementasi: `Booking_Database::migrate_existing_flow_table($flow_name)` dan halaman `migration_page()` di Admin.
