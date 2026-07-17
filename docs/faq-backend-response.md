# Balasan Backend untuk FAQ Tim Mobile — Kontrak Zero-Knowledge

**Dari:** tim Backend (Uangku-BE)
**Untuk:** tim Mobile (Uangku-Mobile-Core-Lib, KMP Android/iOS)
**Tentang:** `faq-backend.md` (16 Juli 2026)
**Status:** Semua item di bawah sudah diimplementasikan di branch `fix/2skd-contract-mobile-review`
(belum merge ke `main` saat dokumen ini ditulis — lihat "Yang masih pending" di akhir).

Terima kasih atas review-nya — sangat detail dan setiap temuan valid. Kami cek satu per satu langsung
ke source dan (untuk yang berkaitan dengan perilaku runtime) reproduksi secara empiris sebelum
memutuskan fix-nya. Ringkasnya: **semua yang kalian laporkan benar, tidak ada yang salah baca.**

---

## 🔴 BLOCKER #1 — SELESAI. Kanonik: per-user salt 16 byte

**Keputusan:** Opsi #1 dari rekomendasi kalian — **per-user `salt` (16 byte) dipakai sebagai HKDF
salt untuk `kdfSecret`**, bukan literal `'user-salt'`.

```
rawSalt   = base64_decode(salt)   // 16 byte MENTAH
kdfPass   = PBKDF2-HMAC-SHA256(NFC(password), rawSalt, iterations, dkLen=32)
kdfSecret = HKDF-SHA256(ikm=secretKey, salt=rawSalt, info="uangku-secretkey-v1", L=32)
unlockKey = kdfPass XOR kdfSecret
authKey   = base64(HKDF-SHA256(ikm=unlockKey, info="uangku-auth-v1", L=32))
```

**Poin kritis yang wajib kalian tahu sebelum implementasi:** HKDF salt untuk `kdfSecret` memakai
**16 byte MENTAH** (hasil `base64_decode(salt)`), sama persis dengan yang dipakai sebagai PBKDF2
salt — **bukan** string base64-nya. Kalau kalian pass string base64 ke HKDF, hasilnya akan berbeda
dari server dan login gagal permanen. Ini detail yang paling gampang salah baca dari dokumen lama,
jadi kami tandai eksplisit di §4.2.

