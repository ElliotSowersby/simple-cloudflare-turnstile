<?php
/**
 * Ultimate Addons for Elementor Forms Integration
 * 
 * @package Simple Cloudflare Turnstile
 * @since 1.34.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Check if UAE plugin is active
 */
function cft_is_uae_active() {
    return cft_is_plugin_active('ultimate-elementor/ultimate-elementor.php');
}

// Only proceed if UAE is active and integration is enabled
if ( cft_is_uae_active() && (get_option('cfturnstile_uae_login') || get_option('cfturnstile_uae_registration')) ) {

    /**
     * Enqueue Turnstile scripts when UAE forms are present
     */
    add_action('wp_enqueue_scripts', 'cfturnstile_uae_enqueue_scripts');
    function cfturnstile_uae_enqueue_scripts() {
        // Check if we're on a page that might have UAE forms
        if ( is_admin() || wp_doing_ajax() ) {
            return;
        }
        
        // Enqueue scripts for UAE forms
        do_action('cfturnstile_enqueue_scripts');
        
        // Enqueue UAE-specific JavaScript
        $defer = get_option('cfturnstile_defer_scripts', 1) ? array('strategy' => 'defer') : array();
        wp_enqueue_script(
            'cfturnstile-uae-forms',
            plugins_url('simple-cloudflare-turnstile/js/integrations/uae-forms.js'),
            array('jquery', 'cfturnstile'),
            '1.0',
            $defer
        );
    }

    /**
     * Display Turnstile widget before UAE login form submit button
     */
    if ( get_option('cfturnstile_uae_login') ) {
        add_action('uael_login_form_before_submit_button', 'cfturnstile_uae_login_display', 10, 2);
    }
    
    function cfturnstile_uae_login_display( $settings, $node_id ) {
        if ( !cfturnstile_whitelisted() ) {
            echo cfturnstile_uae_widget_html( 'login', $node_id );
        }
    }

    /**
     * Display Turnstile widget before UAE registration form submit button
     */
    if ( get_option('cfturnstile_uae_registration') ) {
        add_action('uael_registration_form_before_submit_button', 'cfturnstile_uae_registration_display', 10, 2);
    }
    
    function cfturnstile_uae_registration_display( $settings, $node_id ) {
        if ( !cfturnstile_whitelisted() ) {
            echo cfturnstile_uae_widget_html( 'registration', $node_id );
        }
    }

    /**
     * Validate Turnstile on UAE login form submission
     */
    if ( get_option('cfturnstile_uae_login') ) {
        add_action('uael_login_validation', 'cfturnstile_uae_login_validate', 5, 1);
    }
    
    function cfturnstile_uae_login_validate( $credentials ) {
        if ( !cfturnstile_whitelisted() ) {
            $error_message = cfturnstile_failed_message();
            
            if ( !isset($_POST['cf-turnstile-response']) || empty($_POST['cf-turnstile-response']) ) {
                wp_die( 
                    esc_html( $error_message ), 
                    esc_html__( 'Login Failed', 'simple-cloudflare-turnstile' ), 
                    array( 'back_link' => true ) 
                );
            }
            
            $check = cfturnstile_check();
            if ( !$check['success'] ) {
                // Log the failed attempt if debug logging is enabled
                if ( get_option('cfturnstile_logging') ) {
                    cfturnstile_log_failed_attempt( 'UAE Login Form', $check );
                }
                
                wp_die( 
                    esc_html( $error_message ), 
                    esc_html__( 'Login Failed', 'simple-cloudflare-turnstile' ), 
                    array( 'back_link' => true ) 
                );
            }
            
            // Log successful validation if debug logging is enabled
            if ( get_option('cfturnstile_logging') ) {
                cfturnstile_log_success( 'UAE Login Form' );
            }
        }
    }

    /**
     * Validate Turnstile on UAE registration form submission
     */
    if ( get_option('cfturnstile_uae_registration') ) {
        add_action('uael_registration_validation', 'cfturnstile_uae_registration_validate', 5, 1);
    }
    
    function cfturnstile_uae_registration_validate( $user_data ) {
        if ( !cfturnstile_whitelisted() ) {
            $error_message = cfturnstile_failed_message();
            
            if ( !isset($_POST['cf-turnstile-response']) || empty($_POST['cf-turnstile-response']) ) {
                wp_die( 
                    esc_html( $error_message ), 
                    esc_html__( 'Registration Failed', 'simple-cloudflare-turnstile' ), 
                    array( 'back_link' => true ) 
                );
            }
            
            $check = cfturnstile_check();
            if ( !$check['success'] ) {
                // Log the failed attempt if debug logging is enabled
                if ( get_option('cfturnstile_logging') ) {
                    cfturnstile_log_failed_attempt( 'UAE Registration Form', $check );
                }
                
                wp_die( 
                    esc_html( $error_message ), 
                    esc_html__( 'Registration Failed', 'simple-cloudflare-turnstile' ), 
                    array( 'back_link' => true ) 
                );
            }
            
            // Log successful validation if debug logging is enabled
            if ( get_option('cfturnstile_logging') ) {
                cfturnstile_log_success( 'UAE Registration Form' );
            }
        }
    }

    /**
     * Generate Turnstile widget HTML for UAE forms
     * 
     * @param string $form_type Type of form (login or registration)
     * @param string $node_id Unique node ID from UAE
     * @return string HTML for Turnstile widget
     */
    function cfturnstile_uae_widget_html( $form_type, $node_id ) {
        $widget_id = 'cfturnstile-uae-' . $form_type . '-' . $node_id;
        $theme = get_option('cfturnstile_theme', 'auto');
        $language = get_option('cfturnstile_language', 'auto');
        $mode = get_option('cfturnstile_mode', 'managed');
        $size = get_option('cfturnstile_size', 'normal');
        
        $html = '<div class="cfturnstile-uae-container" style="margin: 10px 0;">';
        $html .= '<div id="' . esc_attr( $widget_id ) . '" class="cf-turnstile" 
                      data-sitekey="' . esc_attr( get_option('cfturnstile_key') ) . '" 
                      data-theme="' . esc_attr( $theme ) . '" 
                      data-language="' . esc_attr( $language ) . '"
                      data-appearance="' . esc_attr( $mode ) . '"
                      data-size="' . esc_attr( $size ) . '"
                      data-action="uae_' . esc_attr( $form_type ) . '_form"
                      data-retry="auto"
                      data-retry-interval="8000"></div>';
        
        // Add JavaScript to render the widget
        $html .= '<script type="text/javascript">
            if (typeof window.onloadTurnstileCallback === "undefined") {
                window.onloadTurnstileCallback = function () {
                    if (typeof turnstile !== "undefined") {
                        turnstile.render("#' . esc_js( $widget_id ) . '");
                    }
                };
            } else {
                if (typeof turnstile !== "undefined") {
                    turnstile.render("#' . esc_js( $widget_id ) . '");
                }
            }
        </script>';
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Log failed Turnstile attempts (if logging is enabled)
     */
    function cfturnstile_log_failed_attempt( $form_type, $check_result ) {
        if ( !function_exists('cfturnstile_log') ) {
            return;
        }
        
        $error_code = isset($check_result['error_code']) ? $check_result['error_code'] : 'unknown';
        cfturnstile_log( "Failed validation on {$form_type} - Error: {$error_code}" );
    }

    /**
     * Log successful Turnstile validations (if logging is enabled)
     */
    function cfturnstile_log_success( $form_type ) {
        if ( !function_exists('cfturnstile_log') ) {
            return;
        }
        
        cfturnstile_log( "Successful validation on {$form_type}" );
    }

}