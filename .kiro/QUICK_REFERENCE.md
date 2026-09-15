# LYDO System - Quick Reference Guide

## Login & Session

### For Users
- **Regular login:** Email + password
- **Stay logged in:** Check "Remember Me" → stays logged in for 30 days
- **Logout:** Click logout → clears all sessions and remember me token
- **Mobile tip:** Always use "Remember Me" to avoid re-login on refresh

### For Admins
- **Shared account:** Multiple staff can use same admin account
  - Go to: `/admin2/set_staff_name.php` → enter staff name
  - All actions tracked individually to staff member
  - View actions: `/admin2/staff_activity_log.php`

## User Registration

### New Youth Member
1. Go to home page
2. Click "Register" or "Get Started"
3. Choose organization:
   - **Option A:** Select existing accredited organization
   - **Option B:** Create new organization (fills in name + category)
4. Complete registration
5. Wait for admin approval
6. Dashboard available after approval

## Organization Accreditation

### For Youth (Organization Members)
1. Login → Dashboard
2. Go to "Accreditation Status"
3. See organization accreditation status
4. If status = "Pending":
   - Gather documents from organization officers
   - Upload required files (Constitution, Officers List, etc.)
5. Wait for admin review (5-7 days)
6. Check dashboard for approval status

### For Admins
1. Go to `/admin2/accreditation_review.php`
2. Default tab = "Pending" organizations
3. For each organization:
   - Click organization name
   - View organization members
   - Download/review uploaded documents
   - Choose action: Approve / Request Revision / Reject
4. Once approved, organization becomes active

## Organization Management

### View/Manage Presidents
**Path:** `/admin2/organization_presidents.php`

**Actions:**
- View all organizations with current president
- Change president (click "Change President" button)
- Select reason: Graduated / Resigned / Transferred / Other
- System tracks all president transitions
- See graduation years to plan annual transitions

### View Organization Members
**Path:** `/admin2/view_organization_members.php?org_id=X`

**Information shown:**
- All members of organization
- Join date
- Approval status
- Role (Member, President, etc.)

## Staff Activity Tracking

### Log Your Actions
1. Go to: `/admin2/set_staff_name.php`
2. Enter your full name (example: "Maria Santos")
3. Click "Set My Name"
4. Now all your admin actions are tracked with your name

### View Activity Log
1. Go to: `/admin2/staff_activity_log.php`
2. See summary of today's and recent activities
3. See detailed log of all staff actions
4. Filter by staff member or date

## File Management

### Upload Documents (Youth)
- Go to Accreditation Status dashboard
- Click "Upload Documents"
- Select required documents:
  - Constitution & Bylaws
  - Officers Directory
  - Members List
  - Financial Report
  - Organizational Chart
  - Mission & Vision Statement
- Upload file (max 5MB)
- System shows upload status

### View Documents (Admin)
- Go to: `/admin2/accreditation_review.php`
- Select organization
- Click "View Files"
- Download or preview each document
- Files stay accessible even after approval

## Events & Check-in

### Create Event
**Path:** `/admin2/events.php`
- Set event name, date, time, location
- Assign organization
- Add event details

### QR Code Check-in
**Path:** `/admin2/event_scan.php` or `/admin2/event_qr.php`
- Generate QR code for event
- Scan with mobile device to check in
- See real-time attendance

### View Attendance
**Path:** `/admin2/events.php`
- Click event name
- See all checked-in members
- Download attendance report
- Generate certificates

## Merit System

### Award Merit Points
- Go to: `/admin2/merit.php`
- Search youth member
- Add points with reason
- Points tracked in system

### View Merit Report
- Go to: `/admin2/merit_report.php`
- See individual and organization merit totals
- Leaderboard rankings
- Export report

## Troubleshooting

### Can't Log In
1. Check email is correct
2. Verify account type (Admin/President/Youth)
3. Reset password if needed: `/forgot_password.php`
4. Check account isn't rejected

### Session Keeps Clearing
1. Check "Remember Me" on login ← This is the fix
2. Allow 30-day persistent login
3. If still clearing: clear browser cookies and try again

### Remember Me Not Working
1. Verify databases tables were created (see DEPLOYMENT_CHECKLIST.md)
2. Check browser cookies are enabled
3. Use incognito mode (don't use private browsing)

### Organization Not in Dropdown
1. Refresh the page
2. Clear browser cache
3. Close/reopen browser
4. Check organization is "active" status

### Can't Find Document Upload
1. Login as youth member
2. Go to Dashboard
3. Click "Accreditation Status"
4. If organization is pending accreditation, upload button appears

## Database

### Common Queries

**Check Remember Me tokens:**
```sql
SELECT * FROM admin_remember_tokens WHERE admin_id = 1;
```

**Check user activity:**
```sql
SELECT * FROM admin_activity_log WHERE staff_member = 'Maria Santos' ORDER BY timestamp DESC LIMIT 10;
```

**Check organization status:**
```sql
SELECT name, accreditation_status FROM organizations ORDER BY created_at DESC;
```

## Emergency Procedures

### Forgot Admin Password
1. Go to: `/admin2/reset_admin_password.php`
2. Enter admin email
3. Check email for reset link
4. Create new password

### Clear User Session (Force Logout)
```sql
-- Get session ID from database if needed
DELETE FROM admin_remember_tokens WHERE admin_id = [admin_id];
-- User will need to login again
```

### Restore Database from Backup
1. Stop application
2. Restore backup SQL file
3. Restart application
4. Verify everything working

## Common Paths & URLs

**Youth:**
- Dashboard: `/shared/youth/dashboard.php`
- Accreditation: `/shared/youth/accreditation_status.php`
- Wellbeing: `/shared/youth/wellbeing.php`
- Logout: `/shared/youth/logout.php`

**Organization President:**
- Dashboard: `/org-president/dashboard.php`
- Members: `/org-president/members.php`
- Events: `/org-president/events.php`
- Logout: `/org-president/logout.php`

**Admin:**
- Dashboard: `/admin2/dashboard.php`
- Organizations: `/admin2/organizations.php`
- Accreditation Review: `/admin2/accreditation_review.php`
- Staff Activity: `/admin2/staff_activity_log.php`
- Presidents: `/admin2/organization_presidents.php`
- Logout: `/admin2/logout.php`

**Public:**
- Login: `/login.php`
- Register: `/index.html` (register section)
- Forgot Password: `/forgot_password.php`

## Documentation Files

**For detailed information, see:**
- `CURRENT_STATUS.md` - System overview and status
- `SESSION_PERSISTENCE_DEPLOYMENT.md` - Mobile session fix details
- `ACCREDITATION_IMPLEMENTATION.md` - Accreditation system details
- `DEPLOYMENT_CHECKLIST.md` - Deployment steps
- `QUICK_REFERENCE.md` - This file

---

**Quick Tip:** Always check "Remember Me" on login to avoid session timeouts on mobile! ✅
