-- Fix President Accreditation Schema
-- Add missing columns and constraints to accreditation tables

-- 1. Add organization_id to accreditation_applications if missing
ALTER TABLE accreditation_applications 
ADD COLUMN IF NOT EXISTS organization_id INT UNSIGNED NULL AFTER id;

-- 2. Add unique constraint for organization+doc_type to prevent duplicates
ALTER TABLE accreditation_documents 
ADD UNIQUE KEY unique_app_doctype (application_id, doc_type);

-- 3. Add organization_id to accreditation_documents if missing
ALTER TABLE accreditation_documents 
ADD COLUMN IF NOT EXISTS organization_id INT UNSIGNED NULL AFTER application_id;

-- 4. Ensure accreditation_documents has correct columns
ALTER TABLE accreditation_documents 
ADD COLUMN IF NOT EXISTS doc_type VARCHAR(50) NULL AFTER organization_id,
ADD COLUMN IF NOT EXISTS original_name VARCHAR(255) NULL AFTER file_path,
ADD COLUMN IF NOT EXISTS file_size INT NULL AFTER original_name,
ADD COLUMN IF NOT EXISTS status VARCHAR(20) NOT NULL DEFAULT 'pending' AFTER file_size;

-- 5. Add foreign key constraint for organization_id in accreditation_applications
-- Remove old constraint if exists
ALTER TABLE accreditation_applications 
DROP FOREIGN KEY IF EXISTS accreditation_applications_ibfk_2,
ADD CONSTRAINT accreditation_applications_org_fk 
  FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE;

-- 6. Make submitted_by nullable to support president submissions
ALTER TABLE accreditation_applications 
MODIFY COLUMN submitted_by INT UNSIGNED DEFAULT NULL;

-- 7. Create index for faster lookups
CREATE INDEX idx_accred_org ON accreditation_applications(organization_id);
CREATE INDEX idx_accred_status ON accreditation_applications(status);
CREATE INDEX idx_accred_docs_app ON accreditation_documents(application_id);

-- 8. Verify tables are properly configured
DESCRIBE accreditation_applications;
DESCRIBE accreditation_documents;
