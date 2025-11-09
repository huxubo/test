-- 子域分发管理系统数据库结构

CREATE TABLE `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(255) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `username` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(30) NULL,
    `is_verified` TINYINT(1) NOT NULL DEFAULT 0,
    `is_admin` TINYINT(1) NOT NULL DEFAULT 0,
    `verification_token` VARCHAR(64) NULL,
    `verification_sent_at` DATETIME NULL,
    `subdomain_quota` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `domain_providers` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `provider_type` VARCHAR(50) NOT NULL,
    `api_key` VARCHAR(255) NULL,
    `api_secret` VARCHAR(255) NULL,
    `api_account` VARCHAR(255) NULL,
    `extra_params` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `primary_domains` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `domain_provider_id` INT UNSIGNED NOT NULL,
    `domain_name` VARCHAR(255) NOT NULL,
    `provider_reference` VARCHAR(255) NULL,
    `description` VARCHAR(255) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_domain_name` (`domain_name`),
    CONSTRAINT `fk_primary_domain_provider` FOREIGN KEY (`domain_provider_id`) REFERENCES `domain_providers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `subdomains` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `primary_domain_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `label` VARCHAR(100) NOT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
    `ns_records` TEXT NULL,
    `registered_at` DATETIME NULL,
    `expires_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_subdomain_label` (`primary_domain_id`, `label`),
    CONSTRAINT `fk_subdomain_primary` FOREIGN KEY (`primary_domain_id`) REFERENCES `primary_domains` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_subdomain_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `subdomain_transfers` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `subdomain_id` INT UNSIGNED NOT NULL,
    `from_user_id` INT UNSIGNED NOT NULL,
    `to_user_id` INT UNSIGNED NOT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'completed',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_transfer_subdomain` FOREIGN KEY (`subdomain_id`) REFERENCES `subdomains` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_transfer_from_user` FOREIGN KEY (`from_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_transfer_to_user` FOREIGN KEY (`to_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `settings` (
    `key` VARCHAR(100) NOT NULL,
    `value` TEXT NULL,
    PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `settings` (`key`, `value`) VALUES
    ('subdomain.auto_review', '1'),
    ('subdomain.initial_valid_days', '365'),
    ('user.initial_subdomain_quota', '3')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);
