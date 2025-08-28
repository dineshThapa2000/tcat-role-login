<?php
if (!defined('ABSPATH')) exit;

class TCAT_Dashboard_Admin {

    public function __construct() {
        add_filter('the_content', [$this, 'load_dashboard_content']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_load_admin_section', [$this, 'ajax_load_section']);
        add_action('wp_ajax_get_job_details', [$this, 'ajax_get_job_details']);

        // 🚨 Restrict direct access to admin dashboard page
        add_action('template_redirect', [$this, 'restrict_page_access']);
    }

    /**
     * Restrict direct access to admin dashboard
     */
    public function restrict_page_access() {
        if (is_page('admin-dashboard')) {
            // Not logged in → redirect to login
            if (!is_user_logged_in()) {
                wp_safe_redirect(home_url('/login'));
                exit;
            }

            // Logged in but wrong role → redirect home
            $user = wp_get_current_user();
            if (!array_intersect(['tcat_admin','administrator'], $user->roles)) {
                wp_safe_redirect(home_url());
                exit;
            }
        }
    }

    public function enqueue_assets() {
        $user = wp_get_current_user();
        if (!array_intersect(['tcat_admin','administrator'], $user->roles)) return;

        wp_enqueue_style('dashicons');
        wp_enqueue_style('tcat-dashboard-style', plugin_dir_url(__FILE__) . '../assets/css/tcat-admin-overview.css', [], '1.0');
        wp_enqueue_style('tcat-admin-jobs-style', plugin_dir_url(__FILE__) . '../assets/css/admin-jobs.css', [], '1.0');

        wp_enqueue_script('tcat-dashboard-js', plugin_dir_url(__FILE__) . '../assets/js/tcat-admin-dashboard.js', ['jquery'], '1.0', true);
        wp_enqueue_script('tcat-admin-jobs-js', plugin_dir_url(__FILE__) . '../assets/js/admin-jobs.js', ['jquery'], '1.0', true);

        wp_localize_script('tcat-dashboard-js', 'tcat_dashboard', [
            'ajaxurl'    => admin_url('admin-ajax.php'),
            'nonce'      => wp_create_nonce('tcat_ajax_nonce'),
            'plugin_url' => plugin_dir_url(__FILE__),
        ]);
    }

    public function load_dashboard_content($content) {
        $user = wp_get_current_user();
        if (!is_page('admin-dashboard') || !array_intersect(['tcat_admin','administrator'], $user->roles)) return $content;

        $admin_name = esc_html($user->display_name);
        ob_start(); ?>
        <div class="tcat-admin-dashboard">
            <header class="tcat-header">
                <div class="tcat-logo">TCAT Portal</div>
                <div class="tcat-header-right">
                    <span>Welcome, <?php echo $admin_name; ?></span>
                    <a class="tcat-logout" href="<?php echo esc_url(wp_logout_url(home_url('/login'))); ?>">Logout</a>

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

    public function ajax_load_section() {
        $user = wp_get_current_user();
        if (!array_intersect(['tcat_admin','administrator'], $user->roles)) wp_die('Unauthorized');

        $section = isset($_GET['section']) ? sanitize_text_field($_GET['section']) : 'overview';
        $template_file = plugin_dir_path(__FILE__) . "../templates/admin/admin-{$section}.php";

        if (file_exists($template_file)) {
            include $template_file;
        } else {
            echo '<p>Section not found.</p>';
        }
        wp_die();
    }

    public function ajax_get_job_details() {
        $user = wp_get_current_user();
        if (!array_intersect(['tcat_admin','administrator'], $user->roles)) {
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
            'job_type'       => implode(', ', wp_get_post_terms($job_id, 'job_type', ['fields'=>'names'])),
            'job_category'   => implode(', ', wp_get_post_terms($job_id, 'job_category', ['fields'=>'names'])),
            'school'         => implode(', ', wp_get_post_terms($job_id, 'school', ['fields'=>'names'])),
            'location'       => get_post_meta($job_id, '_tcat_location', true),
            'closing_date'   => get_post_meta($job_id, '_tcat_closing_date', true),
            'salary'         => get_post_meta($job_id, '_tcat_salary', true),
            'contract_type'  => get_post_meta($job_id, '_tcat_contract_type', true),
            'contract_hours' => get_post_meta($job_id, '_tcat_contract_hours', true),
            'overview'       => get_post_meta($job_id, '_tcat_overview', true),
            'attachment'     => get_post_meta($job_id, '_tcat_attachment', true),
            'date_posted'    => get_the_date('', $job_id),
            'job_description'=> apply_filters('the_content', $job->post_content),
        ];

        wp_send_json_success($data);
    }
}

new TCAT_Dashboard_Admin();
