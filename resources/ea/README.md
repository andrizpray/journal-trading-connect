# JTC_Logger — EA Logger untuk MT4/MT5

## Apa ini?
EA Logger yang mengirim data trade history dari terminal MT4/MT5 ke server Journal Trading Connect secara otomatis.

## Cara Install

### MT4

1. **Download file**
   - Download `JTC_Logger.mq4` dari halaman Connect di web

2. **Copy ke folder MT4**
   - Buka MT4
   - Klik **File > Open Data Folder**
   - Buka folder `MQL4 > Indicators`
   - Copy file `JTC_Logger.mq4` ke folder tersebut

3. **Compile (opsional, kalau pakai file .mq4)**
   - Buka MetaEditor (F4 di MT4)
   - Buka file `JTC_Logger.mq4`
   - Klik **Compile** (F7)
   - Pastikan muncul "0 errors, 0 warnings"

4. **Attach ke chart**
   - Di MT4, buka Navigator (Ctrl+N)
   - Cari `Indicators > JTC_Logger`
   - Drag ke chart manapun

5. **Setup parameter**
   - Saat attach, akan muncul dialog parameter:
     - **Server URL**: `http://43.134.37.14:8081` (default, biarkan saja)
     - **API Token**: Paste token dari halaman Connect di web
     - **Kirim data tiap X detik**: `30` (default, biarkan saja)
     - **Maks trade per request**: `50` (default, biarkan saja)
     - **Debug Mode**: `false` (ubah ke `true` kalau mau lihat log detail)

6. **Allow WebRequest (PENTING!)**
   - Klik **Tools > Options**
   - Tab **Expert Advisors**
   - Centang **"Allow WebRequest for listed URL"**
   - Klik tombol **+** (add URL)
   - Tambahkan: `http://43.134.37.14:8081`
   - Klik **OK**

7. **Verifikasi**
   - Cek tab **Experts** di panel bawah MT4
   - Harus muncul:
     ```
     [JTC] Journal Trading Connect - EA Logger
     [JTC] Version: 1.00
     [JTC] Server: http://43.134.37.14:8081
     ```
   - Di chart akan muncul status:
     ```
     === JTC Logger v1.0 ===
     OK | Sent: X trades
     Last sync: 2026.05.03 20:30
     History: X trades
     ```

### MT5

1. **Download file**
   - Download `JTC_Logger.mq5` dari halaman Connect di web

2. **Copy ke folder MT5**
   - Buka MT5
   - Klik **File > Open Data Folder**
   - Buka folder `MQL5 > Indicators`
   - Copy file `JTC_Logger.mq5` ke folder tersebut

3. **Compile**
   - Buka MetaEditor (F4 di MT5)
   - Buka file `JTC_Logger.mq5`
   - Klik **Compile** (F7)

4-7. **Langkah sama seperti MT4** (Attach, Setup, Allow WebRequest, Verify)

## Fitur

- ✅ Auto-sync trade history setiap 30 detik
- ✅ Hanya kirim trade baru (tidak duplikat)
- ✅ Batch request (max 50 trade per kirim)
- ✅ Fallback ke file lokal kalau server offline
- ✅ Status display di chart
- ✅ Debug mode untuk troubleshooting
- ✅ Tidak mengganggu EA trading lain (berjalan sebagai indicator)

## Troubleshooting

| Masalah | Solusi |
|---------|--------|
| "Token kosong" | Paste API Token dari halaman Connect |
| "WebRequest gagal" | Centang Allow WebRequest + tambah URL |
| "GAGAL kirim" | Cek koneksi internet |
| Status "Menunggu history" | Normal, trade belum ada di terminal |
| Trade tidak muncul di web | Cek Experts tab untuk error, cek API Token |

## Keamanan

- Data dikirim via HTTP POST dengan Bearer token
- Token disimpan lokal di parameter indicator
- Tidak menyimpan password MT4/MT5
- Profit dihitung dari profit + swap + commission
