<?php
/*
Plugin Name: TCAT Role Login
Description: Shared login for Admin and Applicant with redirects, dashboards, job preview functionality, and signup with email notifications.
Version: 2.0
Author: Dinesh Thapa
*/

// Security check
if (!defined('ABSPATH')) exit;

// ===================
// DEFINE CONSTANTS
// ===================
define('TCAT_ROLE_LOGIN_PATH', plugin_dir_path(__FILE__));
define('TCAT_ROLE_LOGIN_URL', plugin_dir_url(__FILE__));

// ===================
// INCLUDE CORE FILES
// ===================
require_once TCAT_ROLE_LOGIN_PATH . 'includes/functions.php';  
require_once TCAT_ROLE_LOGIN_PATH . 'includes/class-roles.php';
require_once TCAT_ROLE_LOGIN_PATH . 'includes/class-login.php';
require_once TCAT_ROLE_LOGIN_PATH . 'includes/class-signup.php';
require_once TCAT_ROLE_LOGIN_PATH . 'includes/class-dashboard-admin.php';
require_once TCAT_ROLE_LOGIN_PATH . 'includes/class-dashboard-applicant.php';
require_once TCAT_ROLE_LOGIN_PATH . 'includes/class-ajax.php';

// ===================
// INITIALIZE PLUGIN
// ===================
function tcat_role_login_init() {
    new TCAT_Login();
    new TCAT_Signup();
    new TCAT_Role_Manager();
    new TCAT_Dashboard_Admin();
    new TCAT_Applicant_Dashboard();
    new TCAT_Ajax();

}
add_action('plugins_loaded', 'tcat_role_login_init');
