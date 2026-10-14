-- Apply once before deploying code that reads or writes products.description.
ALTER TABLE products ADD COLUMN description TEXT NULL AFTER name;