**Jawaban Pertanyaan 1** ("mana yang kanonik, dan apa maksud asli `'user-salt'`?"): `'user-salt'`
memang literal, bukan placeholder yang sengaja — tapi namanya **menyesatkan**, dan itu hampir pasti
alasan kenapa seeder lama menaruh email di situ (seseorang membaca "user-salt" sebagai "salt milik
user" alih-alih literal string). Kami tidak ingin mempertahankan string ambigu semacam itu di
kontrak final, jadi pindah ke salt per-user yang sesungguhnya — namanya sekarang benar-benar berarti
apa yang tertulis.

**Root cause yang kami perbaiki, bukan cuma gejalanya:** masalahnya bukan salt yang salah, tapi
derivasi ditulis manual di 4 tempat (doc, seeder, 2 test) tanpa saling mengecek. Kami tambahkan satu
fungsi kanonik:

```php
// app/Helpers/EncryptionHelper.php
EncryptionHelper::deriveUnlockKey(string $password, string $secretKey, string $rawSalt, int $iterations = 600000): string
EncryptionHelper::deriveAuthKey(string $password, string $secretKey, string $rawSalt, int $iterations = 600000): string
```

`database/seeders/UserSeeder.php` dan seluruh test suite sekarang **memanggil** fungsi ini, bukan
menulis ulang langkahnya — jadi "seeder menyimpang dari test" sudah mustahil secara konstruksi, bukan
cuma diperbaiki sekali lalu bisa menyimpang lagi nanti.

⚠️ **Konsekuensi:** derivasi berubah dibanding dokumen lama. Kalau kalian sudah mulai coding
berdasarkan `'user-salt'` literal, itu harus diganti ke per-user salt sebelum test vector baru rilis.

---

## 🟠 TEMUAN KEAMANAN — keduanya dikonfirmasi & diperbaiki

### Temuan A — Decoy salt `/auth/salt` sekarang indistinguishable

Dikonfirmasi persis seperti laporan kalian: `hash_hmac()` tanpa arg ke-4 `$binary=true` mengembalikan
hex ASCII, jadi decoy selalu decode ke byte `[0-9a-f]` saja. Kami ukur distinguisher-nya sebelum fix:
**100% akurat, 0 false-positive dari 200.000 sampel salt asli** — persis argumen kalian, bukan
melebih-lebihkan.

**Fix** (`app/Services/Auth/AuthServiceImplement.php`, method `getSalt()`): tambah `true` di arg
ke-4 `hash_hmac()`, plus dua bug kecil yang ikut kami perbaiki di method yang sama — `$salt` yang
dibaca sebelum di-assign (undefined variable warning di PHP 8) dan `env('MAIN_BLIND_INDEX_KEY', '')`
yang sekarang konsisten dengan `blindIndex()` (throw kalau key kosong, bukan diam-diam pakai string
kosong).

Test baru menegaskan **properti yang sebenarnya dijanjikan** (indistinguishability), bukan cuma
determinisme seperti test lama — persis saran kalian.

### Temuan B — `hashSecret()` sekarang aman terhadap pepper sepanjang apapun

Dikonfirmasi & direproduksi persis: kami test P=72 dengan PIN salah **dan PIN kosong**, keduanya
tervalidasi (bypass total). Tabel P≤66/67–71/≥72 kalian tepat — kami verifikasi ulang boundary-nya
byte per byte.

**Satu koreksi terhadap laporan kalian (bukan bantahan, cuma konteks tambahan):** ini bukan exploit
aktif di deployment kami saat ini — `.env` produksi kami pakai `MAIN_SALT_KEY` 32 karakter (aman),
dan ada command `php artisan uangku:generate-secrets` yang selalu generate 32 karakter. Tapi kalian
benar bahwa tidak ada yang **mencegah** seseorang mengisi manual dengan `openssl rand -base64 64`
(88 char, kelihatan "lebih aman", justru mematikan PIN total) — jadi tetap kami perbaiki sebagai
ranjau laten, bukan kami abaikan.

**Fix** (`app/Helpers/EncryptionHelper.php`): `hashSecret()`/`validateSecret()` sekarang HMAC-SHA256
pre-hash secret dengan pepper dulu (`base64_encode(hash_hmac('sha256', $secret, $pepper, true))`)
sebelum masuk bcrypt — input bcrypt jadi selalu 44 karakter, truncation 72-byte mustahil terjadi lagi
berapapun panjang pepper-nya. Pola `$pepper.$secret.$pepper` (trailing pepper yang tidak menambah
keamanan, seperti kalian catat) sudah dibuang.

Kami juga tambah guard saat boot aplikasi: `MAIN_SALT_KEY`/`MAIN_SYSTEM_KEY`/`MAIN_BLIND_INDEX_KEY`
kalau **diisi** (bukan kosong) wajib ≥ 32 karakter, atau app menolak boot. Catatan teknis: guard ini
sengaja tidak menolak key yang *kosong* (hanya yang diisi-tapi-pendek), supaya tidak merusak urutan
CI kami yang menjalankan migrasi sebelum secrets di-generate — di luar konteks kalian, tapi
disebutkan untuk lengkap.

**Sekalian menjawab "pertanyaan bonus" kalian** (apakah authKey sengaja tidak di-pepper): tidak
sengaja — itu memang bug dokumentasi. Sekarang authKey **ikut** dipeppered lewat `hashSecret()` yang
sama dengan PIN (pakai HMAC pre-hash di atas, jadi aman meski di-pepper). §4 di `docs/encryption.md`
sudah kami perbaiki supaya cocok dengan kode, bukan sebaliknya.

---

## 🟡 PERTANYAAN — jawaban

**Pertanyaan 2 (NFC)** — jawaban berubah dari yang kalian duga, dalam artian baik: **`uangku-web`
(klien Vue) ternyata scaffold `npm create vue` yang belum disentuh sama sekali** — nol kode kripto,
tanpa HTTP client, bukan git repo. **Kalian satu-satunya klien yang sedang jalan.** Jadi tidak ada
kompatibilitas yang perlu direkonsiliasi — NFC adalah keputusan bebas kalian tetapkan sebagai standar.
Rekomendasi kami: **tetap NFC** sesuai §13.3 (kami konfirmasi PHP `hash_pbkdf2()` tidak menormalisasi
apapun — byte NFC vs NFD menghasilkan `authKey` berbeda, seperti dugaan kalian), dan pastikan test
vector NFC/NFD kalian jadi acuan tunggal untuk web nanti.

**Pertanyaan 3 (`uangku-enc-v1`)** — jawaban (c): **dead**, sisa draft. Nol referensi di `app/`. Kami
hapus dari tabel kontrak server di §4, dan catat sebagai *reserved untuk client-side saja* (enkripsi
DB lokal, tidak pernah menyentuh server) kalau kalian mau pakai — tapi itu di luar kontrak §4.2 ini.
Silakan buang `deriveEncryptionKey()` dari API kalian kalau itu yang kalian rencanakan.

**Pertanyaan 4 (serialisasi `wallet_amount`)** — bentuk kanonik: **string integer minor-unit, tanpa
pemisah ribuan, tanpa simbol mata uang, prefix `-` untuk negatif, muat di signed 64-bit**. Kolom
`amount` sudah `text()` di semua migrasi jadi tidak ada batasan panjang dari sisi DB. Contoh literal
ada di §8 `docs/encryption.md` dan akan disertakan sebagai test vector (`envelope.plaintext` di Set E,
lihat bagian test vector di bawah). Field tanggal seperti `start_date_month`: ISO-8601 (`YYYY-MM-DD`),
envelope yang sama.

**Pertanyaan 5 (register hybrid envelope vs RSA langsung)** — jawaban: **hybrid envelope, selalu,
untuk semua field finansial tanpa pengecualian**, termasuk `wallet_name`/`wallet_amount` opsional di
`POST /auth/register`. Setuju dengan alasan kalian — aturan tanpa pengecualian jauh lebih sulit
disalahimplementasikan.

**Pertanyaan 6 (`iterations` per-user)** — **diterima seluruhnya**, sudah diimplementasikan:
- Kolom baru `user_keys.iterations` (default 600.000).
- `POST /auth/register` terima `iterations` opsional; `change-password`/`reset-password` terima
  `new_iterations` opsional. Semua default ke konstanta server kalau diomit.
- `/auth/salt` sekarang mengembalikan **iterations milik user itu**, bukan konstanta global. Decoy
  tetap mengembalikan default global (supaya iterations custom tidak jadi oracle enumerasi baru —
  ini pertimbangan tambahan dari kami, di luar yang kalian minta, tapi konsisten dengan semangat
  Temuan A).

Karena desain kalian sudah memperlakukan `iterations` sebagai parameter runtime dari `/auth/salt`,
seharusnya tidak perlu perubahan di sisi kalian sama sekali.

---

## 🔵 KOREKSI DOKUMENTASI — semua diterapkan

1. **§9 `RSA-OAEP(pub, famPriv)` mustahil** — dikonfirmasi & direproduksi: PEM PKCS#8 RSA-4096 kami
   ukur **3.272 byte**, limit RSA-OAEP-4096 **446 byte**. `openssl` menolak dengan `data too large for
   key size`, persis prediksi kalian. §9 sekarang pakai `HybridEnvelope(owner.pub, famPriv)` /
   `HybridEnvelope(member.pub, famPriv)`, dan §4 sekarang punya catatan limit 446 byte eksplisit.
2. **§4.2 + §4 kontradiksi `'user-salt'`** — selesai, lihat Blocker #1 di atas. Doc + seeder + test
   sekarang identik karena semuanya memanggil fungsi kanonik yang sama.
3. **§4 tabel verifier hash salah** — dikonfirmasi (authKey memang pakai `Hash::make()` polos, tanpa
   pepper). Sekarang **benar** karena authKey ikut di-pepper (lihat Temuan B) — tabelnya diupdate
   supaya cocok dengan kode, bukan diperbaiki teksnya saja.
4. **§15 checklist rasa aman palsu** — setuju sepenuhnya, ini poin paling penting di seluruh dokumen
   kalian. Checklist sekarang dipisah eksplisit jadi dua bagian: **"self-consistency"** (kode
   konsisten dengan dirinya sendiri — tidak membuktikan apapun soal kontrak) vs **"properti
   lintas-implementasi"** (yang benar-benar dijanjikan §4.2/§6). Baris "Test vectors byte-identik
   lintas KMP/Vue" **sengaja kami biarkan un-checked** — sesuai permintaan kalian, tidak akan kami
   centang sampai ada klien nyata yang benar-benar memvalidasinya.
5. **§11 `iterations` per-user** — selesai, lihat Pertanyaan 6.

---

## 📋 Test Vector

Command baru: `php artisan uangku:kdf-vectors` — dump ke `docs/test-vectors/kdf-vectors.json`, wajib
memanggil `EncryptionHelper::deriveAuthKey()`/`deriveUnlockKey()` (fungsi kanonik yang sama dipakai
seeder), tidak menulis ulang langkahnya.

Semua set sesuai permintaan kalian, termasuk nilai antara:

- **Set A** (2SKD) — 5 kasus: ASCII baseline, café NFC, café NFD (dua-duanya, byte persis berbeda —
  kami sudah cek manual, `authKey` NFC ≠ NFD karena PHP tidak normalize, persis dugaan kalian), emoji
  + spasi depan/belakang, password 200 karakter. Tiap kasus punya `password_hex`, `hkdf_ikm_hex`,
  `hkdf_salt_hex`, `kdf_pass_hex`, `kdf_secret_hex`, `unlock_key_hex`, `auth_key`.
- **Set B** (isolasi HKDF salt) — `ikm_hex` tetap, 4 varian salt (diomit/`''`/`'user-salt'`/email) →
  4 `okm_hex`. Mengonfirmasi `hash_hkdf(salt='')` ≡ RFC 5869 `zeros(HashLen)`.
- **Set C** (AES-GCM IV tetap) — `key_hex`, `iv_hex`, `plaintext_hex`, `container_b64`.
- **Set D** (RSA dari dev DB) — `public_key_b64` + `wrapped_private_key` asli dari `user_keys` kalau
  ada row ter-seed; fallback ke keypair sintetik kalau belum (lihat "Yang masih pending").
- **Set E** (hybrid envelope) — `plaintext` (`"15000000"`, contoh literal untuk Pertanyaan 4),
  `data_key_hex`, `ct`, keypair RSA lengkap.

**Satu penyesuaian jujur di Set E:** field `envelope.ek` kami **kosongkan**, bukan diisi. PHP's
`openssl_public_encrypt()` OAEP cuma bisa SHA-1, bukan SHA-256 yang jadi kontrak §4 — mengisi `ek`
dengan hasil SHA-1-OAEP tapi melabelinya seolah SHA-256 lebih berbahaya daripada mengosongkannya
(server memang sengaja tidak pernah punya kapabilitas RSA-OAEP-SHA256, sesuai §4: "Klien saja").
`data_key_hex` + `ct` + keypair tetap kami sediakan supaya kalian bisa cross-check dua arah:
RSA-OAEP-SHA256-encrypt `data_key_hex` pakai `public_key_b64` dengan implementasi kalian sendiri,
lalu decrypt lagi dengan `private_key_pem_b64` dan pastikan hasilnya sama.

⚠️ **`docs/test-vectors/kdf-vectors.json` belum kami commit ke repo** — command-nya sudah jalan dan
kami verifikasi manual outputnya benar, tapi Set D butuh database dev yang benar-benar ter-seed
supaya "dari dev DB, bukan dikarang" sesuai permintaan kalian (bukan keypair sintetik). Kami akan
generate & commit begitu ini di-merge dan dijalankan di environment dengan DB nyata — akan kami
kabari begitu file itu ada.

---

## 💡 Usulan proses — diterima

Satu implementasi referensi, satu file vector — persis yang kami lakukan lewat
`EncryptionHelper::deriveUnlockKey()`/`deriveAuthKey()` + `uangku:kdf-vectors`. Diagnosis kalian tepat:
yang menyebabkan Blocker #1 bukan kecerobohan, tapi derivasi ditulis 3 kali tanpa saling mengecek.
Sekarang itu jadi satu tempat, dan begitu ada klien nyata memvalidasi test vector, "byte-identical
lintas klien" jadi properti yang **di-test**, bukan diverifikasi manual sekali lalu perlahan
menyimpang.

---

## Lampiran A — dikonfirmasi, tidak ada yang salah baca

Semua 12 kesimpulan kalian di Lampiran A **benar**. Dua catatan tambahan yang kalian sebutkan juga
kami konfirmasi: `iterations=1000` di test memang cuma untuk kecepatan (test vector production tetap
600.000), dan throttle `/auth/salt` 20/menit + `/auth/login` 10/menit sudah sesuai doc.

---

## Yang masih pending di sisi kami

- Branch `fix/2skd-contract-mobile-review` belum di-merge ke `main`.
- `php artisan test` penuh belum dijalankan di environment dengan MySQL+Redis nyata sejak perubahan
  ini — akan dijalankan sebelum merge.
- `docs/test-vectors/kdf-vectors.json` belum di-commit (lihat catatan Set E di atas) — menyusul
  begitu dijalankan di environment dengan DB ter-seed.

Kami akan update kalian begitu branch ini merge dan test vector final ter-commit — mohon jangan mulai
menulis kode berdasarkan derivasi `'user-salt'` lama, tunggu konfirmasi merge ini.
