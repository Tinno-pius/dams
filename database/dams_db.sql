-- =============================================================
--  DAMS - Digital Antenatal Monitoring System
--  Database: dams_db
--
--  HOW TO USE (XAMPP):
--   1. Start Apache and MySQL in the XAMPP control panel.
--   2. Open http://localhost/phpmyadmin
--   3. Create a new database called  dams_db
--   4. Select it, click "Import" and choose this file.
--
--  The file creates every table and adds a few sample accounts
--  so you can log in and test straight away.
-- =============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS `dams_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `dams_db`;

-- -------------------------------------------------------------
-- 1. USERS  (Admin, Health Worker / Nurse, Patient)
-- -------------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `full_name`  VARCHAR(150) NOT NULL,
  `email`      VARCHAR(150) NOT NULL UNIQUE,
  `phone`      VARCHAR(20)  DEFAULT NULL,
  `password`   VARCHAR(255) NOT NULL,           -- stored as a password hash
  `role`       ENUM('admin','healthworker','patient') NOT NULL DEFAULT 'patient',
  `status`     ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- 2. PATIENTS  (Tab 1 of the RCH4 card - Habari za Mama)
-- -------------------------------------------------------------
DROP TABLE IF EXISTS `patients`;
CREATE TABLE `patients` (
  `id`                   INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`              INT DEFAULT NULL,                 -- link to the patient login account
  `registration_number` VARCHAR(50) NOT NULL UNIQUE,
  `clinic_name`          VARCHAR(150) DEFAULT NULL,
  `discount_card_number` VARCHAR(50)  DEFAULT NULL,
  -- Mother information
  `full_name`            VARCHAR(150) NOT NULL,
  `phone`                VARCHAR(20)  DEFAULT NULL,
  `age`                  INT          DEFAULT NULL,
  `height_cm`            DECIMAL(5,1) DEFAULT NULL,
  `education`            VARCHAR(100) DEFAULT NULL,
  `occupation`           VARCHAR(100) DEFAULT NULL,
  `height_category`      VARCHAR(50)  DEFAULT NULL,        -- above / below 150cm
  -- Husband / partner information
  `partner_name`         VARCHAR(150) DEFAULT NULL,
  `partner_age`          INT          DEFAULT NULL,
  `partner_education`    VARCHAR(100) DEFAULT NULL,
  `partner_occupation`   VARCHAR(100) DEFAULT NULL,
  `village`              VARCHAR(150) DEFAULT NULL,
  `chairperson_name`     VARCHAR(150) DEFAULT NULL,
  `district`             VARCHAR(100) DEFAULT NULL,
  -- Previous pregnancy information
  `gravida`              INT DEFAULT NULL,
  `parity`               INT DEFAULT NULL,
  `living_children`      INT DEFAULT NULL,
  -- Current pregnancy
  `lnmp`                 DATE DEFAULT NULL,                -- last normal menstrual period
  `edd`                  DATE DEFAULT NULL,                -- expected date of delivery
  `risk_status`          ENUM('low','high') NOT NULL DEFAULT 'low',
  `registered_by`        INT DEFAULT NULL,                 -- health worker user id
  `created_at`           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_patient_user`     FOREIGN KEY (`user_id`)       REFERENCES `users`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_patient_nurse`    FOREIGN KEY (`registered_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- 3. RCH4 CARDS  (one card per patient - card level info)
-- -------------------------------------------------------------
DROP TABLE IF EXISTS `rch4_cards`;
CREATE TABLE `rch4_cards` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `patient_id`    INT NOT NULL,
  `card_number`   VARCHAR(50) NOT NULL,
  -- PMTCT section
  `art_status`    VARCHAR(100) DEFAULT NULL,
  `drug_regimen`  VARCHAR(100) DEFAULT NULL,
  `ctx`           VARCHAR(100) DEFAULT NULL,
  `ctc_number`    VARCHAR(50)  DEFAULT NULL,
  `infant_feeding` VARCHAR(100) DEFAULT NULL,
  `adherence`     VARCHAR(50)  DEFAULT NULL,
  `family_planning` VARCHAR(150) DEFAULT NULL,
  `birth_preparedness` VARCHAR(200) DEFAULT NULL,
  `sti_counselling` VARCHAR(200) DEFAULT NULL,
  -- Appointment / signing details
  `return_date`   DATE DEFAULT NULL,
  `worker_name`   VARCHAR(150) DEFAULT NULL,
  `worker_position` VARCHAR(100) DEFAULT NULL,
  `worker_signature` VARCHAR(150) DEFAULT NULL,
  `created_by`    INT DEFAULT NULL,
  `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_card_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- 4. MISCARRIAGE HISTORY  (multiple records per patient)
-- -------------------------------------------------------------
DROP TABLE IF EXISTS `miscarriage_history`;
CREATE TABLE `miscarriage_history` (
  `id`              INT AUTO_INCREMENT PRIMARY KEY,
  `patient_id`      INT NOT NULL,
  `year`            VARCHAR(10)  DEFAULT NULL,
  `gestational_age` VARCHAR(30)  DEFAULT NULL,
  CONSTRAINT `fk_misc_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- 5. RISK ASSESSMENT  (Tab 2 - Chunguza / Dalili)
--    Each column is a danger sign or referral condition (0/1).
-- -------------------------------------------------------------
DROP TABLE IF EXISTS `risk_assessment`;
CREATE TABLE `risk_assessment` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `patient_id` INT NOT NULL,
  -- Section A: refer for further check-up
  `a_age_below_20`        TINYINT(1) DEFAULT 0,
  `a_ten_years_gap`       TINYINT(1) DEFAULT 0,
  `a_previous_caesarean`  TINYINT(1) DEFAULT 0,
  `a_previous_stillbirth` TINYINT(1) DEFAULT 0,
  `a_multiple_miscarriage` TINYINT(1) DEFAULT 0,
  `a_heart_disease`       TINYINT(1) DEFAULT 0,
  `a_diabetes`            TINYINT(1) DEFAULT 0,
  `a_tuberculosis`        TINYINT(1) DEFAULT 0,
  -- Section B: advise hospital delivery
  `b_fourth_pregnancy`    TINYINT(1) DEFAULT 0,
  `b_first_above_35`      TINYINT(1) DEFAULT 0,
  `b_height_below_150`    TINYINT(1) DEFAULT 0,
  `b_previous_vacuum`     TINYINT(1) DEFAULT 0,
  `b_pelvic_deformity`    TINYINT(1) DEFAULT 0,
  `b_postpartum_haemorrhage` TINYINT(1) DEFAULT 0,
  `b_retained_placenta`   TINYINT(1) DEFAULT 0,
  -- Section C: danger signs (any of these = HIGH RISK)
  `c_bp_high`             TINYINT(1) DEFAULT 0,
  `c_over_40_weeks`       TINYINT(1) DEFAULT 0,
  `c_hb_below_85`         TINYINT(1) DEFAULT 0,
  `c_reduced_movement`    TINYINT(1) DEFAULT 0,
  `c_albumin_urine`       TINYINT(1) DEFAULT 0,
  `c_bad_position`        TINYINT(1) DEFAULT 0,
  `c_sugar_urine`         TINYINT(1) DEFAULT 0,
  `c_leg_oedema`          TINYINT(1) DEFAULT 0,
  `c_twins`               TINYINT(1) DEFAULT 0,
  `c_abnormal_fundal`     TINYINT(1) DEFAULT 0,
  `advised_delivery_place` VARCHAR(200) DEFAULT NULL,
  `is_high_risk`          TINYINT(1) DEFAULT 0,
  `assessed_by`           INT DEFAULT NULL,
  `created_at`            TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_risk_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- 6. LABORATORY RESULTS
-- -------------------------------------------------------------
DROP TABLE IF EXISTS `laboratory_results`;
CREATE TABLE `laboratory_results` (
  `id`             INT AUTO_INCREMENT PRIMARY KEY,
  `patient_id`     INT NOT NULL,
  `blood_group`    VARCHAR(10)  DEFAULT NULL,
  `syphilis_status` VARCHAR(50) DEFAULT NULL,
  `hb_level`       DECIMAL(4,1) DEFAULT NULL,
  `other_results`  TEXT DEFAULT NULL,
  `recorded_by`    INT DEFAULT NULL,
  `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_lab_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- 7. ANC VISITS  (Tab 3 - Rekodi ya Mahudhurio, 4 visits)
-- -------------------------------------------------------------
DROP TABLE IF EXISTS `anc_visits`;
CREATE TABLE `anc_visits` (
  `id`                INT AUTO_INCREMENT PRIMARY KEY,
  `patient_id`        INT NOT NULL,
  `visit_number`      INT NOT NULL,                 -- 1..4 (or more after week 40)
  `visit_date`        DATE DEFAULT NULL,
  `weight`            DECIMAL(5,1) DEFAULT NULL,
  `blood_pressure`    VARCHAR(20)  DEFAULT NULL,
  `urine_albumin`     VARCHAR(20)  DEFAULT NULL,
  `hb_level`          DECIMAL(4,1) DEFAULT NULL,
  `urine_sugar`       VARCHAR(20)  DEFAULT NULL,
  `gestational_age`   VARCHAR(20)  DEFAULT NULL,
  `fundal_height`     VARCHAR(20)  DEFAULT NULL,
  `fetal_position`    VARCHAR(50)  DEFAULT NULL,
  `presentation`      VARCHAR(50)  DEFAULT NULL,
  `fetal_movement`    VARCHAR(20)  DEFAULT NULL,
  `fetal_heart_rate`  VARCHAR(20)  DEFAULT NULL,
  `leg_oedema`        VARCHAR(20)  DEFAULT NULL,
  `ferrous_sulphate`  VARCHAR(20)  DEFAULT NULL,
  `folic_acid`        VARCHAR(20)  DEFAULT NULL,
  `sp_dose`           VARCHAR(20)  DEFAULT NULL,
  `mebendazole`       VARCHAR(20)  DEFAULT NULL,
  `tt_vaccine`        VARCHAR(20)  DEFAULT NULL,
  `next_appointment`  DATE DEFAULT NULL,
  `recorded_by`       INT DEFAULT NULL,
  `created_at`        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_visit_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- 8. APPOINTMENTS
-- -------------------------------------------------------------
DROP TABLE IF EXISTS `appointments`;
CREATE TABLE `appointments` (
  `id`               INT AUTO_INCREMENT PRIMARY KEY,
  `patient_id`       INT NOT NULL,
  `appointment_date` DATE NOT NULL,
  `reason`           VARCHAR(200) DEFAULT 'ANC Visit',
  `status`           ENUM('scheduled','rescheduled','cancelled','completed') NOT NULL DEFAULT 'scheduled',
  `reminder_sent`    TINYINT(1) DEFAULT 0,
  `created_by`       INT DEFAULT NULL,
  `created_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_appt_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- 9. NOTIFICATIONS
-- -------------------------------------------------------------
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT NOT NULL,
  `title`      VARCHAR(150) NOT NULL,
  `message`    TEXT NOT NULL,
  `is_read`    TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- 10. HEALTH ARTICLES  (health education)
-- -------------------------------------------------------------
DROP TABLE IF EXISTS `health_articles`;
CREATE TABLE `health_articles` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `title`      VARCHAR(200) NOT NULL,
  `body`       TEXT NOT NULL,
  `image`      VARCHAR(200) DEFAULT NULL,
  `created_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- 11. SMS LOGS
-- -------------------------------------------------------------
DROP TABLE IF EXISTS `sms_logs`;
CREATE TABLE `sms_logs` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `patient_id` INT DEFAULT NULL,
  `phone`      VARCHAR(20) NOT NULL,
  `message`    TEXT NOT NULL,
  `provider`   VARCHAR(50) DEFAULT NULL,
  `status`     VARCHAR(50) DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- 12. SETTINGS  (key/value system settings)
-- -------------------------------------------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key`   VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` TEXT DEFAULT NULL
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================
--  SAMPLE DATA
--  All sample passwords are:  password123
--  (they are stored as PHP password_hash values)
-- =============================================================

-- password123
INSERT INTO `users` (`full_name`, `email`, `phone`, `password`, `role`, `status`) VALUES
('System Administrator', 'admin@dams.com',        '0700000001', '$2y$10$3otDkG0Hq.1kDW6urXKQDOWYeX2Q6u.iYWIS8YoxtLe8le7x5evUa', 'admin',        'active'),
('Nurse Amina Juma',     'healthworker@clinic.com','0700000002', '$2y$10$3otDkG0Hq.1kDW6urXKQDOWYeX2Q6u.iYWIS8YoxtLe8le7x5evUa', 'healthworker', 'active'),
('Neema Patient',        'patient@gmail.com',      '0700000003', '$2y$10$3otDkG0Hq.1kDW6urXKQDOWYeX2Q6u.iYWIS8YoxtLe8le7x5evUa', 'patient',      'active');

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('clinic_name',   'Mwananyamala Health Centre'),
('system_language','en'),
('sms_provider',  'africastalking');

INSERT INTO `health_articles` (`title`, `body`, `created_by`) VALUES
('Eat Well During Pregnancy', 'Eat a balanced diet with fruits, vegetables, proteins and enough water every day to keep you and your baby healthy.', 1),
('Attend All ANC Visits', 'Please attend all four antenatal clinic visits so the nurse can monitor the health of you and your baby.', 1),
('Know the Danger Signs', 'Report to the clinic immediately if you notice bleeding, severe headache, blurred vision, swelling of the legs or reduced baby movement.', 1);
