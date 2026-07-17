# Uangku Zero-Knowledge Encryption — Technical Specification

**Status:** Shipped on `main` — this document reflects the current backend contract, not a proposal
**Audience:** Backend (Laravel), Client KMP (Android/iOS), Client Web (Vue JS), Security reviewer

---

## Glosarium (Istilah Teknis untuk Awam)

Dokumen ini penuh istilah kriptografi. Kalau baru pertama kali baca, mulai dari sini dulu sebelum masuk ke bagian teknis.

| Istilah | Penjelasan Awam |
|---|---|
| **Zero-Knowledge (ZK)** | Desain sistem di mana pihak server **secara fisik tidak bisa** membaca data kamu, walau mau — karena kuncinya memang tidak pernah dipegang server. Bukan sekadar janji "kami tidak akan mengintip", tapi memang tidak mungkin. |
| **End-to-End Encryption (E2EE)** | Data dikunci di device kamu, dan hanya bisa dibuka lagi di device tujuan. Semua yang di tengah — termasuk server Uangku — cuma meneruskan data yang terkunci, tanpa bisa membacanya. |
| **Plaintext vs Ciphertext** | *Plaintext* = data asli yang bisa dibaca (mis. "Rp500.000"). *Ciphertext* = versi acak/terkunci dari data itu setelah dienkripsi — kelihatannya seperti karakter acak tak bermakna. |
| **Encryption key (kunci enkripsi)** | Ibarat kunci fisik untuk gembok — deretan data rahasia yang dipakai untuk mengunci (enkripsi) dan membuka (dekripsi) data. |
| **Password vs Secret Key** | *Password* = yang diketik user saat login, seperti biasa. *Secret Key* (`UANGKU-XXXX...`) = kode acak panjang yang digenerate otomatis saat daftar, semacam "kode cadangan" yang **wajib disimpan sendiri** — hilang salah satu dari keduanya, data tidak bisa dibuka lagi. |
| **2SKD (Two-Secret-Key Derivation)** | Proses menggabungkan password + Secret Key jadi satu kunci enkripsi yang sesungguhnya. Analoginya seperti brankas bank yang butuh **dua kunci berbeda** dari dua pihak sebelum bisa dibuka — satu saja tidak cukup. |
| **Salt** | Data acak tambahan yang dicampur ke password sebelum diproses, supaya dua orang dengan password sama tetap menghasilkan kunci yang berbeda. Mencegah penyerang memakai tabel hasil hitung-hitungan siap pakai (*rainbow table*). |
| **PBKDF2 ("password stretching")** | Algoritma yang **sengaja dibuat lambat** saat mengubah password jadi kunci enkripsi (diulang 600.000 kali). Tujuannya: kalau data bocor, penyerang butuh waktu sangat lama untuk mencoba menebak-nebak password. |
| **HKDF** | Cara mengambil satu kunci rahasia dan "mencabangkannya" secara aman jadi beberapa kunci turunan dengan fungsi berbeda-beda, tanpa kunci-kunci itu saling bocor satu sama lain. |
| **AES-256-GCM** | Algoritma enkripsi simetris (kunci yang sama dipakai untuk mengunci maupun membuka) yang juga otomatis mendeteksi kalau ciphertext-nya diutak-atik/dirusak orang lain. |
| **RSA-OAEP** | Algoritma enkripsi asimetris — pakai **sepasang kunci berbeda**: public key untuk mengunci, private key (pasangannya) untuk membuka. |
| **Public key vs Private key** | *Public key* ibarat lubang kotak surat — siapa saja boleh tahu dan memasukkan surat (mengenkripsi data untukmu). *Private key* ibarat kunci rumahmu untuk membuka kotak surat itu dan membaca isinya (dekripsi) — hanya kamu yang boleh punya. |
| **IV (Initialization Vector)** | "Bumbu acak" tambahan yang dipakai setiap kali proses enkripsi berjalan, supaya data yang sama dienkripsi dua kali tetap menghasilkan ciphertext yang kelihatan berbeda. |
| **Bcrypt / hashing** | Proses pengacakan **satu arah** — tidak bisa dibalik ke bentuk asli, hanya bisa dicocokkan ("apakah input ini menghasilkan hash yang sama?"). Dipakai untuk menyimpan bukti login/PIN tanpa menyimpan nilai aslinya. |
| **HMAC** | Cara membuat "sidik jari" dari sebuah data memakai kunci rahasia. Dipakai untuk verifikasi data, atau untuk membuat indeks pencarian tanpa membocorkan data aslinya (lihat *Blind Index*). |
| **Blind Index** | "Sidik jari" dari data terenkripsi (mis. email) yang dipakai server untuk mencari data yang cocok di database — tanpa server pernah tahu isi aslinya. |
| **unlockKey** | Kunci utama yang hanya bisa dihasilkan kalau kamu tahu password **DAN** Secret Key sekaligus. Dipakai membuka private key kamu, dan **tidak pernah** dikirim ke server. |
| **authKey** | "Bukti" turunan dari unlockKey yang dikirim ke server saat login. Server bisa mengecek bukti ini valid atau tidak, tanpa pernah tahu bahan-bahan yang dipakai membuatnya. |
| **wrapped_private_key** | Private key kamu, tapi dalam kondisi terkunci/terenkripsi — aman disimpan di server karena tanpa unlockKey, data ini tidak berguna sama sekali. |
| **Hybrid encryption** | Gabungan dua teknik: enkripsi simetris yang cepat untuk mengunci data sebenarnya, plus enkripsi asimetris untuk mengunci kunci simetris tadi dengan aman. |
| **CSPRNG** | Generator angka acak yang kualitasnya cukup aman untuk kebutuhan kriptografi (bukan "acak" biasa yang bisa ditebak polanya). |
| **Threat model** | Daftar "kita melindungi dari siapa/apa saja" yang jadi dasar keputusan desain keamanan sistem. |
| **User enumeration** | Teknik penyerang mencoba menebak/memastikan email mana saja yang sudah terdaftar di sistem, biasanya lewat respons server yang berbeda-beda. Makanya endpoint `/auth/salt` sengaja memberi jawaban "seolah-olah normal" walau emailnya tidak terdaftar. |
| **Key rotation** | Mengganti kunci enkripsi lama dengan yang baru secara berkala, atau setelah ada indikasi kunci lama bocor. |
| **OTP (One-Time Password)** | Kode sekali pakai (biasanya dikirim ke email) untuk memastikan kamu benar-benar pemilik akun sebelum melakukan aksi sensitif seperti ganti password. |

