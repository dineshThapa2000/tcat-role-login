<?php
/*
Plugin Name: TCAT Role Login
Description: Shared login for Admin and Applicant with redirects, dashboard, job preview functionality, and signup with email notifications.
Version: 1.4
Author: Dinesh Thapa
*/

// =====================
// FRONTEND LOGIN FORM CSS
// =====================
function tcat_enqueue_login_styles() {
    wp_enqueue_style(
        'tcat-login-style',
        plugin_dir_url(__FILE__) . 'assets/css/tcat-admin-login.css',
        [],
        '1.0'
    );
}
add_action('wp_enqueue_scripts', 'tcat_enqueue_login_styles');

// =====================
// SHORTCODE LOGIN FORM
// =====================
add_shortcode('tcat_custom_login', 'tcat_custom_login_form');
function tcat_custom_login_form() {
    wp_enqueue_style('tcat-login-style');

    if (is_user_logged_in()) {
        return '<p>You are already logged in. <a href="' . wp_logout_url(home_url()) . '">Logout</a></p>';
    }

    ob_start();

    if (isset($_GET['login']) && $_GET['login'] === 'failed') {
        echo '<p class="tcat-error">Login failed. Please try again.</p>';
    }
    ?>
    <div class="tcat-login-wrapper">
        <form method="post" class="tcat-login-form">
            <h2>Login</h2>
            <div class="tcat-form-group">
                <input type="text" name="log" placeholder="Username or Email" required />
            </div>
            <div class="tcat-form-group">
                <input type="password" name="pwd" placeholder="Password" required />
            </div>
            <div class="tcat-form-group">
                <input type="submit" name="tcat_login" value="Login" />
            </div>
            <div class="tcat-signup">
                <a href="<?php echo home_url('/signup'); ?>">Don't have an account? Sign Up</a>
            </div>
        </form>
        
    </div>
    <?php
    return ob_get_clean();
}

// =====================
// SHORTCODE SIGNUP FORM (Applicants)
// =====================
add_shortcode('tcat_custom_signup', 'tcat_custom_signup_form');
function tcat_custom_signup_form() {
    if (is_user_logged_in()) {
        return '<p>You are already logged in.</p>';
    }

    ob_start();

    if (isset($_GET['signup']) && $_GET['signup'] === 'failed') {
        echo '<p class="tcat-error">Signup failed. Please try again.</p>';
    }
    ?>
    <div class="tcat-signup-wrapper">
        <form method="post" class="tcat-login-form">
            <h2>Applicant Signup</h2>
            <div class="tcat-form-group">
                <input type="text" name="reg_username" placeholder="Username" required />
            </div>
            <div class="tcat-form-group">
                <input type="email" name="reg_email" placeholder="Email" required />
            </div>
            <div class="tcat-form-group">
                <input type="password" name="reg_pwd" placeholder="Password" required />
            </div>
            <div class="tcat-form-group">
                <input type="submit" name="tcat_register" value="Register" />
            </div>
        </form>
    </div>
    <?php
    return ob_get_clean();
}

// =====================
// HANDLE LOGIN SUBMISSION
// =====================
add_action('init', function () {
    if (isset($_POST['tcat_login'])) {
        $username = sanitize_text_field($_POST['log']);
        $password = sanitize_text_field($_POST['pwd']);

        // allow login by email too
        if (is_email($username)) {
            $user_obj = get_user_by('email', $username);
            if ($user_obj) {
                $username = $user_obj->user_login;
            }
        }

        $user = wp_signon([
            'user_login'    => $username,
            'user_password' => $password,
            'remember'      => true,
        ], false);

        if (is_wp_error($user)) {
            wp_redirect(home_url('/login?login=failed'));
            exit;
        }

        if (in_array('administrator', $user->roles, true)) {
            wp_redirect(admin_url());
        } elseif (in_array('tcat_admin', $user->roles, true)) {
            wp_redirect(home_url('/admin-dashboard'));
        } elseif (in_array('tcat_applicant', $user->roles, true)) {
            wp_redirect(home_url('/applicant-dashboard'));
        } else {
            wp_redirect(home_url());
        }
        exit;
    }
});

