# Al-Huffaz Education Portal - Setup Guide

This guide will help you connect your GitHub repository to your Namecheap hosting for automatic deployment.

## Overview

```
┌─────────────────┐     Auto-Deploy      ┌─────────────────────┐
│   GitHub Repo   │ ──────────────────►  │  Namecheap Hosting  │
│  (Edit here)    │     (via FTP)        │  (WordPress runs)   │
└─────────────────┘                      └─────────────────────┘
```

When you push changes to GitHub, they automatically upload to your WordPress site.

---

## Step 1: Get FTP Credentials from Namecheap cPanel

### 1.1 Login to cPanel

1. Go to your Namecheap dashboard
2. Click on your domain > **Manage**
3. Click **Go to cPanel** or visit `yourdomain.com/cpanel`

### 1.2 Create FTP Account

1. In cPanel, find **"FTP Accounts"** (under Files section)
2. Click **"Create FTP Account"**
3. Fill in:
   - **Login**: `github-deploy`
   - **Password**: Create a strong password (save it!)
   - **Directory**: `/public_html/wp-content/plugins/al-huffaz-portal`
     - Or for subdomain: `/public_html/portal/wp-content/plugins/al-huffaz-portal`
   - **Quota**: Unlimited
4. Click **Create**

### 1.3 Note Your FTP Details

You'll need:
- **FTP Server**: Usually `ftp.yourdomain.com` or found in cPanel FTP Accounts section
- **FTP Username**: `github-deploy@yourdomain.com`
- **FTP Password**: The password you created
- **FTP Path**: `/public_html/wp-content/plugins/al-huffaz-portal/` (with trailing slash)

---

## Step 2: Add Secrets to GitHub

### 2.1 Go to Repository Settings

1. Go to your GitHub repository
2. Click **Settings** tab
3. Click **Secrets and variables** > **Actions**
4. Click **New repository secret**

### 2.2 Add These Secrets

Add each of these (click "New repository secret" for each):

| Secret Name | Value | Example |
|-------------|-------|---------|
| `FTP_SERVER` | Your FTP server address | `ftp.yourdomain.com` |
| `FTP_USERNAME` | Your FTP username | `github-deploy@yourdomain.com` |
| `FTP_PASSWORD` | Your FTP password | `YourSecurePassword123!` |
| `FTP_PATH` | Plugin folder path | `/public_html/wp-content/plugins/al-huffaz-portal/` |

**Important**:
- FTP_PATH must end with a trailing slash `/`
- Keep these secrets secure - never share them

---

## Step 3: Create Subdomain (Optional but Recommended)

If you want to set up the portal on a subdomain like `portal.yourdomain.com`:

### 3.1 Create Subdomain

1. In cPanel, go to **"Subdomains"**
2. Enter: `portal`
3. Document Root: `/public_html/portal`
4. Click **Create**

### 3.2 Install WordPress

1. Go to **"Softaculous Apps Installer"** in cPanel
2. Click **WordPress**
3. Click **Install**
4. Choose Protocol: `https://`
5. Choose Domain: `portal.yourdomain.com`
6. In Directory: Leave empty
7. Set admin username & password
8. Click **Install**

### 3.3 Update FTP_PATH Secret

If using subdomain, update your `FTP_PATH` secret to:
```
/public_html/portal/wp-content/plugins/al-huffaz-portal/
```

---

## Step 4: Trigger Deployment

### Option A: Automatic (Push to main)

Any push to the `main` or `master` branch will automatically deploy:

```bash
git add .
git commit -m "Update"
git push origin main
```

### Option B: Manual

1. Go to your GitHub repository
2. Click **Actions** tab
3. Click **"Deploy to Namecheap"**
4. Click **"Run workflow"**
5. Click the green **"Run workflow"** button

---

## Step 5: Activate the Plugin

1. Login to your WordPress admin: `yourdomain.com/wp-admin`
2. Go to **Plugins** > **Installed Plugins**
3. Find **"Al-Huffaz Education Portal"**
4. Click **Activate**

---

## Step 6: Using the Portal

### Admin Pages

After activation, you'll see a new menu **"Al-Huffaz"** in WordPress admin with:
- **Dashboard** - Overview & statistics
- **Students** - Manage students
- **Add Student** - Add new students
- **Sponsors** - Manage sponsorships
- **Payments** - Track payments
- **Reports** - Generate reports
- **Bulk Import** - Import students from CSV
- **Settings** - Configure the portal

### Shortcodes for Frontend

Add these shortcodes to WordPress pages:

| Shortcode | Description |
|-----------|-------------|
| `[alhuffaz_students]` | Display available students for sponsorship |
| `[alhuffaz_sponsor_dashboard]` | Sponsor's personal dashboard (requires login) |
| `[alhuffaz_sponsorship_form]` | Sponsorship submission form |
| `[alhuffaz_payment_form]` | Payment submission form |

### Creating Pages

1. Create a page called "Sponsor a Student"
2. Add shortcode: `[alhuffaz_students]`
3. Publish

---

## Troubleshooting

### Deployment Failed?

1. **Check FTP credentials** - Make sure username/password are correct
2. **Check FTP path** - Must end with `/` and point to plugin folder
3. **Check server** - Some hosts use different FTP addresses

### Plugin Not Working?

1. **Check PHP version** - Requires PHP 7.4+
2. **Check WordPress version** - Requires WP 5.8+
3. **Activate the plugin** - Make sure it's activated
4. **Check error log** - In cPanel > Errors

### FTP Connection Refused?

1. Some hosts block default FTP. Check if your host uses:
   - SFTP instead of FTP
   - Non-standard ports (21 is default)
2. Contact Namecheap support

---

## File Structure

```
al-huffaz-portal/
├── al-huffaz-portal.php      # Main plugin file
├── includes/
│   ├── admin/                # Admin functionality
│   ├── core/                 # Core classes
│   └── public/               # Frontend functionality
├── assets/
│   ├── css/                  # Stylesheets
│   ├── js/                   # JavaScript
│   └── images/               # Images
├── templates/
│   ├── admin/                # Admin templates
│   └── public/               # Frontend templates
└── languages/                # Translation files
```

---

## Need Help?

1. Check the [Issues](../../issues) page
2. Create a new issue with details about your problem
3. Include any error messages you see

---

## Quick Reference

| What | Where |
|------|-------|
| FTP Accounts | cPanel > Files > FTP Accounts |
| Subdomains | cPanel > Domains > Subdomains |
| WordPress Install | cPanel > Softaculous > WordPress |
| GitHub Secrets | Repo > Settings > Secrets and variables > Actions |
| GitHub Actions | Repo > Actions tab |
| WordPress Admin | yourdomain.com/wp-admin |
| Plugin Settings | WP Admin > Al-Huffaz > Settings |
