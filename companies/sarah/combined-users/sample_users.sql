-- sample_users.sql
-- Create a simple users table and sample data for one company database.
-- Run this in the local company's DB (change DB name as needed).

CREATE DATABASE IF NOT EXISTS company_a_db;
USE company_a_db;

DROP TABLE IF EXISTS users;
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  company VARCHAR(100) NOT NULL
);

INSERT INTO users (name, email, company) VALUES
('Alice Johnson', 'alice@company-a.com', 'Company A'),
('Brian Lee', 'brian@company-a.com', 'Company A'),
('Cindy Tran', 'cindy@company-a.com', 'Company A'),
('David Kim', 'david@company-a.com', 'Company A');
