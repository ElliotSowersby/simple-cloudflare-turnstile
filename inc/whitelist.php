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
    // If the IP address is within the list of IPs in get_option('cfturnstile_whitelist_ips')
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
    // Excluded Page URLs
    if (get_option('cfturnstile_whitelist_pages')) {
        $whitelist_pages = explode("\n", str_replace("\r", "", get_option('cfturnstile_whitelist_pages')));
        
        $current_url = '';
        if ( isset( $_SERVER['REQUEST_URI'] ) ) {
            $current_url = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
        }

        foreach ( $whitelist_pages as $whitelist_page ) {
            $whitelist_page = sanitize_text_field( trim( $whitelist_page ) );
            if ( $current_url && $whitelist_page && strpos( $current_url, $whitelist_page ) !== false ) {
                $whitelisted = true;
                break;
            }
        }
    }
    // If the User Agent is within the list of User Agents in get_option('cfturnstile_whitelist_agents')
    if (get_option( 'cfturnstile_whitelist_agents' )) {
        $whitelist        = get_option( 'cfturnstile_whitelist_agents' );
        $whitelist_agents = explode("\n", str_replace("\r", "", $whitelist));

        $current_agent = '';
        if ( isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
            $current_agent = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );
        }

        foreach ( $whitelist_agents as $whitelist_agent ) {
            $whitelist_agent = sanitize_text_field( trim( $whitelist_agent ) );
            // Check if the User Agent contains the whitelist agent
            if ( $current_agent && $whitelist_agent && strpos( $current_agent, $whitelist_agent ) !== false ) {
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
    // Helper: validate public IP (no private/reserved ranges)
    $is_public_ip = function ( $ip ) {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    };

    // 1. Cloudflare header – trusted if site is known to be behind Cloudflare
    if ( ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
        $ip = sanitize_text_field( trim( wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) );
        if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
            return $ip;
        }
    }

    // 2. Common proxy headers
    $proxy_headers = array(
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_REAL_IP',
        'HTTP_X_CLIENT_IP',
        'HTTP_CLIENT_IP',
        'HTTP_X_CLUSTER_CLIENT_IP',
    );

    foreach ( $proxy_headers as $header ) {
        if ( empty( $_SERVER[ $header ] ) ) {
            continue;
        }

        // Can be a comma-separated list (especially X-Forwarded-For)
        $ips = explode( ',', wp_unslash( $_SERVER[ $header ] ) );
        foreach ( $ips as $ip ) {
            $ip = sanitize_text_field( trim( $ip ) );
            if ( $is_public_ip( $ip ) ) {
                return $ip;
            }
        }

        // If no public IP found, fall back to first valid IP in the header
        foreach ( $ips as $ip ) {
            $ip = sanitize_text_field( trim( $ip ) );
            if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
                return $ip;
            }
        }
    }

    // 3. Fallback to REMOTE_ADDR
    if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
        $ip = sanitize_text_field( trim( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) );
        if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
            return $ip;
        }
    }

    return false; // return false if no valid IP found
}