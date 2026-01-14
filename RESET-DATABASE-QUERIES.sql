-- ========================================
-- COMPLETE SPONSORSHIP SYSTEM RESET
-- Run these queries in phpMyAdmin or MySQL client
-- ========================================
-- WARNING: This will DELETE ALL sponsor data!
-- Make a backup before running!
-- ========================================

-- Replace 'wp_' with your actual table prefix if different

-- STEP 1: Delete all sponsor users
-- (First, get the sponsor role user IDs)
DELETE FROM wp_users
WHERE ID IN (
    SELECT user_id FROM wp_usermeta
    WHERE meta_key = 'wp_capabilities'
    AND meta_value LIKE '%alhuffaz_sponsor%'
);

-- Clean up orphaned user meta
DELETE FROM wp_usermeta
WHERE user_id NOT IN (SELECT ID FROM wp_users);

-- STEP 2: Delete all sponsorship CPT posts and meta
DELETE pm FROM wp_postmeta pm
INNER JOIN wp_posts p ON pm.post_id = p.ID
WHERE p.post_type = 'sponsorship';

DELETE FROM wp_posts
WHERE post_type = 'sponsorship';

-- STEP 3: Delete all sponsor CPT posts and meta
DELETE pm FROM wp_postmeta pm
INNER JOIN wp_posts p ON pm.post_id = p.ID
WHERE p.post_type = 'sponsor';

DELETE FROM wp_posts
WHERE post_type = 'sponsor';

-- STEP 4: Free all students - remove sponsorship metadata
DELETE FROM wp_postmeta
WHERE meta_key IN (
    'already_sponsored',
    'sponsored_date',
    'sponsor_cpt_id',
    'is_sponsored',
    'sponsor_id'
)
AND post_id IN (
    SELECT ID FROM wp_posts WHERE post_type = 'student'
);

-- STEP 5: Clean payment table (if it exists)
TRUNCATE TABLE wp_alhuffaz_payments;

-- STEP 6: Clean activity log related to sponsors
DELETE FROM wp_alhuffaz_activity_log
WHERE action IN (
    'sponsor_registered',
    'sponsor_approved',
    'sponsor_rejected',
    'sponsor_deleted',
    'approve_sponsorship',
    'reject_sponsorship',
    'unlink_sponsor'
);

-- STEP 7: Clean notifications for sponsors
DELETE FROM wp_alhuffaz_notifications
WHERE related_type IN ('sponsorship', 'sponsor');

-- STEP 8: Reset cleanup options
DELETE FROM wp_options
WHERE option_name = 'sponsor_last_cleanup';

-- ========================================
-- VERIFICATION QUERIES
-- Run these to check everything is clean
-- ========================================

-- Check for remaining sponsor users (should be 0)
SELECT COUNT(*) as sponsor_users_remaining
FROM wp_usermeta
WHERE meta_key = 'wp_capabilities'
AND meta_value LIKE '%alhuffaz_sponsor%';

-- Check for remaining sponsorships (should be 0)
SELECT COUNT(*) as sponsorships_remaining
FROM wp_posts
WHERE post_type = 'sponsorship';

-- Check for remaining sponsor CPTs (should be 0)
SELECT COUNT(*) as sponsor_cpts_remaining
FROM wp_posts
WHERE post_type = 'sponsor';

-- Check for sponsored students (should be 0)
SELECT COUNT(*) as sponsored_students_remaining
FROM wp_postmeta
WHERE meta_key = 'already_sponsored'
AND meta_value = 'yes';

-- Check total students (should show all your students)
SELECT COUNT(*) as total_students
FROM wp_posts
WHERE post_type = 'student'
AND post_status = 'publish';

-- ========================================
-- DONE! All sponsor data deleted.
-- ========================================
