<?php
/*
Plugin Name: Al-Huffaz Alfa Payment Gateway (Bank Alfalah)
Description: Adds Bank Alfalah "Alfa Payment Gateway" (APG) Page-Redirection card payments as an AUTOMATED path alongside the existing manual proof flow. On a verified card payment the sponsorship is auto-activated using the exact same logic as the admin "Verify & Link" button. Manual flow is left 100% untouched.
Version: 1.0.0 (Phase 1 - Sandbox)
Author: Al-Huffaz Development
*/

defined('ABSPATH') || exit;

define('AHALFA_VER', '1.3.0');

/* ============================================================================
 * 0. CONFIG
 * All secrets live in WP options (Settings > Alfa Gateway), never in code/git.
 * ========================================================================== */

function ahalfa_settings() {
    $defaults = array(
        'enabled'           => 'no',
        'environment'       => 'sandbox',   // sandbox | live
        'merchant_id'       => '',
        'store_id'          => '',
        'merchant_hash'     => '',
        'merchant_username' => '',
        'merchant_password' => '',
        'key1'              => '',
        'key2'              => '',
        'currency'          => 'PKR',
    );
    return wp_parse_args(get_option('ahalfa_settings', array()), $defaults);
}

function ahalfa_urls($env) {
    $base = ($env === 'live')
        ? 'https://payments.bankalfalah.com'
        : 'https://sandbox.bankalfalah.com';
    return array(
        'handshake' => $base . '/HS/HS/HS',
        'sso'       => $base . '/SSO/SSO/SSO',
        'ipn'       => $base . '/HS/api/IPN/OrderStatus', // + /{merchantId}/{storeId}/{orderId}
    );
}

function ahalfa_log($msg) {
    if (is_array($msg) || is_object($msg)) { $msg = print_r($msg, true); }
    error_log('[AlfaGateway] ' . $msg);
}

/* ============================================================================
 * 1. THE ENCRYPTION (matches the sandbox CryptoJS sample exactly)
 *    AES-128-CBC, key=Key1, iv=Key2, PKCS7, Base64. Hash is built over an
 *    ordered "id=value&..." map of the request fields (excluding the hash
 *    field itself). APG decrypts this to validate the request.
 * ========================================================================== */

function ahalfa_request_hash(array $ordered_fields, $key1, $key2) {
    $parts = array();
    foreach ($ordered_fields as $k => $v) {
        $parts[] = $k . '=' . $v;
    }
    $map = implode('&', $parts);
    $enc = openssl_encrypt($map, 'AES-128-CBC', $key1, OPENSSL_RAW_DATA, $key2);
    return base64_encode($enc);
}

/* ============================================================================
 * 2. AMOUNT (computed server-side; never trust the browser)
 *    Uses the same formula as the live "Sponsor a Student" browse page.
 *    APG requires a WHOLE number, so we round to integer PKR.
 * ========================================================================== */

function ahalfa_calc_amount($student_id, $type) {
    $monthly   = (float) get_post_meta($student_id, 'monthly_tuition_fee', true);
    $course    = (float) get_post_meta($student_id, 'course_fee', true);
    $uniform   = (float) get_post_meta($student_id, 'uniform_fee', true);
    $annual    = (float) get_post_meta($student_id, 'annual_fee', true);
    $one_time  = $course + $uniform + $annual;

    switch ($type) {
        case 'yearly':    $amount = ($monthly * 12) + $one_time;        break;
        case 'quarterly': $amount = ($monthly * 3)  + ($one_time / 4);  break;
        case 'monthly':
        default:          $type = 'monthly';
                          $amount = $monthly + ($one_time / 12);        break;
    }
    return array('type' => $type, 'amount' => (int) round($amount));
}

/* ============================================================================
 * 3. ROUTES:  /alfalah-pay/  /alfalah-return/  /alfalah-listener/
 * ========================================================================== */

