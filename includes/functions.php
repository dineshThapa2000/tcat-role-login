<?php
if (!defined('ABSPATH')) exit;

/**
 * Redirect user to correct dashboard after login
 */
function tcat_redirect_after_login($redirect_to, $request, $user) {
    if (isset($user->roles) && is_array($user->roles)) {
        if (in_array('tcat_admin', $user->roles)) {
            return site_url('/admin-dashboard/');
        } elseif (in_array('tcat_applicant', $user->roles)) {
            return site_url('/applicant-dashboard/');
        }
    }
    return $redirect_to;
}
add_filter('login_redirect', 'tcat_redirect_after_login', 10, 3);

/**
 * Get current user role
 */
function tcat_get_current_user_role() {
    if (!is_user_logged_in()) return false;
    $user = wp_get_current_user();
    return $user->roles[0] ?? false;
}