---

## 1. Tujuan & Prinsip

Uangku bergerak dari model *server-assisted encryption* menuju **Zero-Knowledge (ZK) / End-to-End Encryption (E2EE)**
dengan **dua faktor** ala 1Password:

> Server hanya menyimpan **blob terenkripsi** dan memverifikasi bukti kepemilikan kredensial. Server **tidak pernah**
> memegang password, secret key (`UANGKU-XXXX`), private key plaintext, atau data finansial yang bisa dibaca.

**Ciri khas produk dipertahankan:** setiap akun punya **Secret Key** berformat `UANGKU-XXXXXX-XXXXXX-XXXXX-XXXXX-XXXXX`,
digenerate **di klien**. Data dienkripsi dengan kunci yang hanya bisa diturunkan dari **password DAN secret key
sekaligus** (Two-Secret-Key Derivation / 2SKD) — kehilangan salah satu faktor membuat data tidak bisa dibuka lagi.

**Satu pengecualian eksplisit:** **email** dienkripsi dengan kunci milik sistem agar admin/customer-support dapat
membacanya. Seluruh field lain (nama wallet, saldo, jumlah & catatan transaksi, private key, family key) bersifat ZK
penuh.

### Perbandingan dengan referensi industri

| Aspek                 | Internxt                                | 1Password                        | Uangku (implementasi)            |
|-----------------------|-----------------------------------------|----------------------------------|----------------------------------|
| Lokasi kripto         | Klien                                   | Klien                            | Klien                            |
| Faktor derivasi kunci | Password saja (+ mnemonic device-bound) | **Password + Secret Key (2SKD)** | **Password + Secret Key (2SKD)** |
| Login                 | Challenge-based                         | Challenge-based                  | Challenge-based (`authKey`)      |
| Cipher data           | AES-GCM (AEAD)                          | AES-GCM                          | AES-256-GCM (AEAD)               |
| Sharing               | Wrap ke public key penerima             | —                                | Per-recipient wrap (family)      |
| Metadata              | Minimal di server                       | —                                | Email dikecualikan (by design)   |

---

## 2. Threat Model

**Yang dilindungi:** kompromi database, kompromi server aplikasi, admin/insider yang jahat, snapshot backup, network
sniffing.

**Asumsi:** device klien yang sudah login dianggap tepercaya (kunci ada di memori/keystore device). TLS tetap wajib
untuk transport.

**Kesimpulan:** bila server/DB bocor sepenuhnya, penyerang hanya mendapat: blob terenkripsi, `bcrypt(authKey)` (bukan
authKey itu sendiri, apalagi password/secret key), dan email (yang memang sengaja bisa dibuka sistem). Tanpa **password
DAN secret key** milik user, data finansial tak bisa dibuka — kompromi salah satu faktor saja tidak cukup.

---

## 3. Hierarki Kunci — Two-Secret-Key Derivation (2SKD)

```mermaid
flowchart TD
    PW["Password user"] --> PBKDF2["PBKDF2-HMAC-SHA256(password, salt, 600k iter, 32B)"]
    SALT["Salt acak per-user (16B RAW, disimpan server, dibagikan via /auth/salt)"] --> PBKDF2
    SALT --> HKDFSECRET
    PBKDF2 --> KDFPASS["kdfPass (32B)"]
    SECRET["Secret Key client-generated: UANGKU-XXXXXX-XXXXXX-XXXXX-XXXXX-XXXXX<br/>(TIDAK PERNAH dikirim ke server)"] --> HKDFSECRET["HKDF-SHA256(secretKey, salt=SAME 16B raw salt, info=uangku-secretkey-v1)"]
    HKDFSECRET --> KDFSECRET["kdfSecret (32B)"]
    KDFPASS --> XOR["unlockKey = kdfPass XOR kdfSecret"]
    KDFSECRET --> XOR
    XOR --> UNLOCK["unlockKey — WAJIB password + secret key.<br/>TIDAK PERNAH keluar device."]
    UNLOCK --> HKDFAUTH["HKDF-SHA256(unlockKey, info=uangku-auth-v1)"]
    HKDFAUTH --> AUTHKEY["authKey → dikirim ke server (hanya dibuktikan via bcrypt, tak disimpan mentah)"]
    UNLOCK --> WRAP["AES-256-GCM wrap"]
    KEYPAIR["Keypair RSA-OAEP 4096 (digenerate di klien)"] --> PRIV["private_key"]
    KEYPAIR --> PUB["public_key — plaintext di server (memang publik)"]
    PRIV --> WRAP
    WRAP --> WPRIV["wrapped_private_key → disimpan server"]
    PUB --> DATA["Enkripsi data finansial (hybrid, client-side)"]
    PRIV --> DECRYPT["Dekripsi data finansial (client-side)"]
```

**Ringkas:**