add_action('init', function () {
    add_rewrite_rule('^alfalah-pay/?$',      'index.php?ahalfa=pay',      'top');
    add_rewrite_rule('^alfalah-return/?$',   'index.php?ahalfa=return',   'top');
    add_rewrite_rule('^alfalah-listener/?$', 'index.php?ahalfa=listener', 'top');

    // Self-heal: flush once per version if this file was copied without re-activation.
    if (get_option('ahalfa_rewrites') !== '1.0.0') {
        flush_rewrite_rules(false);
        update_option('ahalfa_rewrites', '1.0.0');
    }
});

add_filter('query_vars', function ($vars) { $vars[] = 'ahalfa'; return $vars; });

// Dispatch on `init` by matching the URL path directly. This runs before
// WordPress decides a page is a 404, so the endpoints work regardless of
// whether rewrite rules have been flushed. (Pretty-permalink query_var is a
// bonus path, but we no longer depend on it.)
add_action('init', 'ahalfa_dispatch', 99);
function ahalfa_dispatch() {
    $uri  = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
    $path = trim((string) parse_url($uri, PHP_URL_PATH), '/');
    if ($path === '') return;

    $map = array(
        'alfalah-ping'     => 'ping',
        'alfalah-pay'      => 'pay',
        'alfalah-return'   => 'return',
        'alfalah-listener' => 'listener',
    );
    $route = '';
    foreach ($map as $slug => $r) {
        if (preg_match('#(^|/)' . preg_quote($slug, '#') . '/?$#', $path)) { $route = $r; break; }
    }
    if (!$route) return;

    switch ($route) {
        case 'ping':     ahalfa_handle_ping();     break;
        case 'pay':      ahalfa_handle_pay();      break;
        case 'return':   ahalfa_handle_return();   break;
        case 'listener': ahalfa_handle_listener(); break;
    }
    exit;
}

// Plain-text proof that the current plugin code is executing on this request.
function ahalfa_handle_ping() {
    nocache_headers();
    header('Content-Type: text/plain; charset=utf-8');
    $s = ahalfa_settings();
    echo "ALFALAH GATEWAY OK\n";
    echo "version=" . AHALFA_VER . "\n";
    echo "enabled=" . $s['enabled'] . "\n";
    echo "environment=" . $s['environment'] . "\n";
    echo "merchant_id=" . ($s['merchant_id'] !== '' ? 'set' : 'EMPTY') . "\n";
    echo "key1=" . ($s['key1'] !== '' ? 'set' : 'EMPTY') . "\n";
    echo "logged_in=" . (is_user_logged_in() ? 'yes' : 'no') . "\n";
    echo "time=" . current_time('mysql') . "\n";
}

/* ============================================================================
 * 4. INITIATE  (/alfalah-pay/?student=<id>&type=monthly|quarterly|yearly)
 *    - requires a logged-in, APPROVED sponsor (ties payment to the person)
 *    - creates a pending sponsorship with a unique order reference
 *    - server-side handshake -> AuthToken
 *    - auto-submits the redirect form to APG's hosted card page
 * ========================================================================== */

