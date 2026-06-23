-- ============================================================
-- SkillSwap Database Schema
-- CP476B Internet Computing — Milestone 02
-- Normalized relational schema with full constraints
-- ============================================================

CREATE DATABASE IF NOT EXISTS skillswap
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE skillswap;

-- ------------------------------------------------------------
-- TABLE: users
-- Stores all platform users regardless of role
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    user_id       INT           NOT NULL AUTO_INCREMENT,
    first_name    VARCHAR(50)   NOT NULL,
    last_name     VARCHAR(50)   NOT NULL,
    email         VARCHAR(100)  NOT NULL,
    password_hash VARCHAR(255)  NOT NULL,
    role          ENUM('customer','provider','admin') NOT NULL DEFAULT 'customer',
    bio           TEXT,
    is_active     BOOLEAN       NOT NULL DEFAULT TRUE,
    created_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_users        PRIMARY KEY (user_id),
    CONSTRAINT uq_users_email  UNIQUE      (email)
);

-- ------------------------------------------------------------
-- TABLE: categories
-- Lookup table — avoids string duplication in services
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
    category_id INT         NOT NULL AUTO_INCREMENT,
    name        VARCHAR(50) NOT NULL,
    CONSTRAINT pk_categories      PRIMARY KEY (category_id),
    CONSTRAINT uq_categories_name UNIQUE      (name)
);

INSERT INTO categories (name) VALUES
    ('Programming'),
    ('Tutoring'),
    ('Design'),
    ('Photography'),
    ('Writing'),
    ('Other');

-- ------------------------------------------------------------
-- TABLE: services
-- Each row is one service listing created by a provider
-- category_id FK enforces valid categories (normalized)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS services (
    service_id  INT            NOT NULL AUTO_INCREMENT,
    user_id     INT            NOT NULL,
    category_id INT            NOT NULL,
    title       VARCHAR(100)   NOT NULL,
    description TEXT           NOT NULL,
    price       DECIMAL(10,2)  NOT NULL,
    is_active   BOOLEAN        NOT NULL DEFAULT TRUE,
    created_at  TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_services           PRIMARY KEY (service_id),
    CONSTRAINT fk_services_user      FOREIGN KEY (user_id)     REFERENCES users(user_id)      ON DELETE CASCADE,
    CONSTRAINT fk_services_category  FOREIGN KEY (category_id) REFERENCES categories(category_id),
    CONSTRAINT chk_services_price    CHECK (price > 0),
    INDEX idx_services_category (category_id),
    INDEX idx_services_user     (user_id),
    INDEX idx_services_active   (is_active)
);

-- ------------------------------------------------------------
-- TABLE: bookings
-- Links a customer to a provider's service
-- provider_id denormalized here intentionally for query speed
-- and to preserve provider reference if service is deleted
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS bookings (
    booking_id     INT       NOT NULL AUTO_INCREMENT,
    service_id     INT       NOT NULL,
    customer_id    INT       NOT NULL,
    provider_id    INT       NOT NULL,
    booking_status ENUM('Pending','Active','Completed','Rejected','Cancelled')
                             NOT NULL DEFAULT 'Pending',
    message        TEXT,
    booking_date   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT pk_bookings          PRIMARY KEY (booking_id),
    CONSTRAINT fk_bookings_service  FOREIGN KEY (service_id)  REFERENCES services(service_id) ON DELETE CASCADE,
    CONSTRAINT fk_bookings_customer FOREIGN KEY (customer_id) REFERENCES users(user_id),
    CONSTRAINT fk_bookings_provider FOREIGN KEY (provider_id) REFERENCES users(user_id),
    CONSTRAINT chk_no_self_booking  CHECK (customer_id <> provider_id),
    INDEX idx_bookings_status   (booking_status),
    INDEX idx_bookings_customer (customer_id),
    INDEX idx_bookings_provider (provider_id)
);

-- ------------------------------------------------------------
-- TABLE: reviews
-- One review per completed booking (UNIQUE on booking_id)
-- rating enforced 1–5 via CHECK constraint
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS reviews (
    review_id   INT     NOT NULL AUTO_INCREMENT,
    booking_id  INT     NOT NULL,
    reviewer_id INT     NOT NULL,
    rating      TINYINT NOT NULL,
    comment     TEXT    NOT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_reviews           PRIMARY KEY (review_id),
    CONSTRAINT uq_reviews_booking   UNIQUE      (booking_id),
    CONSTRAINT fk_reviews_booking   FOREIGN KEY (booking_id)  REFERENCES bookings(booking_id) ON DELETE CASCADE,
    CONSTRAINT fk_reviews_reviewer  FOREIGN KEY (reviewer_id) REFERENCES users(user_id),
    CONSTRAINT chk_reviews_rating   CHECK (rating BETWEEN 1 AND 5)
);

-- ------------------------------------------------------------
-- TABLE: conversations
-- Normalized: one row per unique pair of users
-- Avoids duplicate threads between same two users
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS conversations (
    conversation_id INT       NOT NULL AUTO_INCREMENT,
    user_a_id       INT       NOT NULL,
    user_b_id       INT       NOT NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_conversations        PRIMARY KEY (conversation_id),
    CONSTRAINT uq_conversations_pair   UNIQUE      (user_a_id, user_b_id),
    CONSTRAINT fk_conversations_user_a FOREIGN KEY (user_a_id) REFERENCES users(user_id),
    CONSTRAINT fk_conversations_user_b FOREIGN KEY (user_b_id) REFERENCES users(user_id),
    CONSTRAINT chk_conversations_users CHECK (user_a_id < user_b_id)
);

-- ------------------------------------------------------------
-- TABLE: messages
-- Belongs to a conversation; sender must be one of the pair
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS messages (
    message_id      INT       NOT NULL AUTO_INCREMENT,
    conversation_id INT       NOT NULL,
    sender_id       INT       NOT NULL,
    content         TEXT      NOT NULL,
    is_read         BOOLEAN   NOT NULL DEFAULT FALSE,
    sent_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_messages              PRIMARY KEY (message_id),
    CONSTRAINT fk_messages_conversation FOREIGN KEY (conversation_id) REFERENCES conversations(conversation_id) ON DELETE CASCADE,
    CONSTRAINT fk_messages_sender       FOREIGN KEY (sender_id)       REFERENCES users(user_id),
    INDEX idx_messages_conversation (conversation_id),
    INDEX idx_messages_sender       (sender_id),
    INDEX idx_messages_read         (is_read)
);

-- ------------------------------------------------------------
-- TABLE: reports
-- Tracks user-submitted reports on services, reviews, or users
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS reports (
    report_id   INT       NOT NULL AUTO_INCREMENT,
    reporter_id INT       NOT NULL,
    target_type ENUM('service','review','user') NOT NULL,
    target_id   INT       NOT NULL,
    reason      ENUM('spam','inappropriate','fraud','other') NOT NULL,
    description TEXT,
    status      ENUM('Pending','Resolved','Dismissed') NOT NULL DEFAULT 'Pending',
    reviewed_by INT,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_reports          PRIMARY KEY (report_id),
    CONSTRAINT fk_reports_reporter FOREIGN KEY (reporter_id) REFERENCES users(user_id),
    CONSTRAINT fk_reports_admin    FOREIGN KEY (reviewed_by) REFERENCES users(user_id),
    INDEX idx_reports_status (status),
    INDEX idx_reports_type   (target_type)
);
