<?php
/**
 * UAE Integration Test Script
 * 
 * This script helps test the UAE Turnstile integration
 * Run this script to verify the integration is working correctly
 * 
 * IMPORTANT: Remove this file before deploying to production!
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access not allowed');
}

function test_uae_integration() {
    echo "<h2>UAE Turnstile Integration Test</h2>";
    
    // Test 1: Check if UAE plugin is active
    echo "<h3>1. UAE Plugin Status</h3>";
    if (function_exists('cft_is_plugin_active') && cft_is_plugin_active('ultimate-elementor/ultimate-elementor.php')) {
        echo "✅ Ultimate Elementor plugin is ACTIVE<br>";
    } else {
        echo "❌ Ultimate Elementor plugin is NOT ACTIVE<br>";
    }
    
    // Test 2: Check if Turnstile keys are configured
    echo "<h3>2. Turnstile Configuration</h3>";
    $site_key = get_option('cfturnstile_key');
    $secret_key = get_option('cfturnstile_secret');
    
    if (!empty($site_key) && !empty($secret_key)) {
        echo "✅ Turnstile keys are configured<br>";
        echo "Site Key: " . substr($site_key, 0, 10) . "...<br>";
    } else {
        echo "❌ Turnstile keys are NOT configured<br>";
    }
    
    // Test 3: Check if UAE integration options are available
    echo "<h3>3. UAE Integration Settings</h3>";
    $login_enabled = get_option('cfturnstile_uae_login');
    $registration_enabled = get_option('cfturnstile_uae_registration');
    
    echo "Login Forms Integration: " . ($login_enabled ? "✅ ENABLED" : "❌ DISABLED") . "<br>";
    echo "Registration Forms Integration: " . ($registration_enabled ? "✅ ENABLED" : "❌ DISABLED") . "<br>";
    
    // Test 4: Check if integration file exists
    echo "<h3>4. Integration File Check</h3>";
    $integration_file = plugin_dir_path(__FILE__) . 'inc/integrations/forms/ultimate-addons-elementor.php';
    
    if (file_exists($integration_file)) {
        echo "✅ UAE integration file exists<br>";
    } else {
        echo "❌ UAE integration file NOT found<br>";
    }
    
    // Test 5: Check if hooks are registered
    echo "<h3>5. Hook Registration Check</h3>";
    
    if (has_action('uael_login_form_before_submit_button')) {
        echo "✅ Login form display hook is registered<br>";
    } else {
        echo "❌ Login form display hook is NOT registered<br>";
    }
    
    if (has_action('uael_registration_form_before_submit_button')) {
        echo "✅ Registration form display hook is registered<br>";
    } else {
        echo "❌ Registration form display hook is NOT registered<br>";
    }
    
    if (has_action('uael_login_validation')) {
        echo "✅ Login validation hook is registered<br>";
    } else {
        echo "❌ Login validation hook is NOT registered<br>";
    }
    
    if (has_action('uael_registration_validation')) {
        echo "✅ Registration validation hook is registered<br>";
    } else {
        echo "❌ Registration validation hook is NOT registered<br>";
    }
    
    // Test 6: JavaScript file check
    echo "<h3>6. JavaScript File Check</h3>";
    $js_file = plugin_dir_path(__FILE__) . 'js/integrations/uae-forms.js';
    
    if (file_exists($js_file)) {
        echo "✅ UAE JavaScript file exists<br>";
    } else {
        echo "❌ UAE JavaScript file NOT found<br>";
    }
    
    echo "<h3>Integration Summary</h3>";
    echo "<p>If all tests pass with ✅, your UAE Turnstile integration is ready!</p>";
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ol>";
    echo "<li>Go to WordPress Admin → Settings → Cloudflare Turnstile</li>";
    echo "<li>Enable 'Login Forms' and/or 'Registration Forms' under the 'Ultimate Addons for Elementor Forms' section</li>";
    echo "<li>Save settings</li>";
    echo "<li>Test your UAE login/registration forms</li>";
    echo "</ol>";
    
    echo "<p><em>Remember to delete this test file (test-uae-integration.php) before deploying to production!</em></p>";
}

// Only run if accessed via WordPress admin or if current user is admin
if (is_admin() && current_user_can('manage_options')) {
    test_uae_integration();
} else {
    echo "Access denied. Please run this test from WordPress admin with administrator privileges.";
}
?>