function ahalfa_handle_pay() {
    $s = ahalfa_settings();
    if ($s['enabled'] !== 'yes') { ahalfa_die('Card payments are currently unavailable.'); }

    if (!is_user_logged_in()) {
        wp_safe_redirect(wp_login_url(home_url('/alfalah-pay/?' . $_SERVER['QUERY_STRING'])));
        exit;
    }

    $user  = wp_get_current_user();
    $uid   = $user->ID;
    $status = get_user_meta($uid, 'account_status', true);
    if ($status && $status !== 'approved') {
        ahalfa_die('Your sponsor account is awaiting admin approval.');
    }

    $student_id = isset($_GET['student']) ? intval($_GET['student']) : 0;
    $type       = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : 'monthly';

    if (!$student_id || get_post_type($student_id) !== 'student') {
        ahalfa_die('Invalid student.');
    }
    if (get_post_meta($student_id, 'donation_eligible', true) !== 'yes') {
        ahalfa_die('This student is not open for sponsorship.');
    }
    if (get_post_meta($student_id, 'already_sponsored', true) === 'yes') {
        ahalfa_die('This student has already been sponsored.');
    }

    $calc   = ahalfa_calc_amount($student_id, $type);
    $type   = $calc['type'];
    $amount = $calc['amount'];
    if ($amount < 1) { ahalfa_die('Could not determine a sponsorship amount for this student.'); }

    // Unique per-attempt order reference (also our TransactionReferenceNumber).
    $order_ref = 'AH' . $student_id . 'U' . $uid . 'T' . time() . wp_rand(100, 999);

    // Create the pending sponsorship (mirrors the manual flow's record shape).
    $student_name = get_the_title($student_id);
    $sponsor_name = trim($user->first_name . ' ' . $user->last_name);
    if ($sponsor_name === '') { $sponsor_name = $user->display_name; }

    $sponsorship_id = wp_insert_post(array(
        'post_title'  => $sponsor_name . ' → ' . $student_name . ' (' . ucfirst($type) . ') [Card]',
        'post_type'   => 'sponsorship',
        'post_status' => 'pending',
        'post_author' => $uid,
    ));
    if (!$sponsorship_id || is_wp_error($sponsorship_id)) { ahalfa_die('Could not start the payment. Please try again.'); }

    update_post_meta($sponsorship_id, 'student_id',          $student_id);
    update_post_meta($sponsorship_id, 'sponsor_user_id',     $uid);
    update_post_meta($sponsorship_id, 'sponsorship_type',    $type);
    update_post_meta($sponsorship_id, 'amount',              $amount);
    update_post_meta($sponsorship_id, 'sponsor_name',        $sponsor_name);
    update_post_meta($sponsorship_id, 'sponsor_email',       $user->user_email);
    update_post_meta($sponsorship_id, 'sponsor_phone',       get_user_meta($uid, 'sponsor_phone', true));
    update_post_meta($sponsorship_id, 'sponsor_country',     get_user_meta($uid, 'sponsor_country', true));
    update_post_meta($sponsorship_id, 'payment_method',      'alfalah_card');
    update_post_meta($sponsorship_id, 'gateway',             'alfalah');
    update_post_meta($sponsorship_id, 'gateway_order_ref',   $order_ref);
    update_post_meta($sponsorship_id, 'gateway_status',      'initiated');
    update_post_meta($sponsorship_id, 'submission_date',     current_time('mysql'));
    update_post_meta($sponsorship_id, 'verification_status', 'pending_gateway');
    update_post_meta($sponsorship_id, 'linked',              'no');

    // --- Handshake (server-to-server) ---
    $urls = ahalfa_urls($s['environment']);
    $return_url = home_url('/alfalah-return/');

    $hs_fields = array(
        'HS_IsRedirectionRequest'      => '0',
        'HS_ChannelId'                 => '1001',
        'HS_ReturnURL'                 => $return_url,
        'HS_MerchantId'                => $s['merchant_id'],
        'HS_StoreId'                   => $s['store_id'],
        'HS_MerchantHash'              => $s['merchant_hash'],
        'HS_MerchantUsername'          => $s['merchant_username'],
        'HS_MerchantPassword'          => $s['merchant_password'],
        'HS_TransactionReferenceNumber'=> $order_ref,
    );
    $hs_fields_hashed = $hs_fields; // hash is computed over these ordered fields
    $post_body = $hs_fields;
    $post_body['HS_RequestHash'] = ahalfa_request_hash($hs_fields_hashed, $s['key1'], $s['key2']);

    $resp = wp_remote_post($urls['handshake'], array(
        'timeout' => 45,
        'body'    => $post_body,
    ));

    if (is_wp_error($resp)) {
        ahalfa_log('Handshake WP_Error: ' . $resp->get_error_message());
        ahalfa_die('Could not reach the payment gateway. Please try again.');
    }
    $json = json_decode(wp_remote_retrieve_body($resp), true);
    ahalfa_log(array('handshake_response' => $json, 'order_ref' => $order_ref));

    if (empty($json) || (isset($json['success']) && $json['success'] !== 'true') || empty($json['AuthToken'])) {
        $err = isset($json['ErrorMessage']) ? $json['ErrorMessage'] : 'Handshake failed';
        update_post_meta($sponsorship_id, 'gateway_status', 'handshake_failed');
        ahalfa_die('Payment could not be started (' . esc_html($err) . '). Please try again.');
    }

    $auth_token = $json['AuthToken'];
    update_post_meta($sponsorship_id, 'gateway_status', 'redirected');

    // --- Build the redirect (SSO) form ---
    $sso_fields = array(
        'AuthToken'                 => $auth_token,
        'ChannelId'                 => '1001',
        'Currency'                  => $s['currency'],
        'IsBIN'                     => '0',
        'ReturnURL'                 => $return_url,
        'MerchantId'                => $s['merchant_id'],
        'StoreId'                   => $s['store_id'],
        'MerchantHash'              => $s['merchant_hash'],
        'MerchantUsername'          => $s['merchant_username'],
        'MerchantPassword'          => $s['merchant_password'],
        'TransactionTypeId'         => '3', // Credit/Debit Card
        'TransactionReferenceNumber'=> $order_ref,
        'TransactionAmount'         => (string) $amount,
    );
    $sso_fields['RequestHash'] = ahalfa_request_hash($sso_fields, $s['key1'], $s['key2']);

    ahalfa_render_autopost($urls['sso'], $sso_fields, $student_name, $amount, $s['currency']);
    exit;
}

