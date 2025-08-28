<?php
if (!defined('ABSPATH')) exit;

class TCAT_Role_Manager {

    public function __construct() {
        // Run on plugin activation
        register_activation_hook(TCAT_ROLE_LOGIN_PATH . 'tcat-role-login.php', [$this, 'add_roles']);

        // Optional: remove roles on plugin deactivation
        register_deactivation_hook(TCAT_ROLE_LOGIN_PATH . 'tcat-role-login.php', [$this, 'remove_roles']);
    }

    /**
     * Add custom roles
     */
    public function add_roles() {
        // Applicant role
        if (!get_role('tcat_applicant')) {
            add_role(
                'tcat_applicant',
                'TCAT Applicant',
                [
                    'read' => true,
                    'edit_posts' => false,
                    'delete_posts' => false,
                ]
            );
        }

        // Custom Admin role
        if (!get_role('tcat_admin')) {
            add_role(
                'tcat_admin',
                'TCAT Admin',
                [
                    'read' => true,
                    'edit_posts' => true,
                    'delete_posts' => true,
                    'publish_posts' => true,
                    'upload_files' => true,
                ]
            );
        }
    }

    /**
     * Remove roles on plugin deactivation
     */
    public function remove_roles() {
        if (get_role('tcat_applicant')) remove_role('tcat_applicant');
        if (get_role('tcat_admin')) remove_role('tcat_admin');
    }
}
