-- Add accreditation status and document tracking to organizations table
ALTER TABLE organizations ADD COLUMN IF NOT EXISTS accreditation_status 
  ENUM('pending','active','rejected','suspended') NOT NULL DEFAULT 'active';

ALTER TABLE organizations ADD COLUMN IF NOT EXISTS president_id INT UNSIGNED DEFAULT NULL;

ALTER TABLE organizations ADD COLUMN IF NOT EXISTS created_by INT UNSIGNED DEFAULT NULL;

-- Create table for tracking organization accreditation documents
CREATE TABLE IF NOT EXISTS organization_accreditation_files (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  organization_id   INT UNSIGNED NOT NULL,
  file_type         VARCHAR(100) NOT NULL,  -- constitution, officers_list, financial_report, etc.
  original_filename VARCHAR(255) NOT NULL,
  file_path         VARCHAR(500) NOT NULL,
  file_size         INT UNSIGNED NOT NULL,
  uploaded_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
  INDEX idx_org_id (organization_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create table for accreditation submission tracking
CREATE TABLE IF NOT EXISTS accreditation_submissions (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  organization_id   INT UNSIGNED NOT NULL,
  submitted_by      INT UNSIGNED NOT NULL,  -- youth_user id
  status            ENUM('pending','approved','rejected','needs_revision') NOT NULL DEFAULT 'pending',
  submission_date   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  reviewed_by       INT UNSIGNED DEFAULT NULL,  -- admin_user id
  review_date       DATETIME DEFAULT NULL,
  review_comments   TEXT DEFAULT NULL,
  FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
  FOREIGN KEY (submitted_by) REFERENCES youth_users(id) ON DELETE CASCADE,
  INDEX idx_status (status),
  INDEX idx_org_date (organization_id, submission_date DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