/* ============================================================================
 * 5. RETURN  (/alfalah-return/)  — customer's browser lands here after paying.
 *    We NEVER trust the query alone; we re-inquire the authoritative status.
 * ========================================================================== */

function ahalfa_handle_return() {
    $s = ahalfa_settings();

    // APG returns the Order ID under alias 'O' (and status hints TS/RC).
    $order_ref = '';
    foreach (array('O', 'TransactionReferenceNumber', 'orderId') as $k) {
        if (!empty($_GET[$k])) { $order_ref = sanitize_text_field($_GET[$k]); break; }
    }
    if ($order_ref === '') {
        // Some deployments append params in the path; parse defensively.
        if (preg_match('/O=([^\/&]+)/', $_SERVER['REQUEST_URI'], $m)) { $order_ref = sanitize_text_field($m[1]); }
    }
    ahalfa_log(array('return_hit' => $_GET, 'uri' => $_SERVER['REQUEST_URI'], 'order_ref' => $order_ref));

    $sponsorship_id = ahalfa_find_by_order_ref($order_ref);
    if (!$sponsorship_id) { ahalfa_redirect_result('error', 0); }

    $result = ahalfa_confirm_and_activate($sponsorship_id, $order_ref, $s, 'return');
    ahalfa_redirect_result($result ? 'success' : 'failed', get_post_meta($sponsorship_id, 'student_id', true));
}

/* ============================================================================
 * 6. LISTENER (/alfalah-listener/) — server-to-server IPN backup.
 *    APG POSTs ?url=<inquiry url>. We GET it and confirm. (Requires Bank
 *    Alfalah to whitelist this URL before it fires.)
 * ========================================================================== */

function ahalfa_handle_listener() {
    $s = ahalfa_settings();
    $inquiry_url = isset($_REQUEST['url']) ? esc_url_raw($_REQUEST['url']) : '';
    ahalfa_log(array('listener_hit' => $_REQUEST));

    if (!$inquiry_url || strpos($inquiry_url, 'bankalfalah.com') === false) {
        status_header(400); echo 'bad request'; return;
    }
    $data = ahalfa_inquire_url($inquiry_url);
    $order_ref = isset($data['TransactionReferenceNumber']) ? $data['TransactionReferenceNumber'] : '';
    $sponsorship_id = ahalfa_find_by_order_ref($order_ref);
    if ($sponsorship_id) {
        ahalfa_apply_status($sponsorship_id, $data, 'listener');
    }
    status_header(200); echo 'OK';
}

/* ============================================================================
 * 7. IPN INQUIRY + CONFIRM + ACTIVATE
 * ========================================================================== */

function ahalfa_confirm_and_activate($sponsorship_id, $order_ref, $s, $ctx) {
    $urls = ahalfa_urls($s['environment']);
    $inquiry_url = trailingslashit($urls['ipn']) . rawurlencode($s['merchant_id'])
                 . '/' . rawurlencode($s['store_id']) . '/' . rawurlencode($order_ref);
    $data = ahalfa_inquire_url($inquiry_url);
    return ahalfa_apply_status($sponsorship_id, $data, $ctx);
}

