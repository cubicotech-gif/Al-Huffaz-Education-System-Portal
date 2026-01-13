# SPONSORSHIP CPT MIGRATION LOG
**Date:** 2026-01-13
**Task:** Consolidate duplicate sponsorship CPTs - Standardize on 'sponsorship'
**Approach:** Option A - Full Cleanup Migration

---

## BACKUP - ORIGINAL STATE

### Files to be Modified:
1. `al-huffaz-portal/includes/core/class-roles.php` (get_sponsor_students function)
2. `al-huffaz-portal/includes/core/class-post-types.php` (CPT registration)
3. `al-huffaz-portal/templates/public/sponsor-dashboard.php` (profile query)
4. `al-huffaz-portal/includes/core/class-ajax-handler.php` (create_sponsorship function)

### Current Dual System:
- **Legacy System:** 'alhuffaz_sponsor' CPT with underscore-prefixed meta keys
- **Active System:** 'sponsorship' CPT with non-prefixed meta keys

---

## CHANGES TO BE MADE

### Change 1: Update get_sponsor_students() Function
**File:** `al-huffaz-portal/includes/core/class-roles.php:322-355`
**Current:** Queries 'alhuffaz_sponsor' with '_student_id', '_sponsor_user_id', '_linked', '_status'
**New:** Will query 'sponsorship' with 'student_id', 'sponsor_user_id', 'linked', 'verification_status'

### Change 2: Remove alhuffaz_sponsor CPT Registration
**File:** `al-huffaz-portal/includes/core/class-post-types.php:114-141`
**Action:** Remove entire alhuffaz_sponsor CPT registration block
**Reason:** Only 'sponsorship' CPT is used in production

### Change 3: Fix Sponsor Dashboard Profile Query
**File:** `al-huffaz-portal/templates/public/sponsor-dashboard.php:250-266`
**Current:** Queries 'alhuffaz_sponsor' CPT for profile data
**New:** Use user_meta directly (phone, country, whatsapp already stored there)

### Change 4: Handle create_sponsorship() Function
**File:** `al-huffaz-portal/includes/core/class-ajax-handler.php:1871-1970`
**Action:** Update to use 'sponsorship' CPT and correct meta keys OR deprecate if unused

---

## VERIFICATION CHECKLIST

- [ ] Registration flow works (new sponsors can register)
- [ ] Login flow works (sponsors can log in)
- [ ] Payment submission works (sponsors can submit payment proofs)
- [ ] Admin approval works (admins can approve sponsorships)
- [ ] Dashboard displays correctly (sponsors see their sponsorships)
- [ ] Stats are accurate (active, pending, total contributed)
- [ ] No PHP errors in logs
- [ ] 'Sponsorships' menu visible in WP admin

---

## ROLLBACK PLAN

If anything breaks:
1. Restore original files from git: `git checkout HEAD -- <file>`
2. Each change is committed separately for easy rollback
3. All original code preserved in this documentation

---

## MIGRATION PROGRESS

### Step 1: Pre-Migration Checks
- Status: IN PROGRESS
- Action: Documenting current state

### Step 2: Fix get_sponsor_students()
- Status: PENDING
- File: class-roles.php

### Step 3: Remove Legacy CPT
- Status: PENDING
- File: class-post-types.php

### Step 4: Fix Dashboard Profile Query
- Status: PENDING
- File: sponsor-dashboard.php

### Step 5: Update create_sponsorship()
- Status: PENDING
- File: class-ajax-handler.php

### Step 6: Testing & Verification
- Status: PENDING

---

**CRITICAL NOTES:**
- All changes preserve existing data
- No database modifications required (only code changes)
- Meta keys standardized: NO underscore prefix
- Post type standardized: 'sponsorship' only
- User role unchanged: 'alhuffaz_sponsor' (no conflict with CPT name)
