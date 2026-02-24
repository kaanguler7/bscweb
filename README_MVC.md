# BSC Elektronik - Ninico MVC

Bu proje Ninico template uzerine kurulmus PHP MVC e-ticaret altyapisidir.
Urunler SQL veritabanindan cekilir ve admin panelden gercek CRUD ile yonetilir.
Veri modeli 3NF'e uygun olacak sekilde normalize edilmistir (lookup tablolari + iliski tablolari).

## Kurulan kutuphaneler

- `vlucas/phpdotenv` (ortam/config yonetimi)
- `intervention/image` (urun fotograflarini optimize ederek kaydetme)

## Calistirma (bu makinede portable PHP ile)

1. Veritabanini hazirla:
   `tools/php/php.exe database/init_mysql.php`
2. Sunucuyu baslat:
   `tools/php/php.exe -S localhost:8000`
3. Ac:
   `http://localhost:8000/index.php?route=home`
4. Admin giris:
   `http://localhost:8000/index.php?route=admin/login`
   Varsayilan: `admin / admin123`

## Route'lar

- `index.php?route=home`
- `index.php?route=products`
- `index.php?route=product&id=101`
- `index.php?route=cart`
- `index.php?route=auth/login`
- `index.php?route=auth/forgot-password`
- `index.php?route=admin/products`
- `index.php?route=admin/categories`
- `index.php?route=admin/login`

## Admin CRUD

- Yeni urun ekle (fotograf yukleme dahil)
- Urun duzenle (istersen yeni fotografla degistir)
- Urun sil
- Kategori ekle / guncelle / sil
- Urun eklemede sadece `Urun Adi` zorunludur; marka/model/parca kodu/kategori/stok/fiyat opsiyoneldir.
- Teknik bilgi alanlari (`Parca Turu`, `Parca Numarasi`, `Bilinen Marka/Model`, `TV Panel Modeli`, `TV Panel Markasi`, `Tedarikci Urun Kodu`) SQL'de iliski tablosunda tutulur.

## Siparis ve rapor

- Sepetten `Satin Alan Kisi` ile siparis tamamlama
- Admin sag panelde satin alan kisi, aldigi urunler, tarih-saat
- Bugunku siparis sayisi
- Gunluk tekil ziyaretci sayisi

Fotograflar `storage/uploads/products` klasorune kaydedilir (assets disinda).

## Admin erisim

- Admin alanina site menulerinden gecis verilmez, dogrudan URL ile girilir.
- Admin sayfalari (`admin/products`, `admin/categories`) login zorunludur.
- Giris kontrolu SQL'deki `admin_users` tablosundan yapilir.

## Veritabani

- Bu proje yalnizca MySQL ile calisir.
- `.env` dosyasinda `DB_CONNECTION=mysql` olmali ve MySQL baglanti bilgileri (`DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`) doldurulmalidir.
- Normalize tablo yapisi: `products`, `brands`, `product_models`, `part_codes`, `technical_spec_definitions`, `product_technical_specs`.

## Sifre sifirlama mail ayarlari

- `APP_URL` degerini canli domain veya local adresinizle doldurun (ornek: `http://localhost:8000`).
- Gercek mail gonderimi icin sunucuda `mail()` altyapisi hazir olmali.
- Test amacli mail loglamak isterseniz `.env` icin `MAIL_TRANSPORT=log` kullanabilirsiniz.
- Gonderen bilgisi: `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`.

## MySQL kurulum adimlari

1. MySQL schema kur:
   `tools/php/php.exe database/init_mysql.php`

2. Arama performansi icin MySQL indekslerini ekle:
   `tools/php/php.exe database/add_mysql_indexes.php`

3. Uygulamayi baslat:
   `tools/php/php.exe -S localhost:8000`
