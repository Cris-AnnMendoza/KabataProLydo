# Organization Accreditation Debug Scripts

Two debug scripts have been created to query the database and check organization accreditation status.

## Scripts Created

### 1. **test_orgs_debug.php** (Command Line Version)
- **Location:** `/test_orgs_debug.php`
- **Purpose:** CLI-based script for debugging via terminal/PowerShell
- **Usage:**
  ```powershell
  C:\xampp\php\php.exe test_orgs_debug.php
  ```
- **Requirements:** MySQL must be running locally
- **Output:** Plain text console output

### 2. **test_orgs_debug_web.php** (Web Browser Version)
- **Location:** `/test_orgs_debug_web.php`
- **Purpose:** Web-based script with formatted HTML output, easier to read
- **Usage:** 
  - Open in browser: `http://localhost/test_orgs_debug_web.php`
  - Or through XAMPP web interface
- **Requirements:** Apache/Web server and MySQL must be running
- **Output:** Formatted HTML with tables and statistics

## What These Scripts Check

### 1. **Total Organizations Count**
- Queries the database for total number of organizations
- Shows raw count

### 2. **Active Organizations Count**
- Counts organizations with `accreditation_status = 'active'`
- Shows both count and percentage of total

### 3. **All Unique Accreditation Status Values**
- Lists all distinct status values currently in the database
- Shows count for each status
- Shows percentage distribution

### 4. **Sample Organizations by Status**
- For each status type, shows 3-5 sample organizations
- Displays:
  - Organization ID
  - Organization Name
  - Barangay
  - Category (if available)
  - Accreditation Status

## Expected Output Example

```
=== ORGANIZATION ACCREDITATION DEBUG ===

1. TOTAL ORGANIZATIONS COUNT:
-----------------------------------
Total Organizations: 45

2. ORGANIZATIONS WITH accreditation_status='active':
-----------------------------------
Active Organizations: 18

3. ALL UNIQUE ACCREDITATION_STATUS VALUES:
-----------------------------------
  - 'active': 18 organization(s)
  - 'pending': 20 organization(s)
  - 'rejected': 5 organization(s)
  - 'needs_revision': 2 organization(s)

4. SAMPLE ORGANIZATIONS BY STATUS:

Status: 'active'
  - ID: 1, Name: Kabata Youth Group, Barangay: Santa Cruz
  - ID: 5, Name: Community Development Org, Barangay: San Isidro
  - ID: 12, Name: Youth Leaders Association, Barangay: Pacita
  
Status: 'pending'
  - ID: 2, Name: Local Sports Club, Barangay: Santa Cruz
  - ID: 8, Name: Environmental Youth Group, Barangay: Real
  ...

=== END DEBUG ===
```

## How to Use

### Option 1: Web Browser (Recommended)
1. Make sure XAMPP is running (Apache + MySQL)
2. Open your browser
3. Navigate to: `http://localhost/your-project-path/test_orgs_debug_web.php`
4. View formatted results with tables and statistics

### Option 2: Command Line
1. Make sure XAMPP MySQL is running
2. Open PowerShell
3. Navigate to project directory
4. Run:
   ```powershell
   C:\xampp\php\php.exe test_orgs_debug.php
   ```
5. View plain text output

## Database Connection Requirements

Both scripts use the same database configuration from `shared/config.php`:
- **Development:** Looks for local MySQL on `127.0.0.1:3306`
- **Production:** Uses Railway environment variables (DATABASE_URL)
- **Database Name:** `local_youth_development_db` (local) or `railway` (production)
- **User:** `root` (local)

## Troubleshooting

### "Database Connection Failed" Error
- **Cause:** MySQL is not running
- **Fix:** 
  1. Open XAMPP Control Panel
  2. Click "Start" next to MySQL
  3. Wait for green indicator
  4. Run script again

### "No connection could be made" Error
- **Cause:** MySQL service isn't responsive
- **Fix:**
  1. Stop MySQL in XAMPP
  2. Wait 5 seconds
  3. Start MySQL again
  4. Try running script

### No Results
- **Cause:** Database exists but organizations table is empty
- **Fix:** Ensure data has been loaded into the database

## SQL Queries Used

The scripts execute these queries:

```sql
-- Total count
SELECT COUNT(*) as total FROM organizations

-- Active count
SELECT COUNT(*) as total FROM organizations WHERE accreditation_status = 'active'

-- All statuses
SELECT accreditation_status, COUNT(*) as count 
FROM organizations 
GROUP BY accreditation_status 
ORDER BY accreditation_status ASC

-- Sample data
SELECT id, name, barangay, accreditation_status 
FROM organizations 
WHERE accreditation_status = ? 
LIMIT 5
```

## Notes

- Both scripts use prepared statements for security
- No data is modified - they are read-only queries
- Scripts can be run safely without affecting the database
- Output includes both count and percentage statistics
- Web version includes visual formatting for easier reading
