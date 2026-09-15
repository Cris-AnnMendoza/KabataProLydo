# Organization Accreditation System Implementation

## Overview
Implemented a hybrid organization accreditation workflow that requires youth members to have an organization while supporting both existing accredited organizations and pending organization creation.

## Key Features

### 1. Youth Registration Flow
**Scenario A: Select Existing Accredited Organization**
- Youth selects from dropdown of active organizations
- Auto-added as member upon registration
- Account pending approval by admin

**Scenario B: Create New Organization (Not Yet Accredited)**
- Youth fills in organization name and category
- New organization created with `accreditation_status = 'pending'`
- Youth auto-added as member
- Organization appears in admin review queue
- Youth receives registration message with next steps

### 2. Organization Member Experience
**Dashboard: `/shared/youth/accreditation_status.php`**
- View organization details and accreditation status
- See timeline of 4-step accreditation process
- Upload required documents:
  - Constitution & Bylaws
  - Officers Directory
  - Members List
  - Financial Report
  - Organizational Chart
  - Mission & Vision Statement
- Track submission status (pending, approved, needs_revision, rejected)
- Access admin comments if revision needed

**Next Steps for Youth:**
1. Register with new organization
2. Gather required documents from organization officers
3. Upload documents through dashboard
4. LYDO Admin reviews (5-7 business days)
5. Receive approval/revision request
6. Once approved, organization becomes active

### 3. Admin Review Interface
**Dashboard: `/admin2/accreditation_review.php`**

**Three tabs:**
- **Pending (Default)** - Organizations awaiting approval or resubmission
- **Approved** - Accredited organizations
- **Rejected** - Organizations rejected during review

**Admin Actions:**
- **Approve** - Mark organization as accredited (status → active)
- **Request Revision** - Add comments and send back to youth
- **Reject** - Deny accreditation with reason

**Document Management:**
- View all uploaded files via `/admin2/view_accreditation_files.php`
- Download or preview documents
- Files accessible even after organization is approved
- Files stored in: `/shared/uploads/accreditation/{org_id}/`

**Member Verification:**
- View organization members via `/admin2/view_organization_members.php`
- See member details, join date, approval status
- Verify member count matches submitted list

## Database Changes

### New Tables
1. **organization_accreditation_files**
   - Tracks uploaded documents per organization
   - Stores file path, size, upload date
   - One record per file type (keeps latest version)

2. **accreditation_submissions**
   - Tracks submission history
   - Stores submission status and admin comments
   - Links to organizations and youth users

### Updated Tables
**organizations**
- Added `accreditation_status` (pending/active/rejected/suspended)
- Added `president_id` and `created_by` fields

**organization_members**
- Existing table, now primary membership model
- Links youth to organizations regardless of org status

## File Uploads Implementation

### File Storage
- Location: `/shared/uploads/accreditation/{org_id}/`
- Naming: `{file_type}_{timestamp}_{random}.{ext}`
- File types: PDF, DOC, DOCX, XLS, XLSX, JPG, PNG
- Max size: 5MB per file

### Admin Access
- Files persist after approval
- Admin can view/download at any time
- No file deletion after approval
- Audit trail maintained via database records

## Implementation Steps for Setup

1. **Apply Database Migration:**
   ```sql
   -- Run: database/add_accreditation_status.sql
   -- Creates new tables and columns
   ```

2. **Directory Creation:**
   ```bash
   mkdir -p shared/uploads/accreditation
   chmod 755 shared/uploads/accreditation
   ```

3. **Register youth member:**
   - Choose "Create New Organization" option
   - Fill organization details
   - System creates pending org

4. **Youth actions:**
   - Login to dashboard
   - Navigate to `/shared/youth/accreditation_status.php`
   - Upload required documents

5. **Admin review:**
   - Login to admin panel
   - Go to `/admin2/accreditation_review.php`
   - Review documents and member list
   - Approve/reject/request revision

## User Messages

### During Registration
**New Organization:**
```
"Registration submitted! Your account is pending approval. Your organization is also pending accreditation. 

Next Steps: 
1) You will receive an email with instructions to submit your organization's required documents 
(Constitution, Officers List, Financial Report, etc.) to the LYDO Office. 
2) Please gather these documents from your organization officers and upload them through your dashboard. 
3) The LYDO Admin will review and approve your organization. You will be notified of the status."
```

**Existing Organization:**
```
"Registration submitted! Your account is pending approval by the LYDO office. You will be notified once approved."
```

### Dashboard Status Messages
- **Pending**: "Your organization is pending accreditation. Please upload the required documents..."
- **Needs Revision**: "⚠️ Needs Revision: [Admin comments]"
- **Rejected**: "❌ Rejected: [Reason]. Please contact LYDO Office..."
- **Active**: "✓ Accredited: Your organization is officially accredited..."

## API Endpoints

### Youth Upload
**POST** `/shared/youth/accreditation_upload.php`
```json
{
  "action": "submit_files",
  "file_types": ["constitution_bylaws", "officers_directory", ...],
  "[file_type]": <file>
}
```

**GET** `/shared/youth/accreditation_upload.php?action=get_status`
- Returns organization, submission status, uploaded files

### Admin Views
- `/admin2/accreditation_review.php` - Main review dashboard
- `/admin2/view_accreditation_files.php?org_id=X` - View files for org
- `/admin2/view_organization_members.php?org_id=X` - View members for org

## Security Considerations

1. **File Validation**
   - Only approved file types allowed
   - Size limit enforced (5MB)
   - Stored outside web root where possible

2. **Access Control**
   - Admin only: review dashboard, file viewing
   - Youth: only view own organization status
   - File downloads logged via database

3. **Status Tracking**
   - All changes tracked in accreditation_submissions
   - Admin review timestamps maintained
   - Comment history preserved

## Future Enhancements

- Email notifications when review complete
- Automated reminders for pending submissions
- Batch renewal process for expired accreditations
- Audit trail reports
- Integration with organizational profiles
- Document templates for download
