-- Accreditation tables
CREATE TABLE IF NOT EXISTS accreditation_applications (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  organization_name VARCHAR(200) NOT NULL,
  category VARCHAR(100),
  barangay VARCHAR(100),
  contact_person VARCHAR(150),
  contact_email VARCHAR(191),
  contact_phone VARCHAR(30),
  submitted_by INT UNSIGNED NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'submitted',
  certificate_no VARCHAR(50) UNIQUE,
  valid_until DATE,
  reviewed_by INT UNSIGNED,
  reviewed_at TIMESTAMP NULL,
  rejection_reason TEXT,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (submitted_by) REFERENCES youth_users(id) ON DELETE CASCADE,
  FOREIGN KEY (reviewed_by) REFERENCES admin_users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS accreditation_documents (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  application_id INT UNSIGNED NOT NULL,
  doc_type VARCHAR(50) NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  file_size INT DEFAULT 0,
  status VARCHAR(20) NOT NULL DEFAULT 'pending',
  reviewed_by INT UNSIGNED,
  reviewed_at TIMESTAMP NULL,
  uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (application_id) REFERENCES accreditation_applications(id) ON DELETE CASCADE,
  FOREIGN KEY (reviewed_by) REFERENCES admin_users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS accreditation_workflow (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  application_id INT UNSIGNED NOT NULL,
  step VARCHAR(100) NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'pending',
  done_at TIMESTAMP NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (application_id) REFERENCES accreditation_applications(id) ON DELETE CASCADE
);

-- Assistance tables
CREATE TABLE IF NOT EXISTS assistance_requests (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  organization_id INT UNSIGNED NOT NULL,
  submitted_by INT UNSIGNED NOT NULL,
  request_type VARCHAR(100) NOT NULL,
  title VARCHAR(200) NOT NULL,
  description TEXT NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'pending',
  scheduled_date DATE,
  decline_reason TEXT,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
  FOREIGN KEY (submitted_by) REFERENCES youth_users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS assistance_documents (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  request_id INT UNSIGNED NOT NULL,
  doc_type VARCHAR(50) NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  file_size INT DEFAULT 0,
  uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (request_id) REFERENCES assistance_requests(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS assistance_timeline (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  request_id INT UNSIGNED NOT NULL,
  status VARCHAR(100) NOT NULL,
  note TEXT,
  done_by INT UNSIGNED,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (request_id) REFERENCES assistance_requests(id) ON DELETE CASCADE,
  FOREIGN KEY (done_by) REFERENCES admin_users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS assistance_comments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  request_id INT UNSIGNED NOT NULL,
  author_id INT UNSIGNED NOT NULL,
  author_type VARCHAR(20) NOT NULL,
  comment TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (request_id) REFERENCES assistance_requests(id) ON DELETE CASCADE
);

-- Contact messages table
CREATE TABLE IF NOT EXISTS contact_messages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  email VARCHAR(191) NOT NULL,
  subject VARCHAR(255) NOT NULL,
  message TEXT NOT NULL,
  status VARCHAR(20) DEFAULT 'new',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Volunteer tables
CREATE TABLE IF NOT EXISTS volunteer_programs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  description TEXT,
  start_date DATE NOT NULL,
  end_date DATE,
  location VARCHAR(200),
  slots INT,
  status VARCHAR(20) NOT NULL DEFAULT 'open',
  requirements TEXT,
  created_by INT UNSIGNED,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS volunteer_registrations (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  program_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'pending',
  application_note TEXT,
  admin_note TEXT,
  reviewed_by INT UNSIGNED,
  reviewed_at TIMESTAMP NULL,
  registered_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE(program_id, user_id),
  FOREIGN KEY (program_id) REFERENCES volunteer_programs(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES youth_users(id) ON DELETE CASCADE,
  FOREIGN KEY (reviewed_by) REFERENCES admin_users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS volunteer_hours (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  registration_id INT UNSIGNED NOT NULL,
  hours DECIMAL(5,2) NOT NULL DEFAULT 0,
  date DATE NOT NULL,
  notes TEXT,
  verified_by INT UNSIGNED,
  verified_at TIMESTAMP NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (registration_id) REFERENCES volunteer_registrations(id) ON DELETE CASCADE,
  FOREIGN KEY (verified_by) REFERENCES admin_users(id) ON DELETE SET NULL
);

-- Scholarship tables
CREATE TABLE IF NOT EXISTS scholarship_programs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  description TEXT,
  type VARCHAR(50),
  amount DECIMAL(10,2),
  slots INT,
  requirements TEXT,
  deadline DATE,
  status VARCHAR(20) NOT NULL DEFAULT 'open',
  created_by INT UNSIGNED,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS scholarship_applications (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  program_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'pending',
  gwa DECIMAL(3,2),
  family_income DECIMAL(10,2),
  essay TEXT,
  documents TEXT,
  reviewed_by INT UNSIGNED,
  reviewed_at TIMESTAMP NULL,
  admin_notes TEXT,
  applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE(program_id, user_id),
  FOREIGN KEY (program_id) REFERENCES scholarship_programs(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES youth_users(id) ON DELETE CASCADE,
  FOREIGN KEY (reviewed_by) REFERENCES admin_users(id) ON DELETE SET NULL
);
