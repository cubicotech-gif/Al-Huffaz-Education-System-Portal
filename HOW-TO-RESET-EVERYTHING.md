# 🗑️ HOW TO COMPLETELY RESET THE SPONSORSHIP SYSTEM

This guide shows you **3 ways** to completely clean and reset all sponsor data from your database.

---

## ⚠️ CRITICAL WARNING

**This will PERMANENTLY DELETE:**
- ✅ All sponsor user accounts
- ✅ All sponsorship records
- ✅ All sponsor CPT posts
- ✅ All student sponsorship metadata
- ✅ All payment records
- ✅ All related activity logs

**Students will NOT be deleted** - only their sponsorship links will be removed, making them available again.

**MAKE A BACKUP FIRST!**

---

## 📋 CHOOSE YOUR METHOD

### **Method 1: PHP Reset Script (EASIEST - Recommended)**

This is a one-click solution with a nice interface.

#### Steps:

1. **Upload the reset script**
   - File: `reset-sponsorship-system.php`
   - Upload to your WordPress root directory (same folder as `wp-config.php`)

2. **Visit the script in your browser**
   ```
   https://yoursite.com/reset-sponsorship-system.php
   ```

3. **You'll see a warning page**
   - Read the warnings carefully
   - Click "YES, DELETE EVERYTHING" if you're sure
   - Confirm the JavaScript alert

4. **Wait for completion**
   - The script will run and show you progress
   - You'll see exactly what was deleted
   - Summary will appear at the end

5. **DELETE THE FILE** (Security!)
   - After reset is complete, delete `reset-sponsorship-system.php` from your server
   - This prevents unauthorized access

---

### **Method 2: SQL Queries (DIRECT DATABASE)**

If you prefer to run SQL directly in phpMyAdmin or MySQL client.

#### Steps:

1. **Access your database**
   - Log into cPanel/Hosting Panel
   - Open phpMyAdmin
   - Select your WordPress database

2. **MAKE A BACKUP FIRST!**
   - Click "Export" tab
   - Download full backup
   - Save it somewhere safe

3. **Open SQL tab**
   - Click the "SQL" tab in phpMyAdmin

4. **Copy and paste queries**
   - Open file: `RESET-DATABASE-QUERIES.sql`
   - Copy ALL the queries
   - **IMPORTANT:** Replace `wp_` with your actual table prefix if different
   - Paste into SQL tab
   - Click "Go"

5. **Run verification queries**
   - At the bottom of the SQL file, there are verification queries
   - Run them to confirm everything is clean
   - All counts should be 0 (except total students)

---

### **Method 3: Manual Database Cleanup (STEP BY STEP)**

If you want full control and want to do it step by step.

#### Step 1: Delete Sponsor Users

**Via WordPress Admin:**
1. Go to: Users → All Users
2. Filter by role: "Sponsor" (alhuffaz_sponsor)
3. Select all sponsor users
4. Bulk Actions → Delete → Confirm

**Via Database:**
```sql
DELETE FROM wp_users
WHERE ID IN (
    SELECT user_id FROM wp_usermeta
    WHERE meta_key = 'wp_capabilities'
    AND meta_value LIKE '%alhuffaz_sponsor%'
);
```

#### Step 2: Delete Sponsorship CPTs

**Via WordPress Admin:**
1. Go to: Sponsorships (in admin menu)
2. Select "All" status filter
3. Select all sponsorships
4. Bulk Actions → Move to Trash
5. Go to Trash
6. Empty Trash (permanent delete)

**Via Database:**
```sql
DELETE FROM wp_postmeta WHERE post_id IN (
    SELECT ID FROM wp_posts WHERE post_type = 'sponsorship'
);
DELETE FROM wp_posts WHERE post_type = 'sponsorship';
```

#### Step 3: Delete Sponsor CPTs

**Via Database:**
```sql
DELETE FROM wp_postmeta WHERE post_id IN (
    SELECT ID FROM wp_posts WHERE post_type = 'sponsor'
);
DELETE FROM wp_posts WHERE post_type = 'sponsor';
```

#### Step 4: Free All Students

**Via Database:**
```sql
DELETE FROM wp_postmeta
WHERE meta_key IN (
    'already_sponsored',
    'sponsored_date',
    'sponsor_cpt_id',
    'is_sponsored',
    'sponsor_id'
);
```

#### Step 5: Clean Payment Table

**Via Database:**
```sql
TRUNCATE TABLE wp_alhuffaz_payments;
```

---

## ✅ VERIFICATION

After reset, verify everything is clean:

### **Check in WordPress Admin:**

1. **Users → All Users**
   - Filter by "Sponsor" role
   - Should show: "No users found"

2. **Admin Portal → Payment Tab**
   - Should show: "No payment records found"

3. **Students List (from sponsor perspective)**
   - All students should appear as "Available"
   - None should be marked as "Already Sponsored"

### **Check in Database:**

Run these queries in phpMyAdmin:

```sql
-- Check sponsor users (should be 0)
SELECT COUNT(*) FROM wp_usermeta
WHERE meta_key = 'wp_capabilities'
AND meta_value LIKE '%alhuffaz_sponsor%';

-- Check sponsorships (should be 0)
SELECT COUNT(*) FROM wp_posts WHERE post_type = 'sponsorship';

-- Check sponsor CPTs (should be 0)
SELECT COUNT(*) FROM wp_posts WHERE post_type = 'sponsor';

-- Check sponsored students (should be 0)
SELECT COUNT(*) FROM wp_postmeta
WHERE meta_key = 'already_sponsored' AND meta_value = 'yes';

-- Check total students (should show your actual student count)
SELECT COUNT(*) FROM wp_posts
WHERE post_type = 'student' AND post_status = 'publish';
```

---

## 🎯 WHAT HAPPENS AFTER RESET

After running the reset:

✅ **All students are available** for sponsorship
✅ **Payment tab is empty** (no payment records)
✅ **No sponsor users exist**
✅ **No active sponsorships**
✅ **Clean slate** to start fresh

### **Students are PRESERVED:**
- All student data remains intact
- Academic records preserved
- Photos preserved
- Grades preserved
- Only sponsorship links are removed

---

## 🔄 STARTING FRESH

After reset, you can:

1. **Register new sponsors** via registration form
2. **Admin approves** new sponsors
3. **Sponsors submit payments** for students
4. **Admin approves** payments
5. **Students get linked** to sponsors
6. **Everything works cleanly!**

---

## 📞 NEED HELP?

If something goes wrong:

1. **Restore from backup** if you made one
2. **Check error logs** in cPanel
3. **Run verification queries** to see what remains
4. **Contact support** with specific error messages

---

## 🔐 SECURITY REMINDERS

1. **Delete `reset-sponsorship-system.php` after use!**
2. **Keep your database backup safe**
3. **Don't share these files publicly**
4. **Only run when logged in as administrator**

---

**You're all set!** Choose your preferred method above and follow the steps carefully.
