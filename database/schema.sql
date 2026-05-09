-- =============================================================================
-- LawMillion.com — MySQL Schema (US Legal Directory)
-- Charset/collation: utf8mb4 / utf8mb4_unicode_ci (full Unicode + emoji safe)
-- Engine: InnoDB (FK + transactions)
-- =============================================================================

CREATE DATABASE IF NOT EXISTS lawmillion
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE lawmillion;

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- -----------------------------------------------------------------------------
-- US STATES (50 + DC) — drives /attorneys/{state}/ URLs
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS us_states (
  id            TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  code          CHAR(2)          NOT NULL,           -- 'TX', 'CA'
  slug          VARCHAR(40)      NOT NULL,           -- 'texas', 'california'
  name          VARCHAR(60)      NOT NULL,           -- 'Texas'
  PRIMARY KEY (id),
  UNIQUE KEY uq_state_code (code),
  UNIQUE KEY uq_state_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------------------
-- US CITIES — drives /attorneys/{state}/{city}/ URLs
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS us_cities (
  id            INT UNSIGNED      NOT NULL AUTO_INCREMENT,
  state_id      TINYINT UNSIGNED  NOT NULL,
  slug          VARCHAR(80)       NOT NULL,          -- 'dallas'
  name          VARCHAR(100)      NOT NULL,          -- 'Dallas'
  population    INT UNSIGNED      DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_state_city_slug (state_id, slug),
  KEY idx_state (state_id),
  CONSTRAINT fk_city_state FOREIGN KEY (state_id) REFERENCES us_states(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------------------
-- PRACTICE AREAS — drives /practice-areas/{slug}/ URLs
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS practice_areas (
  id              SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  slug            VARCHAR(80)       NOT NULL,        -- 'personal-injury'
  name            VARCHAR(120)      NOT NULL,        -- 'Personal Injury'
  noun            VARCHAR(20)       NOT NULL DEFAULT 'attorney',  -- 'attorney' or 'lawyer'
  meta_title      VARCHAR(255)      NOT NULL,
  meta_description VARCHAR(500)     NOT NULL,
  meta_keywords   TEXT              NULL,
  h1              VARCHAR(255)      NOT NULL,
  intro_html      MEDIUMTEXT        NULL,            -- hero/intro content
  body_html       LONGTEXT          NULL,            -- main content body
  faq_json        LONGTEXT          NULL,            -- JSON: [{q,a},...] for FAQPage schema
  service_offers_json TEXT          NULL,            -- JSON: list of sub-services
  og_image_url    VARCHAR(500)      NULL,
  is_active       TINYINT(1)        NOT NULL DEFAULT 1,
  sort_order      SMALLINT          NOT NULL DEFAULT 0,
  date_published  DATE              NOT NULL DEFAULT (CURRENT_DATE),
  date_modified   DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_practice_slug (slug),
  KEY idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------------------
-- USERS — base table for both attorneys and clients
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  email           VARCHAR(190) NOT NULL,
  password_hash   VARCHAR(255) NOT NULL,             -- password_hash() output
  role            ENUM('attorney','admin','client') NOT NULL DEFAULT 'attorney',
  email_verified  TINYINT(1)   NOT NULL DEFAULT 0,
  is_active       TINYINT(1)   NOT NULL DEFAULT 1,
  last_login_at   DATETIME     NULL,
  created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_user_email (email),
  KEY idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------------------
-- ATTORNEYS — drives /attorneys/{state}/{city}/{slug}/ URLs
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS attorneys (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id         INT UNSIGNED NULL,
  slug            VARCHAR(120) NOT NULL,             -- 'amanda-j-richardson'
  first_name      VARCHAR(80)  NOT NULL,
  last_name       VARCHAR(80)  NOT NULL,
  middle_initial  VARCHAR(5)   NULL,
  suffix          VARCHAR(20)  NULL,
  headline        VARCHAR(255) NULL,                 -- 'Personal Injury Attorney'
  bio_html        MEDIUMTEXT   NULL,
  bar_number      VARCHAR(50)  NULL,
  bar_state_id    TINYINT UNSIGNED NULL,
  years_practicing SMALLINT UNSIGNED NULL,
  law_school      VARCHAR(200) NULL,
  firm_name       VARCHAR(200) NULL,
  street_address  VARCHAR(255) NULL,
  city_id         INT UNSIGNED NULL,                 -- primary office city
  state_id        TINYINT UNSIGNED NULL,
  zip_code        VARCHAR(10)  NULL,                 -- US zips: 12345 or 12345-6789
  phone           VARCHAR(20)  NULL,                 -- store as +1XXXXXXXXXX
  email_public    VARCHAR(190) NULL,
  website_url     VARCHAR(500) NULL,
  photo_url       VARCHAR(500) NULL,
  hourly_rate_low  INT UNSIGNED NULL,                -- USD
  hourly_rate_high INT UNSIGNED NULL,                -- USD
  free_consultation TINYINT(1) NOT NULL DEFAULT 1,
  contingency_fee  TINYINT(1)  NOT NULL DEFAULT 0,
  rating_avg      DECIMAL(2,1) NOT NULL DEFAULT 0.0,
  review_count    INT UNSIGNED NOT NULL DEFAULT 0,
  is_verified     TINYINT(1)   NOT NULL DEFAULT 0,
  is_active       TINYINT(1)   NOT NULL DEFAULT 1,
  meta_title      VARCHAR(255) NULL,
  meta_description VARCHAR(500) NULL,
  created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_attorney_slug (slug),
  KEY idx_attorney_loc (state_id, city_id),
  KEY idx_attorney_active (is_active, is_verified),
  CONSTRAINT fk_attorney_user FOREIGN KEY (user_id)  REFERENCES users(id)     ON DELETE SET NULL,
  CONSTRAINT fk_attorney_city FOREIGN KEY (city_id)  REFERENCES us_cities(id) ON DELETE SET NULL,
  CONSTRAINT fk_attorney_state FOREIGN KEY (state_id) REFERENCES us_states(id) ON DELETE SET NULL,
  CONSTRAINT fk_attorney_bar_state FOREIGN KEY (bar_state_id) REFERENCES us_states(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Many-to-many: attorneys ↔ practice areas
CREATE TABLE IF NOT EXISTS attorney_practice_areas (
  attorney_id        INT UNSIGNED      NOT NULL,
  practice_area_id   SMALLINT UNSIGNED NOT NULL,
  is_primary         TINYINT(1)        NOT NULL DEFAULT 0,
  PRIMARY KEY (attorney_id, practice_area_id),
  KEY idx_apa_pa (practice_area_id),
  CONSTRAINT fk_apa_attorney FOREIGN KEY (attorney_id)      REFERENCES attorneys(id)       ON DELETE CASCADE,
  CONSTRAINT fk_apa_pa       FOREIGN KEY (practice_area_id) REFERENCES practice_areas(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------------------
-- ATTORNEY REVIEWS — feeds AggregateRating schema
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS attorney_reviews (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  attorney_id   INT UNSIGNED NOT NULL,
  reviewer_name VARCHAR(120) NOT NULL,
  rating        TINYINT UNSIGNED NOT NULL,            -- 1-5
  title         VARCHAR(255) NULL,
  body          TEXT         NULL,
  is_published  TINYINT(1)   NOT NULL DEFAULT 1,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_review_attorney (attorney_id, is_published),
  CONSTRAINT fk_review_attorney FOREIGN KEY (attorney_id) REFERENCES attorneys(id) ON DELETE CASCADE,
  CONSTRAINT chk_review_rating CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------------------
-- BLOG CATEGORIES + POSTS — drives /blog/{slug}/ URLs
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS blog_categories (
  id    SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  slug  VARCHAR(80) NOT NULL,
  name  VARCHAR(120) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_blog_cat_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS blog_posts (
  id                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
  slug               VARCHAR(200) NOT NULL,           -- 'tcja-sunset-2026-tax-cuts-jobs-act-guide'
  category_id        SMALLINT UNSIGNED NULL,
  practice_area_id   SMALLINT UNSIGNED NULL,         -- optional link to practice area
  title              VARCHAR(255) NOT NULL,
  meta_title         VARCHAR(255) NOT NULL,
  meta_description   VARCHAR(500) NOT NULL,
  excerpt            VARCHAR(500) NULL,
  body_html          LONGTEXT     NOT NULL,
  featured_image_url VARCHAR(500) NULL,
  author_name        VARCHAR(120) NOT NULL DEFAULT 'LawMillion Editorial Team',
  author_url         VARCHAR(500) NULL,
  status             ENUM('draft','published','archived') NOT NULL DEFAULT 'published',
  reading_minutes    SMALLINT UNSIGNED NULL,
  view_count         INT UNSIGNED NOT NULL DEFAULT 0,
  date_published     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_modified      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_blog_slug (slug),
  KEY idx_blog_status_date (status, date_published),
  KEY idx_blog_pa (practice_area_id),
  CONSTRAINT fk_blog_cat  FOREIGN KEY (category_id)      REFERENCES blog_categories(id) ON DELETE SET NULL,
  CONSTRAINT fk_blog_pa   FOREIGN KEY (practice_area_id) REFERENCES practice_areas(id)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------------------
-- LEADS — submissions from "Find a Lawyer" + practice-area CTAs
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS leads (
  id                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
  practice_area_id   SMALLINT UNSIGNED NULL,
  state_id           TINYINT UNSIGNED  NULL,
  city_id            INT UNSIGNED      NULL,
  zip_code           VARCHAR(10) NULL,
  full_name          VARCHAR(120) NOT NULL,
  email              VARCHAR(190) NOT NULL,
  phone              VARCHAR(20)  NULL,
  case_summary       TEXT         NOT NULL,
  preferred_contact  ENUM('email','phone','either') NOT NULL DEFAULT 'either',
  source_page        VARCHAR(255) NULL,                -- e.g. /practice-areas/personal-injury/
  ip_address         VARCHAR(45)  NULL,                -- IPv4/IPv6
  user_agent         VARCHAR(500) NULL,
  status             ENUM('new','assigned','contacted','closed','spam') NOT NULL DEFAULT 'new',
  assigned_attorney_id INT UNSIGNED NULL,
  created_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_leads_status (status, created_at),
  KEY idx_leads_pa (practice_area_id),
  CONSTRAINT fk_lead_pa       FOREIGN KEY (practice_area_id)    REFERENCES practice_areas(id) ON DELETE SET NULL,
  CONSTRAINT fk_lead_state    FOREIGN KEY (state_id)            REFERENCES us_states(id)      ON DELETE SET NULL,
  CONSTRAINT fk_lead_city     FOREIGN KEY (city_id)             REFERENCES us_cities(id)      ON DELETE SET NULL,
  CONSTRAINT fk_lead_attorney FOREIGN KEY (assigned_attorney_id) REFERENCES attorneys(id)     ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------------------
-- SESSIONS — DB-backed sessions for the attorney portal
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS sessions (
  id            CHAR(64)     NOT NULL,                 -- session_id
  user_id       INT UNSIGNED NULL,
  ip_address    VARCHAR(45)  NULL,
  user_agent    VARCHAR(500) NULL,
  payload       MEDIUMTEXT   NULL,
  last_activity INT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  KEY idx_sess_user (user_id),
  KEY idx_sess_activity (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------------------
-- 301 REDIRECT MAP — runtime fallback for old/legacy URLs
-- (Most are also handled in .htaccess for speed, but DB allows ops to
--  add/edit redirects without touching server config.)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS redirects (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  old_path      VARCHAR(500) NOT NULL,
  new_path      VARCHAR(500) NOT NULL,
  status_code   SMALLINT     NOT NULL DEFAULT 301,
  hit_count     INT UNSIGNED NOT NULL DEFAULT 0,
  last_hit_at   DATETIME     NULL,
  is_active     TINYINT(1)   NOT NULL DEFAULT 1,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_redirect_old (old_path),
  KEY idx_redirect_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
