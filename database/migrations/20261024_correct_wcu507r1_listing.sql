-- Owner confirmed on 24 October 2026 that only WCU507R-1 is in stock.
-- The product photo also shows WCU507R-1 on both cables. Remove the
-- unsupported WCU507R-4 variant from the visible listing and schema name.
START TRANSACTION;

UPDATE products
SET name = 'WCU507R-1, GRUNDIG 49CLX7820BP FLEX KABLO',
    description = 'WCU507R-1 kodlu Grundig flex kablo. Ürün fotoğrafındaki iki kabloda da WCU507R-1 baskısı görülüyor. 49CLX7820BP model bilgisi mevcut ilanda yer alıyor. Siparişten önce kendi kablonuzdaki kodu, uç yönünü ve bağlantı yerleşimini fotoğrafla karşılaştırın.'
WHERE id = 49
  AND part_code_id = (SELECT id FROM part_codes WHERE code = 'WCU507R-1' LIMIT 1);

COMMIT;