- **`unlockKey`** hanya bisa diturunkan bila punya **password DAN secret key**. Ia membungkus `private_key` dan **tidak
  pernah** keluar dari device.
- **`authKey`** adalah bukti kepemilikan kedua faktor — server menyimpan **`bcrypt(authKey)`** di kolom
  `users.password` (kolom lama, direpurpose; lihat §7). Server tak pernah tahu itu berasal dari password+secretKey,
  hanya tahu ia adalah "sesuatu yang di-bcrypt-check".
- **Tidak ada `seed`/mnemonic terpisah** — secret key itu sendiri sudah berperan sebagai faktor high-entropy kedua;
  keypair digenerate langsung di klien saat registrasi.
- **Kehilangan salah satu faktor = data hilang permanen.** Ini konsisten dengan batasan nyata semua produk E2EE (
  Internxt, 1Password) — server tidak bisa membantu memulihkan kunci yang tidak pernah ia miliki.

---

## 4. Spesifikasi Primitif Kripto

| Fungsi              | Algoritma          | Parameter                                                                       | Dipakai di                                                                                                                |
|---------------------|--------------------|---------------------------------------------------------------------------------|---------------------------------------------------------------------------------------------------------------------------|
| Password stretching | PBKDF2-HMAC-SHA256 | Iterasi per-user (lihat §11), default 600.000 (`EncryptionHelper::PBKDF2_ITERATIONS`), output 32B | Klien                                                                                                    |
| Domain separation   | HKDF-SHA256        | `info` = `uangku-secretkey-v1` / `uangku-auth-v1`, output 32B                   | Klien (+ server: `EncryptionHelper::hkdf()` untuk test vectors)                                                           |
| Enkripsi simetris   | AES-256-GCM        | IV 12 byte acak, tag 16 byte                                                    | Klien (wrap private key, enkripsi data finansial) + Server (`EncryptionHelper::aesGcmEncrypt/Decrypt`, HANYA untuk email) |
| Enkripsi asimetris  | RSA-OAEP-SHA256    | modulus 4096-bit — **plaintext maks. 446 byte** (`512 − 2×32 − 2`); field yang lebih besar (mis. `wrapped_private_key`) WAJIB hybrid envelope §4.1, tidak bisa RSA langsung | Klien saja (server tidak lagi generate/encrypt/decrypt RSA sama sekali)                          |
| Blind index (email) | HMAC-SHA256        | kunci = `MAIN_BLIND_INDEX_KEY`                                                  | Server (`EncryptionHelper::blindIndex()`)                                                                                 |
| Verifier hash       | bcrypt atas HMAC-SHA256(secret, pepper) | pepper = `MAIN_SALT_KEY` (≥ 32 karakter, divalidasi saat boot)    | Server (`EncryptionHelper::hashSecret/validateSecret()` — authKey **dan** PIN, keduanya sama-sama peppered)              |
| Random              | CSPRNG             | —                                                                               | Klien & server (`random_bytes`)                                                                                           |

**Catatan `uangku-enc-v1`:** label ini pernah muncul di draft awal tabel `info` HKDF tapi **tidak
pernah dipakai di kontrak server** — tidak ada endpoint atau flow di §5–§10 yang menyebutnya, dan
tidak ada referensi di `app/`. Kalau klien butuh HKDF terpisah untuk enkripsi database lokal
(never touches the server), itu boleh dipakai **client-only** dengan salt/output yang klien tentukan
sendiri — tapi itu di luar kontrak §4.2 ini, jadi tidak didaftarkan sebagai primitif server-facing.

**Catatan verifier hash:** bcrypt memotong input di 72 byte secara diam-diam. `hashSecret()`/
`validateSecret()` men-HMAC-SHA256 secret dengan pepper dulu (output tetap 44 karakter base64)
sebelum masuk ke bcrypt, supaya truncation itu tidak pernah bisa terjadi — lihat implementasi di
`app/Helpers/EncryptionHelper.php`.

### 4.1 Format ciphertext AES-256-GCM (server-side, email only)

```
┌─────────┬──────────┬───────────────┬──────────┐
│ ver(1B) │ iv (12B) │ ciphertext(N) │ tag(16B) │
└─────────┴──────────┴───────────────┴──────────┘
ver = 0x02  (EncryptionHelper::CIPHER_VERSION_GCM)
```

Base64-encoded as a whole. Klien bebas memilih format containernya sendiri untuk data finansial (server hanya menyimpan
string opaque) — **rekomendasi**: pakai format yang sama (ver‖iv‖ct‖tag) demi konsistensi tooling, plus wrapper hybrid
berikut untuk field besar:

```json
{
    "v": 2,
    "ek": "base64(RSA-OAEP(dataKey))",
    "ct": "base64(ver‖iv‖ciphertext‖tag)"
}
```

### 4.2 Kontrak derivasi kunci (WAJIB identik di semua klien)

```
rawSalt   = base64_decode(salt)   // 16 byte MENTAH — lihat catatan di bawah
kdfPass   = PBKDF2-HMAC-SHA256(NFC(password), rawSalt, iterations, dkLen=32)
kdfSecret = HKDF-SHA256(ikm=secretKey, salt=rawSalt, info="uangku-secretkey-v1", L=32)
unlockKey = kdfPass XOR kdfSecret
authKey   = base64(HKDF-SHA256(ikm=unlockKey, info="uangku-auth-v1", L=32))
```

