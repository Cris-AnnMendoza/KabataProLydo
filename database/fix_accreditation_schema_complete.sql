-- Complete Accreditation Schema Fix
-- Addresses all schema mismatches between database definition and application code

-- 1. Fix accreditation_applications table
ALTER TABLE accreditation_applications 
DROP FOREIGN KEY IF EXISTS accreditation_applications_ibfk_1,
MODIFY COLUMN submitted_by INT UNSIGNED DEFAULT NULL,
ADD CONSTRAINT accreditation_applications_submitted_by_fk 
  FOREIGN KEY (submitted_by) REFERENCES youth_users(id) ON DELETE SET NULL;

-- 2. Add organization_id column to accreditation_applications
ALTER TABLE accreditation_applications 
ADD COLUMN IF NOT EXISTS organization_id INT UNSIGNED DEFAULT NULL AFTER id,
ADD CONSTRAINT accreditation_applications_org_fk 
  FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE;

-- 3. Fix accreditation_documents table - add missing columns
ALTER TABLE accreditation_documents 
ADD COLUMN IF NOT EXISTS organization_id INT UNSIGNED DEFAULT NULL AFTER application_id,
ADD COLUMN IF NOT EXISTS doc_type VARCHAR(50) DEFAULT NULL AFTER organization_id;

-- 4. Add unique constraint to prevent duplicate document uploads
ALTER TABLE accreditation_documents 
ADD UNIQUE KEY IF NOT EXISTS unique_app_doc (application_id, doc_type);

-- 5. Fix status field in accreditation_documents to have proper default
ALTER TABLE accreditation_documents 
MODIFY COLUMN status VARCHAR(20) NOT NULL DEFAULT 'pending';

-- 6. Create indexes for performance
CREATE INDEX IF NOT EXISTS idx_accred_apps_org ON accreditation_applications(organization_id);
CREATE INDEX IF NOT EXISTS idx_accred_apps_status ON accreditation_applications(status);
CREATE INDEX IF NOT EXISTS idx_accred_apps_submitted ON accreditation_applications(submitted_by);
CREATE INDEX IF NOT EXISTS idx_accred_docs_app ON accreditation_documents(application_id);
CREATE INDEX IF NOT EXISTS idx_accred_docs_org ON accreditation_documents(organization_id);
CREATE INDEX IF NOT EXISTS idx_accred_workflow_app ON accreditation_workflow(application_id);

-- 7. Verify table structure
DESCRIBE accreditation_applications;
DESCRIBE accreditation_documents;
DESCRIBE accreditation_workflow;
