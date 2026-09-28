-- =========================================================
-- Expense Tracker — database schema
--
-- Import in phpMyAdmin: Import tab -> choose this file -> Go.
-- Creates the `expense_tracker` database (matches config/database.php),
-- all tables, and a set of default categories shared by every user.
-- Safe to re-import: existing tables and data are left untouched.
-- =========================================================

CREATE DATABASE IF NOT EXISTS `expense_tracker`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `expense_tracker`;


-- ---------------------------------------------------------
-- users
-- ---------------------------------------------------------

CREATE TABLE IF NOT EXISTS `users` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(100) NOT NULL,
    `email`      VARCHAR(150) NOT NULL,
    `password`   VARCHAR(255) NOT NULL,          -- password_hash() output
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------
-- categories
-- user_id NULL  = default category available to everyone
-- user_id set   = custom category created by that user
-- ---------------------------------------------------------

CREATE TABLE IF NOT EXISTS `categories` (
    `id`      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NULL DEFAULT NULL,
    `name`    VARCHAR(100) NOT NULL,
    `type`    ENUM('income', 'expense') NOT NULL,

    PRIMARY KEY (`id`),
    KEY `idx_categories_user` (`user_id`),

    CONSTRAINT `fk_categories_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------
-- transactions
-- ---------------------------------------------------------

CREATE TABLE IF NOT EXISTS `transactions` (
    `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`          INT UNSIGNED NOT NULL,
    `category_id`      INT UNSIGNED NOT NULL,
    `type`             ENUM('income', 'expense') NOT NULL,
    `amount`           DECIMAL(12,2) NOT NULL,
    `description`      VARCHAR(500) NULL DEFAULT NULL,
    `transaction_date` DATE NOT NULL,
    `created_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `idx_transactions_user_date` (`user_id`, `transaction_date`),
    KEY `idx_transactions_category` (`category_id`),

    CONSTRAINT `fk_transactions_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE,

    CONSTRAINT `fk_transactions_category`
        FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------
-- budgets
-- category_id NULL = overall monthly budget
-- ---------------------------------------------------------

CREATE TABLE IF NOT EXISTS `budgets` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`     INT UNSIGNED NOT NULL,
    `category_id` INT UNSIGNED NULL DEFAULT NULL,
    `amount`      DECIMAL(12,2) NOT NULL,
    `month`       TINYINT UNSIGNED NOT NULL,
    `year`        SMALLINT UNSIGNED NOT NULL,
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `idx_budgets_user_period` (`user_id`, `year`, `month`),
    KEY `idx_budgets_category` (`category_id`),

    CONSTRAINT `fk_budgets_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE,

    CONSTRAINT `fk_budgets_category`
        FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------
-- Default categories (inserted only if none exist yet)
-- ---------------------------------------------------------

INSERT INTO `categories` (`user_id`, `name`, `type`)
SELECT NULL, v.`name`, v.`type`
FROM (
    SELECT 'Food & Dining'   AS `name`, 'expense' AS `type`
    UNION ALL SELECT 'Transport',        'expense'
    UNION ALL SELECT 'Rent',             'expense'
    UNION ALL SELECT 'Utilities',        'expense'
    UNION ALL SELECT 'Groceries',        'expense'
    UNION ALL SELECT 'Shopping',         'expense'
    UNION ALL SELECT 'Health',           'expense'
    UNION ALL SELECT 'Education',        'expense'
    UNION ALL SELECT 'Entertainment',    'expense'
    UNION ALL SELECT 'Other Expense',    'expense'
    UNION ALL SELECT 'Salary',           'income'
    UNION ALL SELECT 'Freelance',        'income'
    UNION ALL SELECT 'Business',         'income'
    UNION ALL SELECT 'Investment',       'income'
    UNION ALL SELECT 'Gift',             'income'
    UNION ALL SELECT 'Other Income',     'income'
) AS v
WHERE NOT EXISTS (
    SELECT 1 FROM `categories` c WHERE c.`user_id` IS NULL
);
