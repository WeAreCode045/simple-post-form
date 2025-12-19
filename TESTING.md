# Testing Guide - Hide Labels & Debug Mode Features

## Features Added

### 1. Hide Field Labels on Frontend
- Location: Form Settings → Display Options
- Checkbox: "Hide field labels on frontend"
- Effect: When enabled, all field labels are hidden on the frontend and placeholders are used instead
- If a field has no placeholder, the label text is automatically used as the placeholder

### 2. Debug Mode for Honeypot Field
- Location: Form Settings → Debug & Testing
- Checkbox: "Enable debug mode for honeypot field"
- Effect: When enabled, administrators can see the honeypot anti-spam field
- Visual: Honeypot field shows with red dashed border, yellow background, and "[DEBUG: Honeypot Field]" label
- Non-admin users: Honeypot remains hidden for regular visitors

## How to Test

### Step 1: Activate Database Changes
1. Go to WordPress Admin → Plugins
2. The database will automatically update on the next admin page load
3. Or you can deactivate and reactivate the plugin to force the update

### Step 2: Test Hide Labels Feature
1. Go to Forms → Edit Form
2. Scroll to "Display Options" section
3. Check "Hide field labels on frontend"
4. Click "Save Form"
5. View the form on the frontend
6. Verify: Labels should be hidden, placeholders visible
7. Verify: Fields without placeholders show label text as placeholder

### Step 3: Test Debug Mode
1. Go to Forms → Edit Form
2. Scroll to "Debug & Testing" section
3. Check "Enable debug mode for honeypot field"
4. Click "Save Form"
5. View the form on the frontend while logged in as admin
6. Verify: You should see a field labeled "[DEBUG: Honeypot Field]" with red border
7. Log out or open in incognito mode
8. Verify: Honeypot field is hidden for non-logged-in users

### Step 4: Test Both Features Together
1. Enable both "Hide field labels" and "Debug mode"
2. Save the form
3. View frontend as admin
4. Verify: Regular field labels are hidden
5. Verify: Honeypot field is still visible with its debug label
6. Verify: Placeholders are shown for regular fields

## Troubleshooting

### Checkboxes Not Saving
**Solution**: The plugin now automatically runs database migrations. Just reload any admin page and the columns will be created.

### Changes Not Appearing on Frontend
1. Clear browser cache
2. Clear WordPress cache (if using a caching plugin)
3. Hard reload the page (Cmd+Shift+R on Mac, Ctrl+Shift+R on Windows)

### Honeypot Not Showing in Debug Mode
1. Make sure you're logged in as an administrator
2. Check that debug_mode is checked and form is saved
3. Clear cache and reload

## Technical Details

### Database Changes
Two new columns added to `wp_spf_forms` table:
- `hide_labels` - tinyint(1) DEFAULT 0
- `debug_mode` - tinyint(1) DEFAULT 0

### Files Modified
- `includes/class-simple-post-form.php` - Database schema and migration
- `includes/class-simple-post-form-admin.php` - Admin UI for settings
- `includes/class-simple-post-form-frontend.php` - Frontend rendering logic
- `assets/js/admin.js` - JavaScript to save checkbox values
- `assets/css/frontend.css` - Debug mode styling

### Automatic Migration
The plugin now checks the version on every admin page load and runs migrations if needed. This ensures new columns are created without requiring plugin reactivation.
