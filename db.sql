
-- VoteSecure Database Setup
-- Database: votesecure_db

CREATE DATABASE IF NOT EXISTS `votesecure_db`
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE `votesecure_db`;

SET FOREIGN_KEY_CHECKS = 0;

-- Users table
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `voter_id` VARCHAR(50) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `mobile` VARCHAR(15) NOT NULL,
  `dob` DATE NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'voter') DEFAULT 'voter',
  `status` ENUM('pending', 'verified') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_voter_id` (`voter_id`),
  UNIQUE KEY `unique_email` (`email`),
  INDEX `idx_voter_id` (`voter_id`),
  INDEX `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Elections table
DROP TABLE IF EXISTS `elections`;

CREATE TABLE `elections` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(150) NOT NULL,
  `description` TEXT NOT NULL,
  `start_date` DATE NOT NULL,
  `start_time` TIME NOT NULL,
  `end_date` DATE NOT NULL,
  `end_time` TIME NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_election_dates` (`start_date`, `end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Candidates table
DROP TABLE IF EXISTS `candidates`;

CREATE TABLE `candidates` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `election_id` INT NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `party` VARCHAR(100) NOT NULL,
  `photo` VARCHAR(255) DEFAULT NULL,
  `manifesto` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`election_id`) REFERENCES `elections` (`id`) ON DELETE CASCADE,
  INDEX `idx_candidate_election` (`election_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Votes table
DROP TABLE IF EXISTS `votes`;

CREATE TABLE `votes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `voter_id` INT NOT NULL,
  `election_id` INT NOT NULL,
  `candidate_id` INT NOT NULL,
  `casted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`voter_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`election_id`) REFERENCES `elections` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_voter_election` (`voter_id`, `election_id`),
  INDEX `idx_vote_voter` (`voter_id`),
  INDEX `idx_vote_election` (`election_id`),
  INDEX `idx_vote_candidate` (`candidate_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Demo administrator account
-- Use only for local demonstration.
-- Replace the default credentials before any real deployment.

INSERT INTO `users`
(`name`, `voter_id`, `email`, `mobile`, `dob`, `password`, `role`, `status`)
VALUES
('System Administrator', 'ADMIN07', 'VARUN7@admin.com',
 '9876543210', '2000-01-01',
 '$2y$10$152OjjDN8/rUFKvTlKWtQ.ZqHkgVXhxIofG0rMwmGZeUHqU1fbDRO',
 'admin', 'verified')
ON DUPLICATE KEY UPDATE `email` = VALUES(`email`);

SET FOREIGN_KEY_CHECKS = 1;
