<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
* Check if whitelisted
*/
function cfturnstile_whitelisted() {
    // If admin page return false
    if(isset($_GET['page']) && $_GET['page'] == 'cfturnstile') {
        return false;
    }
    // Filter
    $whitelisted = apply_filters('cfturnstile_whitelisted', false);
    // Logged In Users
    if(get_option('cfturnstile_whitelist_users') && is_user_logged_in()) {
        $whitelisted = true;
    }
    // If the IP address is within the list of IPs in get_option('cfturnstile_whitelist_ips') whist is a textarea field seperated one per line
    if(get_option('cfturnstile_whitelist_ips')) {
        $whitelist = get_option('cfturnstile_whitelist_ips');
        $whitelist_ips = explode("\n", str_replace("\r", "", $whitelist));
        $current_ip = cfturnstile_get_ip();
        foreach ($whitelist_ips as $whitelist_ip) {
            $whitelist_ip = sanitize_text_field(trim($whitelist_ip));
            // Skip invalid inputs
            if (!filter_var($whitelist_ip, FILTER_VALIDATE_IP)) {
                continue;
            }
            // Check if the IP is exactly equal
            if ($current_ip && $current_ip == $whitelist_ip) {
                $whitelisted = true;
                break;
            }
        }
    }
    return $whitelisted;
}

/**
 * Get IP Address
 */
function cfturnstile_get_ip() {
    if (isset( $_SERVER )) {
        $vars = array( 'REMOTE_ADDR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR' );
        foreach ( $vars as $var ) {
            if ( isset($_SERVER[$var]) ) {
                $ips = explode(',', $_SERVER[$var]); // handle comma separated values
                foreach ($ips as $ip) {
                    $ip = sanitize_text_field(trim($ip));
                    if (filter_var($ip, FILTER_VALIDATE_IP)) {
                        return $ip;
                    }
                }
            }
        }
    }
    return false; // return false if no valid ip found
}