# Mobile Session Persistence - Deployment Guide

## What Was Fixed

### Issue
Mobile users were experiencing session timeouts when:
- Closing and reopening the browser
- Going to background and returning to app
- Refreshing the page on slow mobile networks

**Root Cause:** PHP sessions by default only persist for the browser session. Mobile browsers often clear sessions aggressively.

### Solution: "Remember Me" Token System
Users can now check "Remember Me" on login to stay logged in for 30 days even if the browser clears the session.

## Deployment Steps

### 1. Create Database Tables

**Option A: Run SQL File**
```bash
# On your database server
mysql -u [username] -p [database_name] < database/add_remember_me_tokens.sql
```

**Option B: Use Setup Script**
```
1. Calculate your setup key: lydo_setup_[first 8 chars of md5(database_name)]
2. Visit: https://your-domain/admin2/setup_remember_me_tables.php?key=lydo_setup_XXXXXXXX
3. You should see "Setup Complete" message
```

### 2. Update Environment Variables (If Needed)

Session timeout is configurable in `shared/config.php`:
```php
ini_set('session.gc_maxlifetime', 86400); // 24 hours before session garbage collection
ini_set('session.cookie_lifetime', 0);   // Browser-session only (but remember_me extends this)
```

For Railway deployment, no additional env vars needed.

### 3. Test the Feature

**On Mobile:**
1. Go to login page
2. Enter credentials
3. **Check "Remember Me" checkbox** ← Important
4. Click Login
5. Close the browser completely
6. Reopen the browser and go to the app
7. Should be automatically logged in

**Without Remember Me:**
- Session behaves normally (expires with browser)

**On Desktop:**
- Works the same as mobile
- "Remember Me" recommended for shared computers

### 4. Logout Behavior

When a user logs out, remember me tokens are:
- Deleted from database
- Deleted from browser cookie
- Session fully cleared

No "remember me" persistence after logout.

## Security Considerations

### Token Security
- Tokens are SHA256-hashed in database
- Original token only in cookie (httpOnly flag prevents JavaScript access)
- Tokens expire automatically after 30 days
- One token per user per device (older tokens replaced)
- Tokens tied to user ID and type (admin/president/youth)

### What's Protected
- All user data remains secure
- Session regeneration happens on every login
- Remember me only extends SESSION validity, not authentication
- If remember me token is compromised, attacker can only impersonate for 30 days

### Best Practices
- Always logout on shared devices
- Clear cookies periodically on public devices
- Token expiry can be reduced in code if needed:
  ```php
  createRememberMeToken('youth', $user['id'], 7); // 7 days instead of 30
  ```

## File Changes

### Modified Files
- `login.php` - Added remember me token verification and creation
- `admin2/logout.php` - Clears remember me tokens on logout
- `org-president/logout.php` - Clears remember me tokens on logout
- `shared/youth/logout.php` - Clears remember me tokens on logout
- `shared/config.php` - Added token helper functions

### New Files
- `database/add_remember_me_tokens.sql` - Database schema
- `admin2/setup_remember_me_tables.php` - Setup utility script

### New Database Tables
- `admin_remember_tokens` - Admin remember me tokens
- `president_remember_tokens` - Organization president tokens
- `youth_remember_tokens` - Youth member tokens

## Troubleshooting

### Remember Me Not Working

**Symptom:** Checking "Remember Me" doesn't keep user logged in

**Solution:**
1. Verify database tables were created:
   ```sql
   SHOW TABLES LIKE '%remember_tokens%';
   ```
   Should show 3 tables: admin_remember_tokens, president_remember_tokens, youth_remember_tokens

2. Check browser cookies are enabled
3. Verify logout is actually clearing tokens:
   ```sql
   SELECT * FROM admin_remember_tokens WHERE admin_id = [user_id];
   ```

### Session Still Clearing

**Symptom:** Sessions still timing out even with Remember Me checked

**Solution:**
1. Check session.gc_maxlifetime in `shared/config.php` (should be >= 86400)
2. Verify Railway session storage is persistent
3. Check browser isn't in private/incognito mode (cookies not saved)

### Token Cleanup

**Expired tokens in database:**
- Automatically cleaned up when login is attempted
- Manual cleanup (optional):
  ```sql
  CALL cleanup_expired_tokens();
  ```

## Rollback (If Needed)

1. Remove the checkbox from login form (remove HTML lines with "Remember Me")
2. Token functions in config.php can remain (harmless if unused)
3. Sessions revert to default behavior (expires with browser)
4. No other code changes needed

## Performance Impact

- Minimal: One extra database lookup on login if token exists
- Tokens indexed by expiry date for automatic cleanup
- No impact on running sessions

## Feature Flags

To disable Remember Me without rollback:

```php
// In login.php, change:
if (!empty($_POST['remember_me'])) {
    // createRememberMeToken('youth', $user['id']); // DISABLED
}
```

## Future Enhancements

- Admin can view/revoke tokens from user settings
- Two-factor authentication integration
- Device fingerprinting for additional security
- Bulk token cleanup utility
- Token activity logging

## Support & Questions

For issues with session persistence:
1. Check error logs at `/shared/uploads/error.log`
2. Verify database tables exist and have correct structure
3. Test on a private browsing session
4. Clear all browser cookies and try again