**HKDF salt = 16 byte MENTAH, bukan base64-nya.** `salt` datang dari `/auth/salt` sebagai base64;
klien harus `base64_decode()` dulu, lalu memakai hasilnya (16 byte raw) sebagai salt untuk **kedua**
operasi — PBKDF2 (kdfPass) dan HKDF (kdfSecret). Ini bukan literal `'user-salt'` — itu adalah salt
per-user yang sesungguhnya, sudah acak dan unik per akun sejak awal. Percobaan pertama kontrak ini
sempat menggunakan literal `'user-salt'` sebagai placeholder, yang namanya ambigu dan pernah memicu
implementasi keliru di seeder (menaruh email di situ, bukan salt) — lihat §15 dan riwayat proyek.
Kontrak final tidak punya string ambigu semacam itu lagi: `salt` selalu berarti 16 byte acak
per-user, titik.

`iterations` didapat dari `POST /auth/salt` — **per-user**, bukan konstanta global (lihat §11). Ini
memungkinkan menaikkan default untuk akun baru tanpa mengubah apa yang dipakai akun lama.

**Referensi implementasi PHP** (satu-satunya tempat langkah-langkah ini boleh ditulis — seeder dan
test WAJIB memanggil fungsi ini, bukan menuliskan ulang langkahnya; lihat
`app/Helpers/EncryptionHelper.php::deriveUnlockKey()`/`deriveAuthKey()`):

```php
$unlockKey = EncryptionHelper::deriveUnlockKey($password, $secretKey, $rawSalt, $iterations);
$authKey   = EncryptionHelper::deriveAuthKey($password, $secretKey, $rawSalt, $iterations);

// Internally:
// $kdfPass   = EncryptionHelper::pbkdf2($password, $rawSalt, $iterations, 32);
// $kdfSecret = EncryptionHelper::hkdf($secretKey, EncryptionHelper::INFO_SECRET_KEY, 32, $rawSalt);
// $unlockKey = $kdfPass ^ $kdfSecret;
// $authKey   = base64_encode(EncryptionHelper::hkdf($unlockKey, EncryptionHelper::INFO_AUTH, 32));
```

---

## 5. Alur Registrasi (Zero-Knowledge)

```mermaid
sequenceDiagram
    participant C as Klien (KMP/Vue)
    participant S as Server (Laravel)
    participant DB as Database
    C ->> S: POST /auth/pre-register { email }
    S ->> C: OTP dikirim via email
    Note over C: Generate SEMUA kunci lokal
    C ->> C: secretKey = generate UANGKU-XXXXXX-...
    C ->> C: salt = random(16)
    C ->> C: unlockKey, authKey = deriveKeys(password, secretKey, salt)
    C ->> C: (pub, priv) = RSA-OAEP-4096 keypair
    C ->> C: wrapped_private_key = AES-GCM(unlockKey, priv)
    C ->> C: tampilkan secretKey SEKALI ke user (simpan/backup — opsional BIP39)
    C ->> S: POST /auth/register { name, email, otp, uuid,<br/>salt, iterations?, auth_key, public_key, wrapped_private_key,<br/>wallet_name?, wallet_amount?, start_date_month? }
    S ->> S: verifikasi OTP
    S ->> S: blind_index = HMAC(email)
    S ->> S: email_ciphertext = AES-GCM(system_key, email)
S->>S: users.password = bcrypt(auth_key)
S->>DB: simpan users + user_keys (blob apa adanya)
S->>C: { id, token, refresh_token }

Note over S: Server TIDAK PERNAH melihat password, secret key,<br/>unlockKey, atau private key plaintext.
```

**Implementasi:** `AuthServiceImplement::register()`, `AuthController::store()`.

---

## 6. Alur Login (Challenge-Based)

```mermaid
sequenceDiagram
    participant C as Klien
    participant S as Server
    participant DB as Database
    C ->> S: POST /auth/salt { email }
    alt email terdaftar
        S ->> DB: cari via blind_index(email)
        S ->> C: { salt (real), iterations (milik user itu) }
    else email tak dikenal
        S ->> S: salt = HMAC(MAIN_BLIND_INDEX_KEY, "decoy-salt:"+blind_index, raw bytes) — deterministik
        S ->> C: { salt (decoy), iterations (default global) }
    end

    C ->> C: unlockKey, authKey = deriveKeys(password, secretKey, salt)
    C ->> S: POST /auth/login { email, auth_key }
    S ->> DB: user = getUserByBlindIndex(email)
    S ->> S: Hash::check(auth_key, user.password)
    alt valid
        S ->> C: { token, refresh_token, public_key, wrapped_private_key }
        C ->> C: priv = AES-GCM-decrypt(unlockKey, wrapped_private_key)
    else invalid
        S ->> C: 400 "Wrong email or credentials!"
    end

    Note over S: Server tak pernah menerima password atau secret key —<br/>hanya authKey (dibuktikan via bcrypt, tak disimpan mentah).
```

**Implementasi:** `AuthServiceImplement::login()` / `getSalt()`, `AuthController::login()` / `salt()`.

**Catatan desain penting:** kolom `users.password` **direpurpose** untuk menyimpan `bcrypt(authKey)`, BUKAN
`bcrypt(password mentah)`. Ini memanfaatkan kembali mekanisme `Hash::check()` Laravel tanpa perlu menulis custom Auth
Provider — secara fungsional identik, karena `authKey` sudah membuktikan kepemilikan *kedua* faktor sebelum pernah
sampai ke server.

---

## 7. Ganti Kredensial & Pemulihan

Karena `authKey` = `f(password, secretKey)`, **mengganti password DAN mengganti secret key adalah operasi yang sama
persis** dari sudut pandang server (keduanya cuma menukar `salt` + `bcrypt(authKey)` + `wrapped_private_key`). Endpoint
terkonsolidasi:

### 7.1 Ganti kredensial (authenticated, tidak kehilangan data)

