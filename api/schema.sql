-- Schema for attendance REST API

CREATE TABLE branches (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  code VARCHAR(50) NOT NULL UNIQUE,
  address VARCHAR(255),
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX (is_active)
);

CREATE TABLE employees (
  id INT AUTO_INCREMENT PRIMARY KEY,
  branch_id INT NOT NULL,
  employee_code VARCHAR(50) NOT NULL UNIQUE,
  name VARCHAR(150) NOT NULL,
  phone VARCHAR(50),
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  face_template_hash VARCHAR(255),
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_emp_branch FOREIGN KEY (branch_id) REFERENCES branches(id)
);

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('super_admin','branch_manager') NOT NULL,
  branch_id INT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_user_branch FOREIGN KEY (branch_id) REFERENCES branches(id)
);

CREATE TABLE devices (
  id INT AUTO_INCREMENT PRIMARY KEY,
  branch_id INT NOT NULL,
  name VARCHAR(150) NOT NULL,
  device_token VARCHAR(128) NOT NULL UNIQUE,
  api_secret VARCHAR(128) NOT NULL,
  last_ip VARCHAR(64),
  last_seen_at DATETIME,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_dev_branch FOREIGN KEY (branch_id) REFERENCES branches(id)
);

CREATE TABLE attendance_logs (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  employee_id INT NOT NULL,
  branch_id INT NOT NULL,
  device_id INT NOT NULL,
  event_type ENUM('check_in','check_out','auto') NOT NULL,
  marked_at DATETIME NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  source ENUM('device','manual') NOT NULL DEFAULT 'device',
  meta JSON NULL,
  CONSTRAINT fk_log_emp FOREIGN KEY (employee_id) REFERENCES employees(id),
  CONSTRAINT fk_log_branch FOREIGN KEY (branch_id) REFERENCES branches(id),
  CONSTRAINT fk_log_device FOREIGN KEY (device_id) REFERENCES devices(id),
  INDEX (branch_id, marked_at),
  INDEX (employee_id, marked_at)
);

CREATE TABLE attendance_rules (
  id INT AUTO_INCREMENT PRIMARY KEY,
  branch_id INT NOT NULL,
  workday_start TIME NULL,
  workday_end TIME NULL,
  half_day_threshold INT NULL,
  late_after TIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_rules_branch FOREIGN KEY (branch_id) REFERENCES branches(id)
);