function ahalfa_inquire_url($url) {
    $resp = wp_remote_get($url, array('timeout' => 45));
    if (is_wp_error($resp)) { ahalfa_log('IPN inquiry error: ' . $resp->get_error_message()); return array(); }
    $body = wp_remote_retrieve_body($resp);
    $data = json_decode($body, true);
    if (!is_array($data)) {
        // Fallback: parse key = "value" style bodies.
        $data = array();
        if (preg_match_all('/(\w+)\s*=\s*"?([^",\n}]*)"?/', $body, $m, PREG_SET_ORDER)) {
            foreach ($m as $pair) { $data[$pair[1]] = trim($pair[2]); }
        }
    }
    ahalfa_log(array('ipn_inquiry' => $url, 'result' => $data));
    return $data;
}

/**
 * Applies the gateway result. Idempotent: if already approved, no-op.
 * Only a "Paid" status with a matching amount activates the sponsorship.
 */
function ahalfa_apply_status($sponsorship_id, $data, $ctx) {
    // Idempotency guard — Return and Listener may both fire, and APG may retry.
    if (get_post_meta($sponsorship_id, 'verification_status', true) === 'approved') {
        return true;
    }

    $status  = isset($data['TransactionStatus']) ? strtolower(trim($data['TransactionStatus'])) : '';
    $paid    = ($status === 'paid');
    $gw_txn  = isset($data['TransactionId']) ? sanitize_text_field($data['TransactionId']) : '';
    $gw_amt  = isset($data['TransactionAmount']) ? (int) round((float) $data['TransactionAmount']) : 0;
    $our_amt = (int) get_post_meta($sponsorship_id, 'amount', true);

    update_post_meta($sponsorship_id, 'gateway_last_status', $status ?: 'unknown');
    update_post_meta($sponsorship_id, 'gateway_transaction_id', $gw_txn);

    if (!$paid) {
        update_post_meta($sponsorship_id, 'gateway_status', 'failed');
        ahalfa_log("Not paid [$ctx] sponsorship=$sponsorship_id status=$status");
        return false;
    }
    // Amount integrity check (0 from inquiry = tolerate, else must match).
    if ($gw_amt > 0 && $our_amt > 0 && $gw_amt !== $our_amt) {
        update_post_meta($sponsorship_id, 'gateway_status', 'amount_mismatch');
        ahalfa_log("AMOUNT MISMATCH [$ctx] sponsorship=$sponsorship_id ours=$our_amt gw=$gw_amt");
        return false;
    }

    ahalfa_activate($sponsorship_id, $gw_txn);
    ahalfa_log("ACTIVATED [$ctx] sponsorship=$sponsorship_id txn=$gw_txn");
    return true;
}

/* ============================================================================
 * 8. SHARED ACTIVATION — identical to admin "Verify & Link"
 *    (al-Huffaz-sponsor-admin-panel.php :: ajax_sponsor_verify_payment)
 * ========================================================================== */

function ahalfa_activate($sponsorship_id, $gateway_txn = '') {
    wp_cache_flush();

    wp_update_post(array('ID' => $sponsorship_id, 'post_status' => 'publish'));

    update_post_meta($sponsorship_id, 'verification_status', 'approved');
    update_post_meta($sponsorship_id, 'linked', 'yes');
    update_post_meta($sponsorship_id, 'approved_date', current_time('mysql'));
    update_post_meta($sponsorship_id, 'gateway_status', 'paid');
    update_post_meta($sponsorship_id, 'paid_at', current_time('mysql'));
    if ($gateway_txn !== '') {
        update_post_meta($sponsorship_id, 'transaction_id', $gateway_txn);
    }

    $student_id = get_post_meta($sponsorship_id, 'student_id', true);
    if ($student_id) {
        update_post_meta($student_id, 'already_sponsored', 'yes');
        update_post_meta($student_id, 'sponsored_date', current_time('mysql'));
    }

    $sponsor_id = get_post_meta($sponsorship_id, 'sponsor_user_id', true);
    $student    = get_post($student_id);
    $sponsor    = get_userdata($sponsor_id);

    if ($sponsor && $student) {
        wp_mail(
            $sponsor->user_email,
            'Sponsorship Confirmed!',
            "Your sponsorship for {$student->post_title} has been confirmed! Login: " . home_url('/sponsor-dashboard/')
        );
    }

    // Notify admin of the automated (card) activation.
    wp_mail(
        get_option('admin_email'),
        'Card payment received (auto-verified)',
        "A card payment was completed and auto-verified.\n"
        . "Sponsorship ID: {$sponsorship_id}\nStudent: " . ($student ? $student->post_title : $student_id)
        . "\nGateway Txn: {$gateway_txn}"
    );
}