`POST /auth/pre-change-password` (mulai sesi OTP) → `POST /auth/change-password`:

```
{ old_auth_key, new_salt, new_iterations?, new_auth_key, new_wrapped_private_key, otp, uuid }
```

Klien **masih punya** `unlockKey` lama (sedang login) → bisa dekripsi `private_key` lama & re-wrap dengan `unlockKey`
baru. **Tidak ada data yang hilang.**

### 7.2 Lupa password (`forgot-password` / `reset-password`)

```
{ email, otp, uuid, new_salt, new_iterations?, new_auth_key, new_public_key, new_wrapped_private_key }
```

Karena klien **tidak bisa** membuka `wrapped_private_key` lama tanpa password lama, pemulihan ini **mengganti seluruh
keypair** — data lama yang terenkripsi dengan kunci lama **tidak bisa dibaca lagi**. Ini bukan bug — ini batasan jujur
E2EE yang sama dengan Internxt/1Password: server tidak bisa memulihkan apa yang tidak pernah ia punya kuncinya.

**Implementasi:** `AuthServiceImplement::changeCredentials()` / `resetCredentials()`.

---

## 8. Enkripsi Data Finansial (hybrid AES-GCM, client-side)

```mermaid
flowchart LR
    subgraph Klien["Klien (tulis)"]
        PT["Plaintext: amount / note / wallet name"]
        DK["dataKey = random(32)"]
        PT --> GCM["AES-256-GCM(dataKey, PT)"]
        DK --> GCM
        DK --> WRAP["RSA-OAEP(public_key, dataKey)"]
        GCM --> BLOB
        WRAP --> BLOB["ciphertext blob"]
    end
    BLOB -->|kirim apa adanya| SRV["Server: hanya simpan (text column)"]
    SRV -->|kembalikan apa adanya| BLOB2["ciphertext blob"]
    subgraph Klien2["Klien (baca)"]
        BLOB2 --> UNWRAP["dataKey = RSA-OAEP-decrypt(private_key, ek)"]
        UNWRAP --> DEC["plaintext = AES-256-GCM-decrypt(dataKey, ct)"]
    end
```

Server **tidak melakukan enkripsi/dekripsi apa pun** di sini — `WalletServiceImplement::createWallet()`/`updateWallet()`
menerima `$name`/`$amount` sebagai ciphertext client dan menyimpannya apa adanya. `AuthController::store()` menerima
`wallet_name`/`wallet_amount` opsional (ciphertext) untuk wallet default; bila kosong, klien buat wallet belakangan
lewat `POST /wallet`.

**Envelope wajib untuk SEMUA field finansial, tanpa pengecualian.** Setiap field yang masuk kategori
"data finansial" — `wallet.name`, `wallet.amount`, `transaction.amount`, `transaction.note`, dan
`wallet_name`/`wallet_amount` opsional di `POST /auth/register` — **wajib** memakai hybrid envelope
`{v, ek, ct}` dari §4.1, tidak pernah RSA-OAEP langsung. Alasannya bukan cuma konsistensi: RSA-OAEP-4096
mentok di 446 byte plaintext (§4), dan nama wallet + emoji bisa mendekati batas itu. Aturan "semua field
finansial = envelope, tanpa pengecualian" jauh lebih sulit disalahimplementasikan daripada "envelope
kecuali field X" — klien tidak perlu mengingat daftar pengecualian.

**Bentuk kanonik plaintext numerik (sebelum dienkripsi).** Vue dan mobile harus setuju byte-for-byte
tentang bentuk plaintext sebelum enkripsi, karena hasil dekripsi di klien lain akan di-parse ulang dari
string itu:

- **String integer minor-unit** — tanpa titik/koma pemisah ribuan, tanpa simbol mata uang, tanpa desimal
  terpisah (rupiah tidak punya sen yang dipakai produk ini, jadi minor unit = rupiah itu sendiri).
- Nilai negatif diawali `-` (mis. pengeluaran yang disimpan sebagai negatif, kalau desain klien butuh itu).
- Muat di signed 64-bit integer (`Long`/`bigint`), tidak butuh presisi arbitrary.
- Contoh: Rp150.000,00 → plaintext `"15000000"` bila field-nya minor-unit sen; kalau produk ini
  memang tidak punya konsep sen dan `wallet_amount` adalah rupiah utuh, plaintext-nya `"150000"`.
  **Test vector di `docs/test-vectors/kdf-vectors.json` (Set A) menyertakan contoh literal — pakai itu
  sebagai acuan pasti**, teks di atas hanya menjelaskan aturan bentuknya.
- Field tanggal (`start_date_month`, dsb.) yang dienkripsi: ISO-8601 (`YYYY-MM-DD`), plaintext string,
  envelope yang sama seperti field finansial lain.

**Keterbatasan yang diketahui (didokumentasikan, bukan disembunyikan):**

- **Pencarian transaksi** (`note LIKE '%search%'`) tidak berfungsi atas ciphertext — hanya cocok bila klien
  mengimplementasikan pendekatan lain (mis. tag ter-hash).
- **Deteksi nama wallet duplikat** (`isNameExist`) hanya mendeteksi ciphertext yang byte-identik, bukan plaintext yang
  sama — konsekuensi inheren enkripsi non-deterministik di klien.

---

## 9. Family Sharing (per-recipient) & Rotasi

