-- SQL schema for VAREEN Academy backend
-- Run this on your MySQL/MariaDB server (adjust types as needed)

CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) NOT NULL,
  `email` VARCHAR(191) NOT NULL,
  `phone` VARCHAR(50) DEFAULT NULL,
  `subject` VARCHAR(255) DEFAULT NULL,
  `message` TEXT NOT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(512) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX (`email`)
);

CREATE TABLE IF NOT EXISTS `applications` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `first_name` VARCHAR(191) NOT NULL,
  `last_name` VARCHAR(191) NOT NULL,
  `email` VARCHAR(191) NOT NULL,
  `phone` VARCHAR(50) NOT NULL,
  `program` VARCHAR(255) NOT NULL,
  `start_date` DATE DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX (`email`)
);

CREATE TABLE IF NOT EXISTS `recruitment_applications` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `first_name` VARCHAR(191) NOT NULL,
  `last_name` VARCHAR(191) NOT NULL,
  `email` VARCHAR(191) NOT NULL,
  `phone` VARCHAR(50) DEFAULT NULL,
  `date_of_birth` DATE DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `city` VARCHAR(191) DEFAULT NULL,
  `state` VARCHAR(191) DEFAULT NULL,
  `agency` VARCHAR(100) DEFAULT NULL,
  `position_applied` VARCHAR(255) DEFAULT NULL,
  `qualification_level` VARCHAR(100) DEFAULT NULL,
  `years_of_experience` INT DEFAULT NULL,
  `specialization` VARCHAR(255) DEFAULT NULL,
  `previous_employment` TEXT DEFAULT NULL,
  `medical_fitness` VARCHAR(50) DEFAULT NULL,
  `criminal_record` TINYINT(1) DEFAULT 0,
  `criminal_details` TEXT DEFAULT NULL,
  `guarantor_name` VARCHAR(191) DEFAULT NULL,
  `guarantor_phone` VARCHAR(50) DEFAULT NULL,
  `guarantor_address` TEXT DEFAULT NULL,
  `application_fee` DECIMAL(10,2) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX (`email`),
  INDEX (`agency`)
);

