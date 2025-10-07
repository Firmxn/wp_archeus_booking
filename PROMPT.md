<!-- - nama variabel/field belum diatur sehingga masih banyak data yang tersimpan di field "custom_(angka)"
- "customer_email" kosong, padahal ada email yang tersimpan tetapi tersimpan di field "custom_10"
- field yang tersimpan pada database belum disesuaikan dengan "label" yang ada pada booking forms
- saya ingin setiap menambahkan "label" baru pada booking forms, otomatis membuat penamaan variabel/    field, penamaan dengan format "nama_label" namun ditranslate ke bahasa inggris "label_name". misalnya label: Nama Hewan menjadi pet_name   -->



<!-- kenapa penyimpanan data pada tabel tetap tidak mengikuti aturan variabel yang dibuat sebelumnya?
berikut ini adalah isi dari tabel -->
<!-- 
field seperti special_requests padahal tidak ada saya tambahkan pada booking forms namun di tabel mysql tetap ada, dan penamaannya mostly masih custom_(angka) -->


<!-- @wp_archeus_reservasi_puskeswan.sql
diatas adalah isi dari tabel wp_archeus_reservasi_puskeswan

isinya masih tidak sesuai yang saya inginkan, setidak dalam tabel itu harus menyimpan semua field berikut ini (tentunya berdasarkan sistem penamaan variabel yang dibuat sebelumnya dengan mengubah bahasa indonesia ke bahasa inggris, bukan menggunakan custom_N seperti saat ini):

tanggal reservasi
waktu reservasi
jenis servis
status
Nama lengkap : 
Nomor KTP :
Jenis kelamin :
Nomor Telepon 
Tempat tanggal lahir :
Alamat :
Pekerjaan : 
Alamat email :


Jenis Hewan : 
Ras Hewan:
Nama hewan :
Jenis kelamin Hewan: 
Umur Hewan: 
Berat Badan Hewan: 
Jenis Vaksinasi : 
Tanggal Vaksinasi terakhir : 

Bukti Vaksin -->



  <!-- Mau saya ubah label/lokalitas lain di admin agar sepenuhnya Bahasa Indonesia untuk seluruh halaman  
  Bookings? Saya bisa rapikan string yang masih berbahasa Inggris. 


    Ingin saya tambahkan:                                                                               
                                                                                                      
  - Kolom “Siap” pada statistik di atas tabel (sudah saya tambahkan “Siap” dengan hitungan ready).    
  - Opsi pengaturan default status blokir di Calendar agar “Siap” otomatis terpilih bersama           
  “Terkonfirmasi”?                                                                                     -->
                         

<!-- pada archeus booking, di kolom status terdapat selectbox, saya ingin checkbox itu untuk dapat mengganti status secara nyata, setelah admin memilih status baru, maka akan tampil dialog yang berisikan "Yakin untuk mengubah status?" kemudian terdapat tombol batal dan iya. jadi saya ingin menghapus logika lama lain terkait pengubahan status ini.

untuk status completed hanya akan tampil jika status saat ini sudah bernilai confirmed saja

saya ingin dialog konfirmasi muncul sesaat setelah admin memilih status baru, penyimpanan status baru ke database tergantung konfirmasi yang dipilih, jika batal maka tidak jadi menyimpan status baru

dialog konfirmasi tidak muncul saat mengubah status, status langsung berganti beberapa saat kemudian dan disimpan di database -->


  <!-- Ingin saya rapikan juga UI “Booking Flow Management” (misalnya mengganti inline style di 
  section-item jadi kelas kartu modern)? Saya bisa lanjutkan agar konsisten dengan callout 
  baru.

  
 pada halaman dashboard, bagian card: Tampilkan di Sisi Penggunn, ini disebut apa? -->



<!-- pada input berikut, kenapa dalam kondisi invalid? karena menampilkan warna border merah terus, walaupun saat pertama kali saya membuka halamannya

"Nama Formulir 
Masukkan nama deskriptif untuk formulir ini"



selanjutnya checkbox memiliki tinggi dan width yang berbeda, seharusnya checkbox dalam bentuk kotak. kemudian centang dalam checkbox saya ingin warnanya primary bukan warna defaultnya


ada masalah saat menambahkan field "Tambah Field" di klik, input yang muncul ada 2, padahal normalnya yang muncul hanya satu, namun input field yang muncul ada perbedaannya, yaitu yang satu menampilkan kotak value untuk selectbox dan tombol remove. sedangkan yang satu lagi sudah sangat tepat dan juga tombol delete menggunakan ikon. saya ingin jika tipe yang dipilih select baru lah muncul kotak value/opsi yang ingin ditentukan muncul, munculnya juga harus di kolom yang sama dengan tipe, jadi kolom tipe (select) kemudian dibaris selanjutnya field opsi -->






<!-- Pada bagian stat ini, berhasil mengambil data dari tabel yang sesuai dengan Selectbox pilih flow yang dipilih. Jadi, tolong analisis kenapa logika pada tabel tidak dapat menampilkan data dari tabel sedangkan stat ini berhasil. saya memiliki sebuah flow dengan nama "Reservasi Jadwal Operasi" jadi, sudah pasti datanya disimpan dalam tabel wp_archeus_reservasi_jadwal_operasi.

berikut ini card stat yang berhasil mengambil data dari tabel wp_archeus_reservasi_jadwal_operasi
<div class="booking-stats">
                <div class="stat-box default">
                    <h3 id="ab-count-total" class="stat-value">2</h3>
                    <p>Total Pemesanan</p>
                </div>
                <div class="stat-box pending">
                    <h3 id="ab-count-pending" class="stat-value">0</h3>
                    <p>Menunggu</p>
                </div>
                <div class="stat-box success">
                    <h3 id="ab-count-approved" class="stat-value">1</h3>
                    <p>Disetujui</p>
                </div>
                <div class="stat-box done">
                    <h3 id="ab-count-completed" class="stat-value">0</h3>
                    <p>Selesai</p>
                </div>
                <div class="stat-box danger">
                    <h3 id="ab-count-rejected" class="stat-value">1</h3>
                    <p>Ditolak</p>
                </div>
            </div> -->

<!-- saya ingat pada saat halaman archeus booking/dashboard pertama kali dibuka, ada sebuah kondisi yang mengambil data berdasarkan value yang dipilih pada selectbox "pilih flow", dengan syarat id paling kecil dan juga data yang tidak kosong. Jadi, saat pertama kali halaman dibuka akan menampilkan data sesuai kondisi tersebut, dan jika mengubah value pilih flow barulah akan menampilkan data berbeda dari tabel lain  -->



baik, sekarang saya ingin anda fokus ke halaman 
dashboard panel admin dulu. jika bisa jangan ke hal 
lain yang tidak berkaitan.



saya akan memberikan alur singkat bagaimana seharusnya yang terjadi saat admin memperbarui status di halaman dashboard, 1. admin mengubah status, 2. menampilkan dialog konfirmasi(seharusnya saat ini menggunakan dialog kustom), 3. menampilkan loading overlay, 4. saat loading masih terjadi, menyimpan data perubahan status ke database, 4. menampilkan halaman dashboard yang baru direfresh agar menampilkan data yang terbaru, 5. mengirimkan notifikasi via email yang sudah berhasil(tolong jangan diubah logikanya, karena sudah berhasil)