```mermaid
sequenceDiagram
    participant O as Owner (klien)
    participant S as Server
    participant M as Member baru (klien)
    Note over O: Buat family — keypair digenerate KLIEN
    O ->> O: (famPub, famPriv) = keypair
    O ->> O: wrapped_own = HybridEnvelope(owner.pub, famPriv)
    O ->> S: POST /family { name(ciphertext), public_key, wrapped_private_key }
    S ->> S: family_keys{public_key} + family_member_keys{owner, wrapped_own}
    Note over M: Join family — membership granted, key belum ada (pending)
    M ->> S: POST /family/join { invitation_id, family_id }
    S ->> M: { public_key, key_status: "pending" }
    Note over O: Owner mem-fetch member yang belum punya kunci
    O ->> S: GET /family/{id}/pending-keys
    S ->> O: [{ user_id, public_key }]
    O ->> O: wrapped_for_member = HybridEnvelope(member.pub, famPriv)
    O ->> S: POST /family/{id}/member-key { user_id, wrapped_private_key }
    M ->> S: GET /family/{id}/my-key (poll)
    S ->> M: { public_key, wrapped_private_key, key_status: "granted" }
    Note over O, S: Revoke member
    O ->> S: POST /family/{id}/member/{userId}/revoke
    S ->> S: hapus family_member_keys row milik userId (akses fetch berikutnya diputus)
    Note over O, S: Rotasi penuh (opsional, direkomendasikan setelah revoke)
    O ->> O: (famPub', famPriv') = keypair baru
    O ->> O: re-wrap untuk tiap member tersisa
    O->>S: POST /family/{id}/rotate-key { public_key, member_keys: [...] }
S->>S: family_keys.public_key diganti, SEMUA family_member_keys diganti set baru
Note over M: Eks-member tak bisa lagi fetch kunci baru<br/>(salinan lama di device tetap ada — batasan E2EE inheren)
```

**Skema:** `family_keys(id, family, public_key)` — HANYA public key, tanpa private key/secret key bersama.
`family_member_keys(id, family, users, wrapped_private_key)` — **satu baris per member**, di-wrap ke public key
masing-masing. Tidak ada lagi konsep "family secret key" yang dibagikan manusia-ke-manusia.

**Kenapa `HybridEnvelope`, bukan RSA-OAEP langsung:** `wrapped_own`/`wrapped_for_member` membungkus
`famPriv`, yaitu **private key RSA-4096** dalam bentuk PKCS#8 PEM — sekitar 3.200+ byte. RSA-OAEP-4096
hanya bisa mengenkripsi maksimum 446 byte plaintext (§4) — RSA-OAEP langsung atas private key ini
**mustahil secara matematis** di implementasi manapun, bukan keterbatasan bahasa/library tertentu.
`FamilyServiceImplement` menyimpan `wrapped_private_key` sepenuhnya opaque dan tidak pernah melakukan
RSA sendiri, jadi klien tinggal pakai hybrid envelope §4.1 di sini — strukturnya sama dengan enkripsi
data finansial di §8, jadi helper-nya bisa dipakai ulang.

**Implementasi:** `FamilyServiceImplement` (`createFamily`, `responseInvitation`, `getPendingMembers`, `grantMemberKey`,
`getMyMemberKey`, `rotateKey`, `revokeMember`), `FamilyController`, endpoint di `routes/api.php`.

---

## 10. Pengecualian Email (admin-decryptable)

Email adalah satu-satunya field yang boleh dibuka server (untuk customer support).

- **Blind index** untuk lookup & login: `blind_index = HMAC-SHA256(MAIN_BLIND_INDEX_KEY, normalize(email))` — kolom
  `users.blind_index` (unique, indexed). Deterministik → `WHERE blind_index = ?` cepat, tanpa IV statis pada kolom
  `email`.
- **Nilai tersimpan** untuk dibaca admin: `email = AES-256-GCM(MAIN_SYSTEM_KEY, email)` dengan **IV acak** setiap kali (
  `EncryptionHelper::encryptEmail`/`decryptEmail`) — ini memperbaiki kelemahan lama (static IV → equality leak).
- **Category/sub-category** juga memakai primitif yang sama (`encryptSystem`/`decryptSystem`) untuk field terkait yang
  boleh dibaca admin (lihat `SubCategoriesRelationManager`).

```mermaid
flowchart LR
    E["email"] --> N["normalize (lowercase/trim)"]
    N --> BI["HMAC-SHA256(MAIN_BLIND_INDEX_KEY) → users.blind_index (lookup)"]
    N --> ENC["AES-256-GCM(MAIN_SYSTEM_KEY, IV acak) → users.email (admin baca)"]
```

`MAIN_SYSTEM_KEY` dan `MAIN_BLIND_INDEX_KEY` adalah kunci server yang **berbeda** (domain separation) — lihat
`.env.example`.

---

## 11. API Contract

