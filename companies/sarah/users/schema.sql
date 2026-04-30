-- Create the company database (run once; requires privileges)
CREATE DATABASE IF NOT EXISTS noirium_company_db
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE noirium_company_db;

-- Users table
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(80) NOT NULL,
  last_name VARCHAR(80) NOT NULL,
  email VARCHAR(190) NOT NULL,
  home_address VARCHAR(255) NOT NULL,
  home_phone VARCHAR(40) NOT NULL,
  cell_phone VARCHAR(40) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_email (email),
  KEY idx_name (last_name, first_name),
  KEY idx_home_phone (home_phone),
  KEY idx_cell_phone (cell_phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

