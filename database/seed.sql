INSERT INTO categories (id, name, parent_id) VALUES
(1, 'TV BOARD', NULL),
(2, 'TV PANEL LEDLERI', NULL),
(3, 'TV PANEL', NULL),
(4, 'TV KUMANDASI', NULL),
(5, 'TV YER AYAGI', NULL);

INSERT INTO brands (id, name) VALUES
(1, 'Samsung'),
(2, 'LG'),
(3, 'Vestel'),
(4, 'Philips'),
(5, 'Arcelik'),
(6, 'Sony');

INSERT INTO product_models (id, brand_id, name) VALUES
(1, 1, 'UE50TU8500'),
(2, 2, '49UK6470'),
(3, 3, '43U9500'),
(4, 4, '55PUS7805'),
(5, 5, 'A49L 8900 5B'),
(6, 6, 'KD-55XH9096');

INSERT INTO part_codes (id, code) VALUES
(1, 'BN94-15413A'),
(2, 'EAX67865201'),
(3, 'VES430UNDL-2D'),
(4, '6870C-0805A'),
(5, 'LVDS-A49L-8900'),
(6, 'A5016366A');

INSERT INTO products (id, name, category_id, brand_id, model_id, part_code_id, price, stock, image_path) VALUES
(101, 'Samsung UE50TU8500 Anakart', 1, 1, 1, 1, 2450.00, 5, 'assets/img/product/home-one/product-1.jpg'),
(102, 'LG 49UK6470 Power Board', 1, 2, 2, 2, 1890.00, 7, 'assets/img/product/home-one/product-2.jpg'),
(103, 'Vestel 43U9500 LED Bar Seti', 2, 3, 3, 3, 980.00, 14, 'assets/img/product/home-one/product-3.jpg'),
(104, 'Philips 55PUS7805 T-Con Kart', 1, 4, 4, 4, 1120.00, 4, 'assets/img/product/home-one/product-4.jpg'),
(105, 'Arcelik A49L 8900 5B Panel Kablosu', 3, 5, 5, 5, 450.00, 30, 'assets/img/product/home-one/product-5.jpg'),
(106, 'Sony KD-55XH9096 Anakart', 1, 6, 6, 6, 3290.00, 3, 'assets/img/product/home-one/product-6.jpg');

INSERT INTO technical_spec_definitions (id, code, label) VALUES
(1, 'part_type', 'Parca Turu'),
(2, 'part_number', 'Parca Numarasi'),
(3, 'known_brand_model', 'Bilinen Marka/Model'),
(4, 'tv_panel_model', 'TV Panel Modeli'),
(5, 'tv_panel_brand', 'TV Panel Markasi'),
(6, 'supplier_code', 'Tedarikci Urun Kodu');

INSERT INTO product_technical_specs (product_id, spec_id, value) VALUES
(101, 1, 'Main Board'),
(101, 2, 'BN94-15413A'),
(101, 3, 'Samsung UE50TU8500'),
(101, 6, 'AA4-54');