| Endpoint                                | Request                                                                                                                        | Response                                                                      |
|-----------------------------------------|--------------------------------------------------------------------------------------------------------------------------------|-------------------------------------------------------------------------------|
| `POST /auth/pre-register`               | `{ email }`                                                                                                                    | OTP terkirim                                                                  |
| `POST /auth/register`                   | `{ name, email, otp, uuid, salt, iterations?, auth_key, public_key, wrapped_private_key, wallet_name?, wallet_amount?, start_date_month? }` | `{ id, token, refresh_token }`                                   |
| `POST /auth/salt`                       | `{ email }` (throttle 20/menit)                                                                                                | `{ salt, iterations }` (iterations milik user itu; default global untuk decoy) |
| `POST /auth/login`                      | `{ email, auth_key }` (throttle 10/menit)                                                                                      | `{ id, name, avatar, token, refresh_token, public_key, wrapped_private_key }` |
| `POST /auth/refresh-token`              | `{ refresh_token }`                                                                                                            | token baru                                                                    |
| `POST /auth/pre-change-password`        | (auth)                                                                                                                         | mulai sesi OTP                                                                |
| `POST /auth/change-password`            | `{ old_auth_key, new_salt, new_iterations?, new_auth_key, new_wrapped_private_key, otp, uuid }`                                | ok                                                                            |
| `POST /auth/forgot-password`            | `{ email }`                                                                                                                    | mulai sesi OTP                                                                |
| `POST /auth/reset-password`             | `{ email, otp, uuid, new_salt, new_iterations?, new_auth_key, new_public_key, new_wrapped_private_key }`                       | ok                                                                            |
| `POST /family`                          | `{ name, public_key, wrapped_private_key }`                                                                                    | family + key info                                                             |
| `POST /family/join`                     | `{ invitation_id, family_id }`                                                                                                 | `{ public_key, wrapped_private_key?, key_status }`                            |
| `GET /family/{id}/my-key`               | —                                                                                                                              | `{ public_key, wrapped_private_key?, key_status }`                            |
| `GET /family/{id}/pending-keys` (admin) | —                                                                                                                              | `[{ user_id, public_key }]`                                                   |
| `POST /family/{id}/member-key` (admin)  | `{ user_id, wrapped_private_key }`                                                                                             | ok                                                                            |
| `POST /family/{id}/rotate-key` (admin)  | `{ public_key, member_keys: [{user_id, wrapped_private_key}] }`                                                                | ok                                                                            |
| `POST /wallet`                          | `{ name, amount, family_id? }` (ciphertext)                                                                                    | wallet                                                                        |
| `POST /auth/pin/verify`                 | `{ pin }` (throttle 5/menit)                                                                                                   | ok                                                                            |
| `POST /auth/pin/forgot`                 | `{ auth_key }`                                                                                                                 | mulai sesi OTP                                                                |

Semua field kripto dikirim sebagai string base64 (kecuali `wrapped_private_key`/ciphertext data finansial yang formatnya
bebas ditentukan klien, lihat §4.1).

---

## 12. Tanggung Jawab Server (state final)

| Data                                 | Bentuk di server                                        | Server bisa baca?                            |
|--------------------------------------|---------------------------------------------------------|----------------------------------------------|
| Password                             | — (tak pernah dikirim)                                  | ❌                                            |
| Secret Key (`UANGKU-XXXX`)           | — (tak pernah dikirim)                                  | ❌                                            |
| authKey                              | `bcrypt(authKey)` di `users.password`                   | ❌                                            |
| unlockKey                            | — (tak pernah keluar device)                            | ❌                                            |
| private_key                          | `wrapped_private_key` (AES-GCM client-side)             | ❌                                            |
| public_key                           | plaintext                                               | (publik)                                     |
| wallet/txn/saldo                     | ciphertext hybrid client-side                           | ❌                                            |
| family private key                   | `wrapped_private_key` per-member                        | ❌                                            |
| **email**                            | AES-256-GCM (`MAIN_SYSTEM_KEY`, IV acak) + blind_index  | ✅ **(satu-satunya pengecualian, by design)** |
| category/sub-category (admin-facing) | AES-256-GCM (`MAIN_SYSTEM_KEY`)                         | ✅ (non-finansial, admin-managed)             |
| refresh token                        | SHA-256 hash                                            | ❌                                            |
| PIN                                  | bcrypt (unlock lokal/gating aksi, bukan kunci enkripsi) | ❌ (hash only)                                |

---

## 13. Panduan Implementasi Klien

### 13.1 Kotlin Multiplatform (Android/iOS)

- **KDF/HKDF/HMAC/AES-GCM:** library kripto multiplatform (mis. `cryptography-kotlin`) → JCA di Android,
  CommonCrypto/Security.framework di iOS.
- **RSA-OAEP:** JCA (`RSA/ECB/OAEPWithSHA-256AndMGF1Padding`) / `SecKeyCreateEncryptedData` (`rsaEncryptionOAEPSHA256`).
- **Penyimpanan:** Android Keystore / iOS Keychain untuk cache `unlockKey` (dibuka via PIN/biometrik lokal — server
  tidak terlibat).
- **BIP39** opsional untuk menampilkan secret key sebagai frasa yang mudah dibackup.

### 13.2 Web (Vue JS)

- **WebCrypto (`crypto.subtle`)** untuk semua primitif: `deriveBits` (PBKDF2), HKDF, AES-GCM, RSA-OAEP.
- **600k iterasi PBKDF2** sebaiknya di **Web Worker** agar tidak blocking UI.
- **Penyimpanan:** IndexedDB untuk blob; hindari menyimpan `unlockKey` di storage biasa tanpa proteksi tambahan.

### 13.3 Kesamaan wajib

- Encoding password: **UTF-8 NFC** — WAJIB, semua klien. PHP (`hash_pbkdf2`) **tidak** menormalisasi
  apapun secara internal — byte password masuk apa adanya ke PBKDF2. Kalau satu klien mengirim NFD
  (mis. macOS keyboard untuk `café` menghasilkan `e` + combining acute) dan klien lain mengirim NFC
  (mis. Android keyboard menghasilkan U+00E9), `authKey` yang dihasilkan berbeda dan login gagal
  permanen untuk password non-ASCII — tidak akan pernah muncul di test, hanya kena user asli.
  Mobile (KMP) adalah klien pertama yang mengimplementasikan kontrak ini — normalisasi NFC wajib
  dilakukan di sisi klien sebelum PBKDF2, bukan diasumsikan atau dilakukan server. Test vector
  (`docs/test-vectors/kdf-vectors.json` Set A) menyertakan kasus NFC vs NFD eksplisit untuk
  memverifikasi ini, bukan hanya diasumsikan benar.
