CREATE DATABASE IF NOT EXISTS koperasi_digital
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE koperasi_digital;

SET FOREIGN_KEY_CHECKS = 0;

DROP VIEW IF EXISTS v_attendance_calendar;
DROP VIEW IF EXISTS v_customer_risk_summary;
DROP VIEW IF EXISTS v_daily_collection_monitoring;
DROP PROCEDURE IF EXISTS sp_generate_loan_schedules;

DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS auth_sessions;
DROP TABLE IF EXISTS attendance_records;
DROP TABLE IF EXISTS attendance_logs;
DROP TABLE IF EXISTS fingerprint_credentials;
DROP TABLE IF EXISTS iot_devices;
DROP TABLE IF EXISTS approval_requests;
DROP TABLE IF EXISTS customer_daily_snapshots;
DROP TABLE IF EXISTS payment_allocations;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS payment_qr_schedules;
DROP TABLE IF EXISTS payment_qrs;
DROP TABLE IF EXISTS loan_schedules;
DROP TABLE IF EXISTS loan_collaterals;
DROP TABLE IF EXISTS loan_business_photos;
DROP TABLE IF EXISTS loans;
DROP TABLE IF EXISTS loan_applications;
DROP TABLE IF EXISTS customer_profiles;
DROP TABLE IF EXISTS employee_profiles;
DROP TABLE IF EXISTS branches;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  role ENUM('CUSTOMER', 'COLLECTOR', 'ADMIN', 'MASTER_ADMIN') NOT NULL,
  status ENUM('INVITED', 'ACTIVE', 'SUSPENDED', 'ARCHIVED') NOT NULL DEFAULT 'ACTIVE',
  name VARCHAR(160) NOT NULL,
  username VARCHAR(80) NULL,
  email VARCHAR(160) NULL,
  phone VARCHAR(30) NULL,
  password_hash VARCHAR(255) NOT NULL,
  last_login_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_username (username),
  UNIQUE KEY uq_users_email (email),
  UNIQUE KEY uq_users_phone (phone),
  KEY idx_users_role_status (role, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE branches (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(160) NOT NULL,
  address TEXT NULL,
  phone VARCHAR(30) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE employee_profiles (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  branch_id BIGINT UNSIGNED NULL,
  supervisor_id BIGINT UNSIGNED NULL,
  employee_code VARCHAR(40) NOT NULL,
  position VARCHAR(120) NULL,
  can_collect BOOLEAN NOT NULL DEFAULT TRUE,
  active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_employee_user (user_id),
  UNIQUE KEY uq_employee_code (employee_code),
  KEY idx_employee_branch_active (branch_id, active),
  KEY idx_employee_supervisor (supervisor_id),
  CONSTRAINT fk_employee_user FOREIGN KEY (user_id) REFERENCES users(id),
  CONSTRAINT fk_employee_branch FOREIGN KEY (branch_id) REFERENCES branches(id),
  CONSTRAINT fk_employee_supervisor FOREIGN KEY (supervisor_id) REFERENCES employee_profiles(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE customer_profiles (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  collector_id BIGINT UNSIGNED NOT NULL,
  status ENUM('DRAFT', 'PENDING_SURVEY', 'PENDING_APPROVAL', 'ACTIVE', 'REJECTED', 'SUSPENDED', 'CLOSED') NOT NULL DEFAULT 'PENDING_APPROVAL',
  national_id VARCHAR(40) NULL,
  business_name VARCHAR(180) NOT NULL,
  business_type VARCHAR(120) NULL,
  business_address TEXT NOT NULL,
  business_lat DECIMAL(10,7) NULL,
  business_lng DECIMAL(10,7) NULL,
  survey_note TEXT NULL,
  surveyed_at DATETIME NULL,
  activated_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_customer_user (user_id),
  KEY idx_customer_collector_status (collector_id, status),
  KEY idx_customer_location (business_lat, business_lng),
  CONSTRAINT fk_customer_user FOREIGN KEY (user_id) REFERENCES users(id),
  CONSTRAINT fk_customer_collector FOREIGN KEY (collector_id) REFERENCES employee_profiles(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE loan_applications (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  customer_id BIGINT UNSIGNED NOT NULL,
  proposed_by_employee_id BIGINT UNSIGNED NOT NULL,
  reviewed_by_user_id BIGINT UNSIGNED NULL,
  requested_amount DECIMAL(14,2) NOT NULL,
  recommended_amount DECIMAL(14,2) NULL,
  daily_installment DECIMAL(14,2) NULL,
  repayment_analysis TEXT NOT NULL,
  work_or_business_summary TEXT NULL,
  status ENUM('DRAFT', 'SUBMITTED', 'APPROVED', 'REJECTED', 'CANCELLED') NOT NULL DEFAULT 'SUBMITTED',
  reviewed_at DATETIME NULL,
  review_note TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_application_status_created (status, created_at),
  KEY idx_application_proposer_status (proposed_by_employee_id, status),
  KEY idx_application_customer (customer_id),
  CONSTRAINT fk_application_customer FOREIGN KEY (customer_id) REFERENCES customer_profiles(id),
  CONSTRAINT fk_application_proposer FOREIGN KEY (proposed_by_employee_id) REFERENCES employee_profiles(id),
  CONSTRAINT fk_application_reviewer FOREIGN KEY (reviewed_by_user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE loans (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  loan_no VARCHAR(60) NOT NULL,
  application_id BIGINT UNSIGNED NULL,
  customer_id BIGINT UNSIGNED NOT NULL,
  collector_id BIGINT UNSIGNED NOT NULL,
  approved_by_user_id BIGINT UNSIGNED NOT NULL,
  principal_amount DECIMAL(14,2) NOT NULL,
  daily_installment DECIMAL(14,2) NOT NULL,
  total_collectible DECIMAL(14,2) NOT NULL,
  outstanding_amount DECIMAL(14,2) NOT NULL,
  term_days SMALLINT UNSIGNED NOT NULL DEFAULT 60,
  disbursement_date DATE NOT NULL,
  start_date DATE NOT NULL,
  expected_end_date DATE NULL,
  status ENUM('PROPOSED', 'ACTIVE', 'PAID_OFF', 'DEFAULTED', 'CANCELLED') NOT NULL DEFAULT 'ACTIVE',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_loans_loan_no (loan_no),
  UNIQUE KEY uq_loans_application (application_id),
  KEY idx_loans_customer_status (customer_id, status),
  KEY idx_loans_collector_status (collector_id, status),
  KEY idx_loans_dates (start_date, expected_end_date),
  CONSTRAINT fk_loan_application FOREIGN KEY (application_id) REFERENCES loan_applications(id),
  CONSTRAINT fk_loan_customer FOREIGN KEY (customer_id) REFERENCES customer_profiles(id),
  CONSTRAINT fk_loan_collector FOREIGN KEY (collector_id) REFERENCES employee_profiles(id),
  CONSTRAINT fk_loan_approved_by FOREIGN KEY (approved_by_user_id) REFERENCES users(id),
  CONSTRAINT chk_loan_term_60 CHECK (term_days = 60)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE loan_business_photos (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  loan_application_id BIGINT UNSIGNED NOT NULL,
  file_url VARCHAR(500) NOT NULL,
  caption VARCHAR(160) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_business_photo_application (loan_application_id),
  CONSTRAINT fk_business_photo_application FOREIGN KEY (loan_application_id) REFERENCES loan_applications(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE loan_collaterals (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  loan_application_id BIGINT UNSIGNED NOT NULL,
  collateral_type VARCHAR(120) NOT NULL,
  description TEXT NULL,
  estimated_value DECIMAL(14,2) NULL,
  photo_url VARCHAR(500) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_collateral_application (loan_application_id),
  CONSTRAINT fk_collateral_application FOREIGN KEY (loan_application_id) REFERENCES loan_applications(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE loan_schedules (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  loan_id BIGINT UNSIGNED NOT NULL,
  day_index SMALLINT UNSIGNED NOT NULL,
  due_date DATE NOT NULL,
  due_amount DECIMAL(14,2) NOT NULL,
  paid_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  status ENUM('PENDING', 'PARTIAL', 'PAID', 'OVERDUE', 'SKIPPED') NOT NULL DEFAULT 'PENDING',
  paid_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_schedule_loan_day (loan_id, day_index),
  UNIQUE KEY uq_schedule_loan_date (loan_id, due_date),
  KEY idx_schedule_due_status (due_date, status),
  CONSTRAINT fk_schedule_loan FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE,
  CONSTRAINT chk_schedule_day_range CHECK (day_index BETWEEN 1 AND 60)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE payment_qrs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  token_hash CHAR(64) NOT NULL,
  payload_version SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  generated_by_user_id BIGINT UNSIGNED NOT NULL,
  customer_id BIGINT UNSIGNED NOT NULL,
  loan_id BIGINT UNSIGNED NOT NULL,
  coverage_mode ENUM('ONE_DAY', 'TWO_DAYS', 'ARREARS_ACCUMULATED', 'CUSTOM') NOT NULL,
  expected_amount DECIMAL(14,2) NOT NULL,
  status ENUM('ACTIVE', 'SCANNED', 'EXPIRED', 'CANCELLED') NOT NULL DEFAULT 'ACTIVE',
  expires_at DATETIME NOT NULL,
  generated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_qr_token_hash (token_hash),
  KEY idx_qr_generator_date (generated_by_user_id, generated_at),
  KEY idx_qr_customer_status (customer_id, status),
  KEY idx_qr_loan_status (loan_id, status),
  CONSTRAINT fk_qr_generator FOREIGN KEY (generated_by_user_id) REFERENCES users(id),
  CONSTRAINT fk_qr_customer FOREIGN KEY (customer_id) REFERENCES customer_profiles(id),
  CONSTRAINT fk_qr_loan FOREIGN KEY (loan_id) REFERENCES loans(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE payment_qr_schedules (
  qr_id BIGINT UNSIGNED NOT NULL,
  schedule_id BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (qr_id, schedule_id),
  KEY idx_qr_schedule_schedule (schedule_id),
  CONSTRAINT fk_qr_schedule_qr FOREIGN KEY (qr_id) REFERENCES payment_qrs(id) ON DELETE CASCADE,
  CONSTRAINT fk_qr_schedule_schedule FOREIGN KEY (schedule_id) REFERENCES loan_schedules(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE payments (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  qr_id BIGINT UNSIGNED NOT NULL,
  customer_id BIGINT UNSIGNED NOT NULL,
  loan_id BIGINT UNSIGNED NOT NULL,
  collected_by_user_id BIGINT UNSIGNED NOT NULL,
  method ENUM('CASH', 'TRANSFER') NOT NULL,
  amount DECIMAL(14,2) NOT NULL,
  transfer_reference VARCHAR(180) NULL,
  status ENUM('PENDING', 'VERIFIED', 'REJECTED', 'REVERSED') NOT NULL DEFAULT 'VERIFIED',
  scanned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  verified_at DATETIME NULL,
  location_lat DECIMAL(10,7) NULL,
  location_lng DECIMAL(10,7) NULL,
  note TEXT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_payment_qr (qr_id),
  KEY idx_payment_customer_date (customer_id, scanned_at),
  KEY idx_payment_loan_date (loan_id, scanned_at),
  KEY idx_payment_collector_date (collected_by_user_id, scanned_at),
  CONSTRAINT fk_payment_qr FOREIGN KEY (qr_id) REFERENCES payment_qrs(id),
  CONSTRAINT fk_payment_customer FOREIGN KEY (customer_id) REFERENCES customer_profiles(id),
  CONSTRAINT fk_payment_loan FOREIGN KEY (loan_id) REFERENCES loans(id),
  CONSTRAINT fk_payment_collector_user FOREIGN KEY (collected_by_user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE payment_allocations (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  payment_id BIGINT UNSIGNED NOT NULL,
  schedule_id BIGINT UNSIGNED NOT NULL,
  amount DECIMAL(14,2) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_payment_schedule (payment_id, schedule_id),
  KEY idx_allocation_schedule (schedule_id),
  CONSTRAINT fk_allocation_payment FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE,
  CONSTRAINT fk_allocation_schedule FOREIGN KEY (schedule_id) REFERENCES loan_schedules(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE customer_daily_snapshots (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  customer_id BIGINT UNSIGNED NOT NULL,
  loan_id BIGINT UNSIGNED NOT NULL,
  snapshot_date DATE NOT NULL,
  arrears_days INT NOT NULL DEFAULT 0,
  streak_days INT NOT NULL DEFAULT 0,
  unpaid_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  risk_color ENUM('BLUE', 'YELLOW', 'RED', 'DARK') NOT NULL DEFAULT 'BLUE',
  paid_today BOOLEAN NOT NULL DEFAULT FALSE,
  due_today BOOLEAN NOT NULL DEFAULT FALSE,
  generated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_snapshot_loan_date (loan_id, snapshot_date),
  KEY idx_snapshot_customer_date (customer_id, snapshot_date),
  KEY idx_snapshot_risk_date (risk_color, snapshot_date),
  CONSTRAINT fk_snapshot_customer FOREIGN KEY (customer_id) REFERENCES customer_profiles(id),
  CONSTRAINT fk_snapshot_loan FOREIGN KEY (loan_id) REFERENCES loans(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE approval_requests (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  entity_type ENUM('CUSTOMER', 'CUSTOMER_UPDATE', 'EMPLOYEE', 'LOAN_APPLICATION', 'ADMIN_USER') NOT NULL,
  entity_id BIGINT UNSIGNED NOT NULL,
  action_type ENUM('CREATE', 'UPDATE', 'DELETE', 'ACTIVATE') NOT NULL,
  status ENUM('PENDING', 'APPROVED', 'REJECTED', 'CANCELLED') NOT NULL DEFAULT 'PENDING',
  requested_by_user_id BIGINT UNSIGNED NOT NULL,
  reviewed_by_user_id BIGINT UNSIGNED NULL,
  payload_snapshot JSON NOT NULL,
  note TEXT NULL,
  reviewed_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_approval_entity (entity_type, entity_id),
  KEY idx_approval_status_created (status, created_at),
  CONSTRAINT fk_approval_requester FOREIGN KEY (requested_by_user_id) REFERENCES users(id),
  CONSTRAINT fk_approval_reviewer FOREIGN KEY (reviewed_by_user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE iot_devices (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  device_code VARCHAR(80) NOT NULL,
  name VARCHAR(160) NOT NULL,
  location VARCHAR(180) NULL,
  status ENUM('ACTIVE', 'MAINTENANCE', 'DISABLED') NOT NULL DEFAULT 'ACTIVE',
  api_key_hash CHAR(64) NOT NULL,
  last_seen_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_iot_device_code (device_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE fingerprint_credentials (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  employee_id BIGINT UNSIGNED NOT NULL,
  device_id BIGINT UNSIGNED NULL,
  fingerprint_template_id VARCHAR(100) NOT NULL,
  active BOOLEAN NOT NULL DEFAULT TRUE,
  enrolled_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_fingerprint_device_template (device_id, fingerprint_template_id),
  KEY idx_fingerprint_employee_active (employee_id, active),
  CONSTRAINT fk_fingerprint_employee FOREIGN KEY (employee_id) REFERENCES employee_profiles(id),
  CONSTRAINT fk_fingerprint_device FOREIGN KEY (device_id) REFERENCES iot_devices(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE attendance_logs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  device_id BIGINT UNSIGNED NOT NULL,
  employee_id BIGINT UNSIGNED NOT NULL,
  fingerprint_credential_id BIGINT UNSIGNED NULL,
  slot ENUM('MORNING_IN', 'NOON_OUT', 'AFTERNOON_IN', 'NIGHT_OUT', 'UNKNOWN') NOT NULL,
  occurred_at DATETIME NOT NULL,
  received_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  raw_payload JSON NOT NULL,
  PRIMARY KEY (id),
  KEY idx_attendance_log_employee_time (employee_id, occurred_at),
  KEY idx_attendance_log_device_time (device_id, occurred_at),
  KEY idx_attendance_log_slot_time (slot, occurred_at),
  CONSTRAINT fk_attendance_log_device FOREIGN KEY (device_id) REFERENCES iot_devices(id),
  CONSTRAINT fk_attendance_log_employee FOREIGN KEY (employee_id) REFERENCES employee_profiles(id),
  CONSTRAINT fk_attendance_log_fingerprint FOREIGN KEY (fingerprint_credential_id) REFERENCES fingerprint_credentials(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE attendance_records (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  employee_id BIGINT UNSIGNED NOT NULL,
  attendance_date DATE NOT NULL,
  morning_in_at DATETIME NULL,
  noon_out_at DATETIME NULL,
  afternoon_in_at DATETIME NULL,
  night_out_at DATETIME NULL,
  status ENUM('COMPLETE', 'PARTIAL', 'ABSENT') NOT NULL DEFAULT 'ABSENT',
  color ENUM('BLUE', 'YELLOW', 'RED') NOT NULL DEFAULT 'RED',
  note TEXT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_attendance_employee_date (employee_id, attendance_date),
  KEY idx_attendance_date_status (attendance_date, status),
  CONSTRAINT fk_attendance_record_employee FOREIGN KEY (employee_id) REFERENCES employee_profiles(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE auth_sessions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  refresh_token_hash CHAR(64) NOT NULL,
  platform ENUM('ANDROID', 'DESKTOP') NOT NULL,
  device_name VARCHAR(180) NULL,
  fcm_token VARCHAR(255) NULL,
  expires_at DATETIME NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_session_refresh_token_hash (refresh_token_hash),
  KEY idx_session_user_expiry (user_id, expires_at),
  CONSTRAINT fk_session_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_logs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  actor_id BIGINT UNSIGNED NULL,
  action VARCHAR(120) NOT NULL,
  entity VARCHAR(120) NOT NULL,
  entity_id BIGINT UNSIGNED NULL,
  metadata JSON NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_audit_entity (entity, entity_id),
  KEY idx_audit_actor_created (actor_id, created_at),
  CONSTRAINT fk_audit_actor FOREIGN KEY (actor_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELIMITER //

CREATE PROCEDURE sp_generate_loan_schedules(
  IN p_loan_id BIGINT UNSIGNED,
  IN p_start_date DATE
)
BEGIN
  DECLARE v_day_index INT DEFAULT 1;
  DECLARE v_cursor_date DATE DEFAULT p_start_date;
  DECLARE v_daily_installment DECIMAL(14,2);

  SELECT daily_installment
  INTO v_daily_installment
  FROM loans
  WHERE id = p_loan_id;

  WHILE v_day_index <= 60 DO
    IF DAYOFWEEK(v_cursor_date) <> 1 THEN
      INSERT INTO loan_schedules (loan_id, day_index, due_date, due_amount)
      VALUES (p_loan_id, v_day_index, v_cursor_date, v_daily_installment);

      SET v_day_index = v_day_index + 1;
    END IF;

    SET v_cursor_date = DATE_ADD(v_cursor_date, INTERVAL 1 DAY);
  END WHILE;

  UPDATE loans
  SET expected_end_date = (
    SELECT MAX(due_date)
    FROM loan_schedules
    WHERE loan_id = p_loan_id
  )
  WHERE id = p_loan_id;
END//

DELIMITER ;

CREATE VIEW v_customer_risk_summary AS
SELECT
  c.id AS customer_id,
  u.name AS customer_name,
  collector.id AS collector_id,
  collector_user.name AS collector_name,
  l.id AS loan_id,
  l.loan_no,
  l.outstanding_amount,
  COALESCE(s.arrears_days, 0) AS arrears_days,
  COALESCE(s.streak_days, 0) AS streak_days,
  CASE
    WHEN COALESCE(s.arrears_days, 0) <= 0 THEN 'BLUE'
    WHEN COALESCE(s.arrears_days, 0) <= 2 THEN 'YELLOW'
    WHEN COALESCE(s.arrears_days, 0) <= 9 THEN 'RED'
    ELSE 'DARK'
  END AS risk_color
FROM customer_profiles c
JOIN users u ON u.id = c.user_id
JOIN employee_profiles collector ON collector.id = c.collector_id
JOIN users collector_user ON collector_user.id = collector.user_id
JOIN loans l ON l.customer_id = c.id AND l.status = 'ACTIVE'
LEFT JOIN customer_daily_snapshots s ON s.loan_id = l.id AND s.snapshot_date = CURRENT_DATE();

CREATE VIEW v_daily_collection_monitoring AS
SELECT
  l.id AS loan_id,
  c.id AS customer_id,
  u.name AS customer_name,
  collector.id AS collector_id,
  collector_user.name AS collector_name,
  ls.due_date,
  ls.due_amount,
  ls.paid_amount,
  ls.status AS schedule_status,
  CASE WHEN p.id IS NULL THEN 0 ELSE 1 END AS scanned_today,
  p.scanned_at
FROM loan_schedules ls
JOIN loans l ON l.id = ls.loan_id
JOIN customer_profiles c ON c.id = l.customer_id
JOIN users u ON u.id = c.user_id
JOIN employee_profiles collector ON collector.id = l.collector_id
JOIN users collector_user ON collector_user.id = collector.user_id
LEFT JOIN payment_allocations pa ON pa.schedule_id = ls.id
LEFT JOIN payments p ON p.id = pa.payment_id AND DATE(p.scanned_at) = CURRENT_DATE()
WHERE ls.due_date = CURRENT_DATE();

CREATE VIEW v_attendance_calendar AS
SELECT
  ar.employee_id,
  u.name AS employee_name,
  ar.attendance_date,
  ar.morning_in_at,
  ar.noon_out_at,
  ar.afternoon_in_at,
  ar.night_out_at,
  ar.status,
  ar.color
FROM attendance_records ar
JOIN employee_profiles e ON e.id = ar.employee_id
JOIN users u ON u.id = e.user_id;
