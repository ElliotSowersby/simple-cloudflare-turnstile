/* WP */
function turnstileWPCallback() {
    document.querySelectorAll('#wp-submit').forEach(function(el) {
        el.style.pointerEvents = 'auto';
        el.style.opacity = '1';
    });
}
function turnstileCommentCallback() {
    document.querySelectorAll('.cf-turnstile-comment').forEach(function(el) {
        el.style.pointerEvents = 'auto';
        el.style.opacity = '1';
    });
}
/* Woo */
function turnstileWooLoginCallback() {
    document.querySelectorAll('.woocommerce-form-login__submit').forEach(function(el) {
        el.style.pointerEvents = 'auto';
        el.style.opacity = '1';
    });
}
function turnstileWooRegisterCallback() {
    document.querySelectorAll('.woocommerce-form-register__submit').forEach(function(el) {
        el.style.pointerEvents = 'auto';
        el.style.opacity = '1';
    });
}
function turnstileWooResetCallback() {
    document.querySelectorAll('.woocommerce-ResetPassword .button').forEach(function(el) {
        el.style.pointerEvents = 'auto';
        el.style.opacity = '1';
    });
}
/* CF7 */
function turnstileCF7Callback() {
    document.querySelectorAll('.wpcf7-submit').forEach(function(el) {
        el.style.pointerEvents = 'auto';
        el.style.opacity = '1';
    });
}
/* MC4WP */
function turnstileMC4WPCallback() {
    document.querySelectorAll('.mc4wp-form-fields input[type=submit]').forEach(function(el) {
        el.style.pointerEvents = 'auto';
        el.style.opacity = '1';
    });
}
/* BuddyPress */
function turnstileBPCallback() {
    document.querySelectorAll('#buddypress #signup-form .submit').forEach(function(el) {
        el.style.pointerEvents = 'auto';
        el.style.opacity = '1';
    });
}
/* BBPress */
function turnstileBBPressReplyCallback() {
    document.querySelectorAll('#bbp_reply_submit').forEach(function(el) {
        el.style.pointerEvents = 'auto';
        el.style.opacity = '1';
    });
}
/* WPForms */
function turnstileWPFCallback() {
    document.querySelectorAll('.wpforms-submit').forEach(function(el) {
        el.style.pointerEvents = 'auto';
        el.style.opacity = '1';
    });
}
/* Fluent Forms */
function turnstileFluentCallback() {
    document.querySelectorAll('.fluentform .ff-btn-submit').forEach(function(el) {
        el.style.pointerEvents = 'auto';
        el.style.opacity = '1';
    });
}
/* Formidable Forms */
function turnstileFormidableCallback() {
    document.querySelectorAll('.frm_forms .frm_button_submit').forEach(function(el) {
        el.style.pointerEvents = 'auto';
        el.style.opacity = '1';
    });
}
/* Forminator Forms */
function turnstileForminatorCallback() {
    document.querySelectorAll('.forminator-button-submit').forEach(function(el) {
        el.style.pointerEvents = 'auto';
        el.style.opacity = '1';
    });
}
/* Gravity Forms */
function turnstileGravityCallback() {
    document.querySelectorAll('.gform_button').forEach(function(el) {
        el.style.pointerEvents = 'auto';
        el.style.opacity = '1';
    });
}
/* Ultimate Member */
function turnstileUMCallback() {
    document.querySelectorAll('#um-submit-btn').forEach(function(el) {
        el.style.pointerEvents = 'auto';
        el.style.opacity = '1';
    });
}