// =====================
// HANDLE SIGNUP SUBMISSION
// =====================
add_action('init', function () {
    if (isset($_POST['tcat_register'])) {
        $username = sanitize_user($_POST['reg_username']);
        $email    = sanitize_email($_POST['reg_email']);
        $password = sanitize_text_field($_POST['reg_pwd']);

        if (username_exists($username) || email_exists($email)) {
            wp_redirect(home_url('/signup?signup=failed'));
            exit;
        }

        $user_id = wp_create_user($username, $password, $email);
        if (is_wp_error($user_id)) {
            wp_redirect(home_url('/signup?signup=failed'));
            exit;
        }

        // Assign applicant role
        $user = new WP_User($user_id);
        $user->set_role('tcat_applicant');

        // Applicant Welcome Email
        $subject = "Welcome to TCAT Careers Portal";
        $login_url = home_url('/login');
        $message = "
Hello {$username},

Thank you for registering with the TCAT Careers Portal.

You can log in anytime using the details below:

Username: {$username}
Login URL: {$login_url}

Please keep your password safe (for security reasons, we do not email your password).

Best regards,
TCAT Careers Team
";
        wp_mail($email, $subject, $message);

        // Notify Super Admin
        $admin_email = get_option('admin_email');
        $notify_subject = "New Applicant Registration - TCAT Careers";
        $notify_message = "A new applicant has registered:\n\nUsername: {$username}\nEmail: {$email}\n";
        wp_mail($admin_email, $notify_subject, $notify_message);

        // Auto-login after registration
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);
        wp_redirect(home_url('/applicant-dashboard'));
        exit;
    }
});

// =====================
// CREATE CUSTOM ROLES
// =====================
register_activation_hook(__FILE__, function () {
    remove_role('tcat_admin');
    add_role('tcat_admin', 'TCAT Admin', [
        'read' => true,
        'edit_posts' => false,
        'delete_posts' => false,
    ]);
    remove_role('tcat_applicant');
    add_role('tcat_applicant', 'TCAT Applicant', [
        'read' => true,
    ]);
});

// =====================
// DASHBOARD ACCESS RESTRICTIONS
// =====================
add_action('template_redirect', function () {
    if (is_page('admin-dashboard')) {
        if (!current_user_can('tcat_admin') && !current_user_can('administrator')) {
            wp_redirect(home_url('/login'));
            exit;
        }
    }
    if (is_page('applicant-dashboard')) {
        if (!current_user_can('tcat_applicant')) {
            wp_redirect(home_url('/login'));
            exit;
        }
    }
});

// =====================
// PREVENT SEARCH ENGINE INDEXING
// =====================
add_action('wp_head', function () {
    if (is_page('admin-dashboard') || is_page('applicant-dashboard')) {
        echo '<meta name="robots" content="noindex, nofollow" />' . "\n";
    }
});

