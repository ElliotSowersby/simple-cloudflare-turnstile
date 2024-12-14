<?php
if (!defined("ABSPATH")) {
    exit();
}

if (get_option("cfturnstile_jetpack")) {
    add_filter(
        "jetpack_contact_form_html",
        "cfturnstile_field_jetpack_form",
        10,
        1
    );
    function cfturnstile_field_jetpack_form($html)
    {
        ob_start();
        $unique_id = wp_rand();

        $button_id = ".wp-block-jetpack-contact-form button";
        ob_start();
        cfturnstile_field_show(
            $button_id,
            "",
            "jetpack-form",
            "-jetpack-" . $unique_id
        );
        $cfturnstile = ob_get_contents();
        ob_end_clean();
        wp_reset_postdata();

        $position = strpos($html, "</form>");

        if ($position !== false) {
            $html = substr_replace($html, $cfturnstile, $position, 0);
        }

        return $html;
    }

    add_filter(
        "jetpack_contact_form_is_spam",
        "cfturnstile_jetpack_check",
        10,
        1
    );
    function cfturnstile_jetpack_check($default)
    {
        $check = cfturnstile_check();
        $success = $check["success"];
        if (!$success) {
            $error = new WP_Error(
                "captcha_failed",
                cfturnstile_failed_message()
            );

            // Modify the contact form HTML.
            add_filter("jetpack_contact_form_html", function (
                $format_html
            ) use ($error) {
                return '<div class="contact-form__error">' .
                    $error->get_error_message() .
                    "</div>" .
                    $format_html;
            });

            // Return a custom error to abort the form process submission.
            return $error;
        }
        return $default || !$success;
    }
}
