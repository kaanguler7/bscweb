-- Run only after confirming that every existing product has category, brand and part code values.
ALTER TABLE products
  DROP FOREIGN KEY fk_products_category,
  DROP FOREIGN KEY fk_products_brand,
  DROP FOREIGN KEY fk_products_part_code;

ALTER TABLE products
  MODIFY category_id INT NOT NULL,
  MODIFY brand_id INT NOT NULL,
  MODIFY part_code_id INT NOT NULL;

ALTER TABLE products
  ADD CONSTRAINT fk_products_category
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
  ADD CONSTRAINT fk_products_brand
    FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE RESTRICT,
  ADD CONSTRAINT fk_products_part_code
    FOREIGN KEY (part_code_id) REFERENCES part_codes(id) ON DELETE RESTRICT;
