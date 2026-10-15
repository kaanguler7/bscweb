# SEO ve mobil yayın özeti — 15 Ekim 2026

## Ürün verisi

- 56 boş ürün açıklaması, satıcının mevcut ilan başlığı, marka, kategori ve parça kodundan üretildi. Böylece 57 aktif ürünün tamamında görünür açıklama var.
- 56 ürünün teknik tablosuna ilan kaydındaki parça türü ve ana parça kodu eklendi. Daha önce doldurulmuş 17PW26-4 ürünü değiştirilmedi.
- Dört bariz ana kod düzeltildi: 17MB82S (ürün 28), 17IPS12 (34), WCU507R-1 (49), BN44-00472A (53). Son iki düzeltme ürün fotoğrafındaki baskıyla da karşılaştırıldı.
- Kondisyon, test sonucu, kart revizyonu ve TV uyumluluğu için bağımsız stok/etiket kaydı verilmedi. Bu alanlara doğrulanmamış değer eklenmedi. Bazı mevcut ilan başlıkları birden fazla TV/panel kodu içeriyor; bunlar doğrulanmış uyumluluk olarak sunulmamalı.

Veritabanı değişiklikleri `database/migrations/20261015_fill_catalog_facts.sql` dosyasındadır. Canlı içe aktarma 179 sorguyla başarılı oldu.

## Mobil ve kod temizliği

- 57 ürün fotoğrafının 720 piksel WebP liste sürümü oluşturuldu. Toplam görsel dosyası boyutu 7,61 MB özgün fotoğraftan 2,28 MB küçük sürüme indi (%70 azaltma). Ürün detayında özgün fotoğraf korunur.
- Ana sayfa, ürün listesi ve favoriler küçük sürümleri kullanır. Yeni yüklemelerde küçük sürüm otomatik oluşturulur; eski fotoğraf silinince ilgili küçük sürüm de silinir.
- Font Awesome için yalnızca WOFF2 dosyaları bırakıldı. 20 eski font dosyası ve sayfalarda yüklenmeyen 21 eski tema CSS/JS dosyası kaynak depodan çıkarıldı. `phpbb`, `cgi-bin` ve eski uygulama dizinlerine dokunulmadı.
- Aynı 41 eski dosya canlı sunucuda DirectAdmin'in kurtarılabilir çöp kutusuna taşındı; geçici dağıtım ZIP dosyası da kaldırıldı. Canlı ana sayfa, ürün listesi ve ürün detayındaki bağlantılı CSS, JS ve görseller tekrar kontrol edildi; kırık varlık bağlantısı bulunmadı.

## Doğrulama ve Search Console

- Canlı sitemap'teki 69 adres HTTP, başlık, H1, meta açıklama, canonical ve ürün şeması açısından denetlendi: 0 hata.
- Search Console'da `/sitemap.xml` 15 Ekim'de başarılı okunmuş ve 69 adres keşfedilmiş. Dizin raporunun son güncellemesi 12 Ekim: 11 dizinde, 32 keşfedilmiş fakat henüz dizinde değil, 2 `noindex`. Yeni yayının etkisi bu rapora henüz yansımadı.
- 15 Ekim tek koşu mobil Lighthouse ana sayfa sonucu: performans 86, SEO 100, LCP 4,1 sn, CLS 0,021. Bunlar laboratuvar sonuçlarıdır; Search Console henüz gerçek kullanıcı Core Web Vitals verisi göstermiyor.
- Kargo ve iade şeması gerçek politika/ücret/süre olmadan eklenmedi. Google'ın ürün dokümanına göre bunlar ilgili zengin sonuçları geliştiren isteğe bağlı alanlardır.

## İzleme

Search Console'da 32 “Keşfedildi” adresi yeniden tarandıktan sonra dizine alınma durumu ve ürün kodu sorgularının tıklama, gösterim ve konumu takip edilmeli. İlk sıra garanti edilemez. Eksiksiz ürün teknik verisi için fiziksel etiket, revizyon, test ve gerçek uyumluluk listesi gerekir.

Google kaynakları: [Ürün yapılandırılmış verisi](https://developers.google.com/search/docs/appearance/structured-data/merchant-listing), [Sitemap](https://developers.google.com/search/docs/crawling-indexing/sitemaps/build-sitemap), [Tarama sorunları](https://developers.google.com/search/docs/crawling-indexing/troubleshoot-crawling-errors).