// =====================
// LOAD ADMIN DASHBOARD CONTENT
// =====================
add_filter('the_content', function ($content) {
    if (is_page('admin-dashboard') && (current_user_can('tcat_admin') || current_user_can('administrator'))) {
        $admin_name = wp_get_current_user()->display_name;
        ob_start();
        ?>
        <div class="tcat-admin-dashboard">
            <header class="tcat-header">
                <div class="tcat-logo">TCAT Portal</div>
                <div class="tcat-header-right">
                    <span>Welcome, <?php echo esc_html($admin_name); ?></span>
                    <a class="tcat-logout" href="<?php echo wp_logout_url(home_url()); ?>">Logout</a>
                </div>
            </header>
            <div class="tcat-body">
                <aside class="tcat-sidebar">
                    <ul>
                        <li><a href="#" data-section="overview">Overview</a></li>
                        <li><a href="#" data-section="jobs">Jobs</a></li>
                        <li><a href="#" data-section="applications">Applications</a></li>
                        <li><a href="#" data-section="reports">Reports</a></li>
                        <li><a href="#" data-section="settings">Settings</a></li>
                    </ul>
                </aside>
                <main id="tcat-admin-content">
                    <h2>Welcome to the Admin Dashboard</h2>
                    <p>Select a menu item from the sidebar to get started.</p>
                </main>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    return $content;
});

// =====================
// ENQUEUE DASHBOARD SCRIPTS & STYLES
// =====================
add_action('wp_enqueue_scripts', function () {
    if (is_page('admin-dashboard') && (current_user_can('tcat_admin') || current_user_can('administrator'))) {
        wp_enqueue_style('dashicons');

        // Main dashboard CSS
        wp_enqueue_style(
            'tcat-dashboard-style',
            plugin_dir_url(__FILE__) . 'assets/css/tcat-admin-overview.css',
            [],
            '1.0'
        );

        // Admin Jobs CSS
        wp_enqueue_style(
            'tcat-admin-jobs-style',
            plugin_dir_url(__FILE__) . 'assets/css/admin-jobs.css',
            [],
            '1.0'
        );
        
        // Dashboard JS
        wp_enqueue_script(
            'tcat-dashboard-js',
            plugin_dir_url(__FILE__) . 'assets/js/tcat-admin-dashboard.js',
            ['jquery'],
            '1.0',
            true
        );

        // Localize AJAX for preview & dashboard actions
        wp_localize_script('tcat-dashboard-js', 'tcat_dashboard', [
            'ajaxurl'    => admin_url('admin-ajax.php'),
            'nonce'      => wp_create_nonce('tcat_dashboard_nonce'),
            'plugin_url' => plugin_dir_url(__FILE__)
        ]);
    }
});

// =====================
// AJAX HANDLER FOR DASHBOARD SECTIONS
// =====================
add_action('wp_ajax_load_admin_section', function () {
    if (!current_user_can('tcat_admin') && !current_user_can('administrator')) {
        wp_die('Unauthorized');
    }

    $section = isset($_GET['section']) ? sanitize_text_field($_GET['section']) : 'overview';
    $template_file = plugin_dir_path(__FILE__) . "templates/admin-{$section}.php";

    if (file_exists($template_file)) {
        include $template_file;
    } else {
        echo '<p>Section not found.</p>';
    }

    wp_die();
});

// =====================
// AJAX HANDLER FOR JOB PREVIEW
// =====================
add_action('wp_ajax_get_job_details', 'tcat_get_job_details');
function tcat_get_job_details() {
    if (!current_user_can('tcat_admin') && !current_user_can('administrator')) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }

    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tcat_ajax_nonce')) {
        wp_send_json_error(['message' => 'Invalid nonce']);
    }

    $job_id = intval($_POST['job_id'] ?? 0);
    if (!$job_id) wp_send_json_error(['message' => 'Invalid job ID']);

    $job = get_post($job_id);
    if (!$job) wp_send_json_error(['message' => 'Job not found']);

    $data = [
        'title'          => get_the_title($job_id),
        'type'           => implode(', ', wp_get_post_terms($job_id, 'job_type', ['fields'=>'names'])),
        'school'         => implode(', ', wp_get_post_terms($job_id, 'school', ['fields'=>'names'])),
        'closing_date'   => get_post_meta($job_id, '_tcat_closing_date', true),
        'salary'         => get_post_meta($job_id, '_tcat_salary', true),
        'contract_type'  => get_post_meta($job_id, '_tcat_contract_type', true),
        'contract_hours' => get_post_meta($job_id, '_tcat_contract_hours', true),
        'overview'       => get_post_meta($job_id, '_tcat_overview', true),
        'job_description'=> apply_filters('the_content', $job->post_content),
    ];

    wp_send_json_success($data);
}

// =====================
// DISABLE ADMIN BAR FOR CUSTOM ROLES
// =====================
add_action('after_setup_theme', function () {
    if (current_user_can('tcat_admin') || current_user_can('tcat_applicant')) {
        show_admin_bar(false);
    }
});