- Email normalize: lowercase + trim sebelum blind index (server melakukan ini di `EncryptionHelper::blindIndex`).
- Verifikasi hasil klien terhadap test vectors sebelum integrasi (§4.2) — pastikan `authKey` byte-identik dengan
  referensi PHP.

---

## 14. Catatan Migrasi & File Kritis

Greenfield → migrasi diedit langsung (tanpa dual-read/backward-compat shim).

- `app/Helpers/EncryptionHelper.php` — primitif final (§4).
- `app/Services/Auth/AuthService(Implement).php`, `app/Http/Controllers/Api/AuthController.php` —
  registrasi/login/ganti-kredensial ZK.
- `database/migrations/0001_01_01_000000_create_users_table.php` (+`blind_index`),
  `..._000004_create_user_keys_table.php` (`salt` ganti `hashed_key`),
  `2024_09_22_095203_create_family_keys_table.php` (hanya `public_key`),
  `2026_07_14_090000_create_family_member_keys_table.php` (baru).
- `app/Services/Family/FamilyService(Implement).php`, `app/Http/Controllers/Api/FamilyController.php` — per-recipient
  wrapping & rotasi.
- `app/Services/Wallet/WalletService(Implement).php` — berhenti enkripsi server-side.
- `app/Services/Pin/PinServiceImplement.php` — fix bug OTP-tidak-diverifikasi di `createPin`; `forgotPin` kini pakai
  `authKey`.
- `app/Repositories/UserSession/UserSessionRepositoryImplement.php` — refresh token di-hash SHA-256.
- `app/Http/Resources/BaseResponse.php`, `PaginationResponse.php` — enkripsi respons (`IS_NEED_ENCRYPT`) dihapus total.
- `.env.example` — `MAIN_SYSTEM_KEY`, `MAIN_BLIND_INDEX_KEY` baru; `MAIN_SECRET_KEY`/`MAIN_STATIC_IV` dihapus (
  obsolete).
- **Dihapus (superseded, bukan hilang fitur):** `UserService::preRegenerateSecretKey/generateSecretKey`,
  `OtpService::sendChangeSecretKey`, Console Commands `EncryptAsymmetricCommand`/`GenerateSecretKey` — lihat §7 untuk
  penggantinya.

---

## 15. Checklist Verifikasi

> **Catatan penting soal cara membaca checklist ini:** sebelum review tim Mobile
> (`faq-backend.md`, Juli 2026), item-item di bawah yang bercentang memberi rasa aman yang keliru —
> keduanya membuktikan **self-consistency** (kode konsisten dengan dirinya sendiri), bukan
> **konsistensi dengan kontrak** (kode konsisten dengan §4.2 yang didokumentasikan). Itulah persis
> kelas bug yang menyebabkan Blocker #1: seeder dan test sama-sama hijau selamanya sambil memuat dua
> derivasi yang tidak akan pernah bisa saling login. Checklist di bawah sudah ditandai ulang untuk
> membedakan dua hal itu secara eksplisit.

**Self-consistency (kode konsisten dengan dirinya sendiri — TIDAK membuktikan kontrak dipatuhi):**

- [x] Unit test `EncryptionHelper`: AES-GCM round-trip, tamper detection, PBKDF2/HKDF determinism, blind index, email
  round-trip.
- [x] Test "server tak pernah menerima password/secret key mentah" (`AuthControllerTest`).
- [x] Test dua-faktor: salah password saja ATAU salah secret key saja → login gagal (
  `test_login_fails_when_only_*_factor_is_wrong`) — ini membuktikan derivasi lokal di test konsisten
  dengan dirinya sendiri, **bukan** bahwa derivasi itu sama dengan yang dipakai seeder atau klien nyata.
- [x] `/auth/salt` mengembalikan salt **deterministik** untuk email tak dikenal — determinisme saja,
  **bukan** indistinguishability (lihat baris di bawah untuk properti yang sebenarnya dijanjikan).
- [x] Full suite (`php artisan test`, termasuk test family/wallet yang butuh koneksi DB nyata) dijalankan di CI
  (`.github/workflows/tests.yml`) pada [PR #187](https://github.com/uangkuid/Uangku-BE/pull/187), sudah merged ke
  `main`.
- [ ] `./vendor/bin/pint` bersih di semua file yang disentuh oleh perubahan Blocker #1/Temuan A/Temuan B.

**Properti lintas-implementasi (yang benar-benar dijanjikan kontrak §4.2 & §6 — WAJIB sebelum dianggap selesai):**

- [ ] Seeder dan test suite memanggil **satu** fungsi kanonik (`EncryptionHelper::deriveUnlockKey()`/
  `deriveAuthKey()`) untuk derivasi 2SKD, tidak menuliskan ulang langkahnya masing-masing — Blocker #1
  secara konstruksi tidak bisa terulang kalau ini dipenuhi.
- [ ] Test decoy salt mengecek **indistinguishability** (byte hasil `base64_decode` tidak melulu
  `[0-9a-f]`), bukan hanya determinisme — Temuan A.
- [ ] Test regresi: PIN salah tetap ditolak walau `MAIN_SALT_KEY` panjang (mis. 88 karakter) — Temuan B.
- [ ] Test vectors (`docs/test-vectors/kdf-vectors.json`) di-generate dari `EncryptionHelper` dan
  di-commit ke repo.
- [ ] **Test vectors byte-identik lintas KMP/Vue** — **JANGAN dicentang** sampai ada klien nyata (KMP
  atau Vue) yang benar-benar menjalankan vector itu dan melaporkan hasilnya cocok. Ini satu-satunya
  baris yang benar-benar menangkap kelas bug Blocker #1 — centang manual tanpa validasi klien nyata
  sama saja dengan checklist sebelumnya.
