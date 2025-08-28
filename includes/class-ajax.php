<?php
if (!defined('ABSPATH')) exit;

class TCAT_Ajax {

    public function __construct() {
        // Admin Dashboard AJAX
        add_action('wp_ajax_load_admin_section', [$this, 'load_admin_section']);

        // Applicant Dashboard AJAX
        add_action('wp_ajax_load_applicant_section', [$this, 'load_applicant_section']);
        add_action('wp_ajax_nopriv_load_applicant_section', [$this, 'unauthorized']);

        // (Optional) other global AJAX actions can go here
    }

    /**
     * Load Admin Dashboard Sections
     */
    public function load_admin_section() {
        if (!is_user_logged_in()) {
            wp_send_json_error('Unauthorized');
        }

        $user = wp_get_current_user();
        if (!in_array('tcat_admin', (array)$user->roles)) {
            wp_send_json_error('No permission');
        }

        $section = sanitize_text_field($_GET['section'] ?? 'overview');

        switch ($section) {
            case 'overview':
                include TCAT_ROLE_LOGIN_PATH . 'templates/admin/admin-overview.php';
                break;
            case 'jobs':
                include TCAT_ROLE_LOGIN_PATH . 'templates/admin/admin-jobs.php';
                break;
            case 'applications':
                include TCAT_ROLE_LOGIN_PATH . 'templates/admin/admin-applications.php';
                break;
            case 'reports':
                include TCAT_ROLE_LOGIN_PATH . 'templates/admin/admin-reports.php';
                break;
            case 'settings':
                include TCAT_ROLE_LOGIN_PATH . 'templates/admin/admin-settings.php';
                break;
            default:
                echo '<p>Section not found.</p>';
        }

        wp_die();
    }

    /**
     * Load Applicant Dashboard Sections
     */
    public function load_applicant_section() {
        if (!is_user_logged_in()) {
            wp_send_json_error('Unauthorized');
        }

        $user = wp_get_current_user();
        if (!in_array('tcat_applicant', (array)$user->roles)) {
            wp_send_json_error('No permission');
        }

        $section = sanitize_text_field($_GET['section'] ?? 'jobs');

        switch ($section) {
            case 'jobs':
                include TCAT_ROLE_LOGIN_PATH . 'templates/applicant/applicant-jobs.php';
                break;
            case 'applications':
                include TCAT_ROLE_LOGIN_PATH . 'templates/applicant/applicant-applications.php';
                break;
            case 'saved-jobs':
                include TCAT_ROLE_LOGIN_PATH . 'templates/applicant/applicant-saved-jobs.php';
                break;
            case 'profile':
                include TCAT_ROLE_LOGIN_PATH . 'templates/applicant/applicant-profile.php';
                break;
            case 'settings':
                include TCAT_ROLE_LOGIN_PATH . 'templates/applicant/applicant-settings.php';
                break;
            default:
                echo '<p>Section not found.</p>';
        }

        wp_die();
    }

    /**
     * Handle unauthorized requests
     */
    public function unauthorized() {
        wp_send_json_error('Unauthorized access');
    }
}
