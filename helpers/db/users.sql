CREATE TABLE users (
                       id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                       telegram_id INT(32) UNIQUE NOT NULL,
                       user_id INT(6) UNIQUE,
                       first_name VARCHAR(30),
                       last_name VARCHAR(30),
                       username VARCHAR(33),
                       phone_number VARCHAR(32),
                       telegram_number VARCHAR(32),
                       role VARCHAR(10),
                       created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                       update_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

ALTER TABLE `users` ADD `lang` VARCHAR(5) NOT NULL DEFAULT 'uzb' AFTER `update_at`;