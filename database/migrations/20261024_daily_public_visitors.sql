-- The old daily_visitors table includes bots, admin requests and non-page routes.
-- Keep it for historical comparison; start the new, filtered metric from deployment.
CREATE TABLE IF NOT EXISTS daily_public_visitors (
  id INT PRIMARY KEY AUTO_INCREMENT,
  visit_date DATE NOT NULL,
  visitor_hash CHAR(64) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_daily_public_visitor (visit_date, visitor_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
