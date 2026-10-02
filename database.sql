CREATE DATABASE IF NOT EXISTS movieverse
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE movieverse;

CREATE TABLE IF NOT EXISTS movies (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  imdb_id VARCHAR(20) NOT NULL,
  rank_no SMALLINT UNSIGNED DEFAULT NULL,
  title VARCHAR(220) NOT NULL,
  release_year SMALLINT UNSIGNED NOT NULL,
  genre VARCHAR(180) NOT NULL DEFAULT 'Drama',
  director VARCHAR(180) NOT NULL DEFAULT 'Unknown',
  cast_text VARCHAR(600) NOT NULL DEFAULT '',
  description TEXT NOT NULL,
  poster_url VARCHAR(1000) NOT NULL,
  imdb_rating DECIMAL(3,1) NOT NULL DEFAULT 0.0,
  rating_count INT UNSIGNED NOT NULL DEFAULT 0,
  runtime SMALLINT UNSIGNED DEFAULT NULL,
  views INT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_movies_imdb_id (imdb_id),
  KEY idx_movies_title (title),
  KEY idx_movies_year (release_year),
  KEY idx_movies_rating (imdb_rating),
  KEY idx_movies_rank (rank_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    status ENUM('active', 'blocked') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