/* ============================================================================
 * 9. HELPERS
 * ========================================================================== */

function ahalfa_find_by_order_ref($order_ref) {
    if ($order_ref === '') return 0;
    $q = get_posts(array(
        'post_type'   => 'sponsorship',
        'post_status' => array('pending', 'publish', 'draft'),
        'numberposts' => 1,
        'fields'      => 'ids',
        'meta_key'    => 'gateway_order_ref',
        'meta_value'  => $order_ref,
    ));
    return $q ? (int) $q[0] : 0;
}

function ahalfa_redirect_result($result, $student_id) {
    $url = add_query_arg(array(
        'alfalah_payment' => $result,          // success | failed | error
        'open_tab'        => 'payments',
    ), home_url('/sponsor-dashboard/'));
    wp_safe_redirect($url);
    exit;
}

function ahalfa_render_autopost($action, $fields, $student_name, $amount, $currency) {
    $rows = '';
    foreach ($fields as $k => $v) {
        $rows .= '<input type="hidden" name="' . esc_attr($k) . '" value="' . esc_attr($v) . '">' . "\n";
    }
    ?><!doctype html><html><head><meta charset="utf-8"><title>Redirecting to secure payment…</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>body{font-family:system-ui,Arial,sans-serif;background:#f5f6f8;display:flex;min-height:100vh;align-items:center;justify-content:center;margin:0}
    .card{background:#fff;padding:32px 40px;border-radius:12px;box-shadow:0 8px 30px rgba(0,0,0,.08);text-align:center;max-width:420px}
    .spin{width:38px;height:38px;border:4px solid #e5e7eb;border-top-color:#16a34a;border-radius:50%;margin:0 auto 18px;animation:s 1s linear infinite}
    @keyframes s{to{transform:rotate(360deg)}}</style></head>
    <body><div class="card"><div class="spin"></div>
    <h2>Redirecting to Bank Alfalah…</h2>
    <p>Sponsoring <strong><?php echo esc_html($student_name); ?></strong><br>
    Amount: <strong><?php echo esc_html($currency . ' ' . number_format($amount)); ?></strong></p>
    <p style="color:#6b7280;font-size:14px">Please do not close or refresh this page.</p>
    <form id="ahalfaForm" method="post" action="<?php echo esc_url($action); ?>"><?php echo $rows; ?>
    <noscript><button type="submit">Continue to payment</button></noscript></form>
    </div><script>document.getElementById('ahalfaForm').submit();</script></body></html><?php
}

function ahalfa_die($msg) {
    wp_die(esc_html($msg), 'Al-Huffaz Payment', array('response' => 200, 'back_link' => true));
}

/* ============================================================================
 * 10. ADMIN SETTINGS PAGE  (Settings > Alfa Gateway)
 * ========================================================================== */

add_action('admin_menu', function () {
    add_options_page('Alfa Gateway', 'Alfa Gateway', 'manage_options', 'ahalfa-settings', 'ahalfa_settings_page');
});
add_action('admin_init', function () {
    register_setting('ahalfa_group', 'ahalfa_settings', 'ahalfa_sanitize_settings');
});

function ahalfa_sanitize_settings($in) {
    $out = ahalfa_settings();
    $out['enabled']      = (isset($in['enabled']) && $in['enabled'] === 'yes') ? 'yes' : 'no';
    $out['environment']  = (isset($in['environment']) && $in['environment'] === 'live') ? 'live' : 'sandbox';
    foreach (array('merchant_id','store_id','merchant_hash','merchant_username','merchant_password','key1','key2','currency') as $k) {
        if (isset($in[$k])) { $out[$k] = trim($in[$k]); }
    }
    return $out;
}

function ahalfa_settings_page() {
    $s = ahalfa_settings();
    $f = function ($k) use ($s) { return isset($s[$k]) ? esc_attr($s[$k]) : ''; };
    ?>
    <div class="wrap">
      <h1>Alfa Payment Gateway (Bank Alfalah) <span style="font-size:13px;color:#059669;">v<?php echo esc_html(AHALFA_VER); ?></span></h1>
      <p>Diagnostic (open in a new tab): <a href="<?php echo esc_url(home_url('/alfalah-ping/')); ?>" target="_blank"><?php echo esc_html(home_url('/alfalah-ping/')); ?></a></p>
      <p><strong>Return URL:</strong> <code><?php echo esc_html(home_url('/alfalah-return/')); ?></code><br>
         <strong>Listener URL:</strong> <code><?php echo esc_html(home_url('/alfalah-listener/')); ?></code></p>
      <form method="post" action="options.php">
        <?php settings_fields('ahalfa_group'); ?>
        <table class="form-table">
          <tr><th>Enable card payments</th><td>
            <label><input type="checkbox" name="ahalfa_settings[enabled]" value="yes" <?php checked($s['enabled'], 'yes'); ?>> Show the "Pay with Card" path</label></td></tr>
          <tr><th>Environment</th><td>
            <select name="ahalfa_settings[environment]">
              <option value="sandbox" <?php selected($s['environment'],'sandbox'); ?>>Sandbox (testing)</option>
              <option value="live" <?php selected($s['environment'],'live'); ?>>Live (production)</option>
            </select></td></tr>
          <tr><th>Merchant ID</th><td><input type="text" class="regular-text" name="ahalfa_settings[merchant_id]" value="<?php echo $f('merchant_id'); ?>"></td></tr>
          <tr><th>Store ID</th><td><input type="text" class="regular-text" name="ahalfa_settings[store_id]" value="<?php echo $f('store_id'); ?>"></td></tr>
          <tr><th>Merchant Hash</th><td><input type="text" class="large-text" name="ahalfa_settings[merchant_hash]" value="<?php echo $f('merchant_hash'); ?>"></td></tr>
          <tr><th>Merchant Username</th><td><input type="text" class="regular-text" name="ahalfa_settings[merchant_username]" value="<?php echo $f('merchant_username'); ?>"></td></tr>
          <tr><th>Merchant Password</th><td><input type="text" class="regular-text" name="ahalfa_settings[merchant_password]" value="<?php echo $f('merchant_password'); ?>"></td></tr>
          <tr><th>Key1 (AES key)</th><td><input type="text" class="regular-text" name="ahalfa_settings[key1]" value="<?php echo $f('key1'); ?>"></td></tr>
          <tr><th>Key2 (AES IV)</th><td><input type="text" class="regular-text" name="ahalfa_settings[key2]" value="<?php echo $f('key2'); ?>"></td></tr>
          <tr><th>Currency</th><td><input type="text" class="small-text" name="ahalfa_settings[currency]" value="<?php echo $f('currency'); ?>"> (PKR)</td></tr>
        </table>
        <?php submit_button('Save Gateway Settings'); ?>
      </form>
      <hr>
      <h2>Test link</h2>
      <p>While logged in as an approved sponsor, open (replace 123 with a donation-eligible student ID):<br>
      <code><?php echo esc_html(home_url('/alfalah-pay/?student=123&type=monthly')); ?></code></p>
    </div>
    <?php
}

/* ============================================================================
 * 11. ACTIVATION — flush rewrite rules
 * ========================================================================== */

register_activation_hook(__FILE__, function () {
    add_rewrite_rule('^alfalah-pay/?$',      'index.php?ahalfa=pay',      'top');
    add_rewrite_rule('^alfalah-return/?$',   'index.php?ahalfa=return',   'top');
    add_rewrite_rule('^alfalah-listener/?$', 'index.php?ahalfa=listener', 'top');
    flush_rewrite_rules();
    update_option('ahalfa_rewrites', '1.0.0');
});
register_deactivation_hook(__FILE__, function () { flush_rewrite_rules(); });
