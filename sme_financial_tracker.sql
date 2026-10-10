-- SME Financial Tracker: import this file in phpMyAdmin's Import tab.
-- Compatible with XAMPP MySQL/MariaDB. Import once into a NEW database.
-- No DROP DATABASE or DROP TABLE statements. Existing data is not erased.
CREATE DATABASE IF NOT EXISTS sme_financial_tracker CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sme_financial_tracker;
SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS users (
 id INT UNSIGNED NOT NULL AUTO_INCREMENT,
 name VARCHAR(80) NOT NULL,
 email VARCHAR(160) NOT NULL,
 password_hash VARCHAR(255) NOT NULL,
 role ENUM('owner','accountant','admin') NOT NULL,
 active TINYINT(1) NOT NULL DEFAULT 1,
 PRIMARY KEY (id), UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categories (
 id INT UNSIGNED NOT NULL AUTO_INCREMENT,
 name VARCHAR(80) NOT NULL,
 type ENUM('income','expense') NOT NULL,
 PRIMARY KEY (id), UNIQUE KEY uq_categories_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS transactions (
 id INT UNSIGNED NOT NULL AUTO_INCREMENT,
 type ENUM('income','expense') NOT NULL,
 category_id INT UNSIGNED NOT NULL,
 description VARCHAR(160) NOT NULL,
 amount_cents BIGINT UNSIGNED NOT NULL,
 record_date DATE NOT NULL,
 created_by INT UNSIGNED NOT NULL,
 status ENUM('pending','reviewed') NOT NULL DEFAULT 'pending',
 reviewed_by INT UNSIGNED NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY (id), KEY idx_record_date (record_date),
 CONSTRAINT fk_record_category FOREIGN KEY (category_id) REFERENCES categories(id),
 CONSTRAINT fk_record_creator FOREIGN KEY (created_by) REFERENCES users(id),
 CONSTRAINT fk_record_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id),
 CONSTRAINT ck_positive_amount CHECK (amount_cents > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS activity (
 id INT UNSIGNED NOT NULL AUTO_INCREMENT,
 user_id INT UNSIGNED NOT NULL,
 action VARCHAR(255) NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY (id),
 CONSTRAINT fk_activity_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Three demo logins. Default password: SmeDemo2026!
-- Passwords are bcrypt hashes, never plaintext in the users table.
START TRANSACTION;
INSERT INTO users (id, name, email, password_hash, role) VALUES
 (1, 'Business Owner', 'owner@sme.local', '$2y$12$q.jmkjZav6.vmYg0NV7OJ.EPoTbtf.jEt2N4lMPsdE.g6N5RdBAgi', 'owner'),
 (2, 'Maria Santos', 'accountant@sme.local', '$2y$12$q.jmkjZav6.vmYg0NV7OJ.EPoTbtf.jEt2N4lMPsdE.g6N5RdBAgi', 'accountant'),
 (3, 'System Admin', 'admin@sme.local', '$2y$12$q.jmkjZav6.vmYg0NV7OJ.EPoTbtf.jEt2N4lMPsdE.g6N5RdBAgi', 'admin');

INSERT INTO categories (id, name, type) VALUES
 (1,'Sales','income'), (2,'Services','income'), (3,'Other income','income'),
 (4,'Supplies','expense'), (5,'Rent','expense'), (6,'Utilities','expense'),
 (7,'Payroll','expense'), (8,'Transport','expense'), (9,'Marketing','expense'), (10,'Other expense','expense');

-- Example records for exploring the dashboard; remove them before real use.
INSERT INTO transactions (id,type,category_id,description,amount_cents,record_date,created_by,status,reviewed_by) VALUES
 (1,'income',1,'Retail sales',2850000,DATE_SUB(CURDATE(), INTERVAL 7 DAY),1,'reviewed',2),
 (2,'expense',5,'Monthly workspace rent',850000,DATE_SUB(CURDATE(), INTERVAL 6 DAY),1,'reviewed',2),
 (3,'income',2,'Design project payment',1600000,DATE_SUB(CURDATE(), INTERVAL 5 DAY),1,'reviewed',2),
 (4,'expense',4,'Packaging and supplies',235000,DATE_SUB(CURDATE(), INTERVAL 4 DAY),1,'pending',NULL),
 (5,'expense',6,'Internet and electricity',195000,DATE_SUB(CURDATE(), INTERVAL 3 DAY),1,'pending',NULL),
 (6,'income',1,'Online orders',1275000,DATE_SUB(CURDATE(), INTERVAL 2 DAY),1,'pending',NULL),
 (7,'expense',9,'Social media campaign',320000,DATE_SUB(CURDATE(), INTERVAL 1 DAY),1,'pending',NULL);
INSERT INTO activity (user_id,action) VALUES (1,'Demo workspace initialized');
COMMIT;
