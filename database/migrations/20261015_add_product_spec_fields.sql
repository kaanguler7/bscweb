-- Add empty, editable product fact fields. Existing products are unchanged.
INSERT IGNORE INTO technical_spec_definitions (code, label) VALUES
('part_type', 'Parça Türü'),
('part_number', 'Parça Numarası'),
('alternate_code', 'Diğer Kart Kodu'),
('known_brand_model', 'Doğrulanmış Uyumlu Marka/Model'),
('tv_panel_model', 'Panel Modeli'),
('tv_panel_brand', 'Panel Markası'),
('supplier_code', 'Tedarikçi Ürün Kodu'),
('board_revision', 'Kart Revizyonu'),
('condition', 'Kondisyon'),
('test_status', 'Test Durumu'),
('package_contents', 'Paket İçeriği');
