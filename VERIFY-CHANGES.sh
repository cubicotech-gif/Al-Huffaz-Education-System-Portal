#!/bin/bash

echo "================================================="
echo "🔍 AL-HUFFAZ PLUGIN CHANGES VERIFICATION"
echo "================================================="
echo ""

PLUGIN_DIR="al-huffaz-portal"

# Check if we're in the right directory
if [ ! -d "$PLUGIN_DIR" ]; then
    echo "❌ ERROR: al-huffaz-portal directory not found!"
    echo "   Run this script from the repository root"
    exit 1
fi

echo "✅ Plugin directory found"
echo ""

# Check template file for our changes
echo "📄 Checking sponsor-dashboard.php template..."
TEMPLATE="$PLUGIN_DIR/templates/public/sponsor-dashboard.php"

if grep -q "payment_submitted" "$TEMPLATE"; then
    echo "  ✅ Success banner code FOUND"
else
    echo "  ❌ Success banner code NOT FOUND"
fi

if grep -q "pending_sponsorships" "$TEMPLATE"; then
    echo "  ✅ Pending payments code FOUND"
else
    echo "  ❌ Pending payments code NOT FOUND"
fi

if grep -q "Financial Summary" "$TEMPLATE"; then
    echo "  ✅ Financial breakdown code FOUND"
else
    echo "  ❌ Financial breakdown code NOT FOUND"
fi

echo ""

# Check JavaScript file
echo "📄 Checking public.js..."
JS_FILE="$PLUGIN_DIR/assets/js/public.js"

if grep -q "redirect_url" "$JS_FILE"; then
    echo "  ✅ Redirect code FOUND"
else
    echo "  ❌ Redirect code NOT FOUND"
fi

echo ""

# Check AJAX handler
echo "📄 Checking AJAX handler..."
AJAX_FILE="$PLUGIN_DIR/includes/core/class-ajax-handler.php"

if grep -q "redirect_url" "$AJAX_FILE"; then
    echo "  ✅ Redirect URL code FOUND"
else
    echo "  ❌ Redirect URL code NOT FOUND"
fi

echo ""

# Check Dashboard class
echo "📄 Checking Sponsor Dashboard class..."
DASHBOARD_CLASS="$PLUGIN_DIR/includes/public/class-sponsor-dashboard.php"

if grep -q "pending_sponsorships" "$DASHBOARD_CLASS"; then
    echo "  ✅ Pending sponsorships code FOUND"
else
    echo "  ❌ Pending sponsorships code NOT FOUND"
fi

if grep -q "monthly_total" "$DASHBOARD_CLASS"; then
    echo "  ✅ Financial totals code FOUND"
else
    echo "  ❌ Financial totals code NOT FOUND"
fi

echo ""

# Check git status
echo "📊 Git Status:"
echo "  Branch: $(git branch --show-current)"
echo "  Last commit: $(git log -1 --format='%h - %s' | head -c 80)"
echo "  Modified files:"
git status --short

echo ""
echo "================================================="
echo "✅ Verification complete!"
echo ""
echo "If all checks show ✅ but changes don't appear:"
echo "  1. Clear browser cache (Ctrl+Shift+R)"
echo "  2. Upload files to your WordPress server"
echo "  3. Deactivate and reactivate the plugin"
echo "  4. Check you're logged in as sponsor role"
echo "================================================="
