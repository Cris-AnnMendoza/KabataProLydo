-- Remove unnecessary staff_encoder and other role distinctions
-- Keep only: super_admin, admin (shared by staff)

-- Add staff tracking to admin_users table
ALTER TABLE admin_users ADD COLUMN IF NOT EXISTS role ENUM('super_admin','admin','organization_president') NOT NULL DEFAULT 'admin' AFTER email;

-- Simplify existing roles - combine all staff types into 'admin'
UPDATE admin_users SET role = 'admin' WHERE role IN ('youth_coordinator','barangay_admin','staff_encoder');

-- Create staff activity log with staff name tracking
CREATE TABLE IF NOT EXISTS staff_activity_log (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  admin_id INT UNSIGNED NOT NULL,
  staff_name VARCHAR(150) NOT NULL,  -- Name of staff who made the change
  action VARCHAR(255) NOT NULL,      -- What action was taken
  entity_type VARCHAR(100) DEFAULT NULL,  -- What was modified (youth, org, accreditation, etc)
  entity_id INT UNSIGNED DEFAULT NULL,    -- ID of the entity
  details TEXT DEFAULT NULL,         -- Additional details
  ip_address VARCHAR(45) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE CASCADE,
  INDEX idx_staff_name (staff_name),
  INDEX idx_created (created_at DESC),
  INDEX idx_entity (entity_type, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Update admin portal to show staff activity
ALTER TABLE accreditation_submissions ADD COLUMN IF NOT EXISTS reviewed_by_staff_name VARCHAR(150) DEFAULT NULL;

-- Track staff who approve/reject organizations
ALTER TABLE organizations ADD COLUMN IF NOT EXISTS approved_by_staff_name VARCHAR(150) DEFAULT NULL;
ALTER TABLE organizations ADD COLUMN IF NOT EXISTS approved_at DATETIME DEFAULT NULL;

-- Track staff who change president
ALTER TABLE organization_president_history ADD COLUMN IF NOT EXISTS changed_by_staff_name VARCHAR(150) DEFAULT NULL;
