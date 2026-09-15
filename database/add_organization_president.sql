-- Add organization president tracking and history
ALTER TABLE organizations ADD COLUMN IF NOT EXISTS president_id INT UNSIGNED DEFAULT NULL;
ALTER TABLE organizations ADD COLUMN IF NOT EXISTS president_since DATE DEFAULT NULL;
ALTER TABLE organizations ADD FOREIGN KEY (president_id) REFERENCES youth_users(id) ON DELETE SET NULL;

-- Track president change history
CREATE TABLE IF NOT EXISTS organization_president_history (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  organization_id INT UNSIGNED NOT NULL,
  president_id INT UNSIGNED NOT NULL,
  started_at DATE NOT NULL,
  ended_at DATE DEFAULT NULL,
  reason VARCHAR(255) DEFAULT NULL,  -- 'graduated', 'resigned', 'transferred', 'changed', etc.
  changed_by INT UNSIGNED DEFAULT NULL,  -- admin or outgoing president
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
  FOREIGN KEY (president_id) REFERENCES youth_users(id) ON DELETE CASCADE,
  INDEX idx_org_president (organization_id, started_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Track when youth graduate (school year end)
ALTER TABLE youth_users ADD COLUMN IF NOT EXISTS graduation_year INT DEFAULT NULL;
ALTER TABLE youth_users ADD COLUMN IF NOT EXISTS educational_level VARCHAR(100) DEFAULT NULL;
