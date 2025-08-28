<?php
if (!defined('ABSPATH')) exit;

class TCAT_Applicant_Dashboard {

    public function __construct() {
        // Replace page content dynamically
        add_filter('the_content', [$this, 'load_dashboard_page']);

        // AJAX loader for sidebar sections
        add_action('wp_ajax_load_applicant_section', [$this, 'ajax_load_section']);

        // Job details preview
        add_action('wp_ajax_get_job_details', [$this, 'ajax_get_job_details']);

        // Restrict direct page access
        add_action('template_redirect', [$this, 'restrict_page_access']);

        // Enqueue CSS/JS
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Restrict direct access to applicant dashboard
     */
    public function restrict_page_access() {
        if (!is_page('applicant-dashboard')) return;

        // Not logged in → redirect to login
        if (!is_user_logged_in()) {
            wp_safe_redirect(home_url('/login'));
            exit;
        }

        // Wrong role → redirect home
        $user = wp_get_current_user();
        if (!in_array('tcat_applicant', (array)$user->roles, true)) {
            wp_safe_redirect(home_url());
            exit;
        }
    }

    /**
     * Enqueue styles & scripts
     */
    public function enqueue_assets() {
        $user = wp_get_current_user();
        if (!is_page('applicant-dashboard') || !in_array('tcat_applicant', (array)$user->roles)) return;

        wp_enqueue_style('dashicons');
        wp_enqueue_style('tcat-applicant-dashboard', plugin_dir_url(__FILE__) . '../assets/css/tcat-applicant-dashboard.css', [], '1.0');
        wp_enqueue_style('tcat-admin-jobs-style', plugin_dir_url(__FILE__) . '../assets/css/admin-jobs.css', [], '1.0');
        wp_enqueue_style('tcat-applicant-jobs', plugin_dir_url(__FILE__) . '../assets/css/tcat-applicant-jobs.css', [], '1.0');

        // Dashboard JS
        wp_enqueue_script(
            'tcat-applicant-dashboard-js',
            plugin_dir_url(__FILE__) . '../assets/js/tcat-applicant-dashboard.js',
            ['jquery'],
            '1.0',
            true
        );

        // Jobs JS
        wp_enqueue_script(
            'tcat-applicant-jobs-js',
            plugin_dir_url(__FILE__) . '../assets/js/tcat-applicant-jobs.js',
            ['jquery'],
            '1.0',
            true
        );

        // Localize data for both scripts
        $localize_data = [
            'ajaxurl'    => admin_url('admin-ajax.php'),
            'nonce'      => wp_create_nonce('tcat_ajax_nonce'),
            'plugin_url' => plugin_dir_url(__FILE__),
        ];

        wp_localize_script('tcat-applicant-dashboard-js', 'tcat_applicant_dashboard', $localize_data);
        wp_localize_script('tcat-applicant-jobs-js', 'tcat_applicant_dashboard', $localize_data);
    }


    /**
     * Replace content with applicant dashboard
     */
    public function load_dashboard_page($content) {
        if (!is_page('applicant-dashboard')) return $content;

        $user = wp_get_current_user();
        ob_start(); ?>
        <div class="tcat-applicant-dashboard">
            <header class="tcat-header">
                <div class="tcat-logo">TCAT Portal</div>
                <div class="tcat-header-right">
                    <span>Welcome, <?php echo esc_html($user->display_name); ?></span>
                    <a class="tcat-logout" href="<?php echo esc_url(wp_logout_url(home_url('/login'))); ?>">Logout</a>
                </div>
            </header>

            <div class="tcat-body">
                <aside class="tcat-sidebar">
                    <ul>
                        <li><a href="#" data-section="jobs"><span class="dashicons dashicons-portfolio"></span> Jobs</a></li>
                        <li><a href="#" data-section="applications"><span class="dashicons dashicons-clipboard"></span> My Applications</a></li>
                        <li><a href="#" data-section="saved-jobs"><span class="dashicons dashicons-heart"></span> Saved Jobs</a></li>
                        <li><a href="#" data-section="profile"><span class="dashicons dashicons-admin-users"></span> Profile</a></li>
                        <li><a href="#" data-section="settings"><span class="dashicons dashicons-admin-generic"></span> Settings</a></li>
                    </ul>
                </aside>
                <main id="tcat-applicant-content" class="tcat-main-content">
                    <h2>Welcome to your Applicant Dashboard</h2>
                    <p>Select a menu item from the sidebar to get started.</p>
                </main>
            </div>
        </div>

        <!-- Job Preview Panel -->
        <div id="tcat-job-preview-panel" style="display:none;">
            <button id="close-job-preview">Close</button>
            <div id="job-preview-content"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * AJAX: Load sections dynamically
     */
    public function ajax_load_section() {
        $user = wp_get_current_user();
        if (!is_user_logged_in() || !in_array('tcat_applicant', (array)$user->roles)) {
            wp_die('Unauthorized');
        }

        $section = sanitize_text_field($_GET['section'] ?? 'jobs');
        $template_file = plugin_dir_path(__FILE__) . "../templates/applicant/applicant-{$section}.php";

        if (file_exists($template_file)) {
            include $template_file;
        } else {
            echo '<p>Section not found.</p>';
        }
        wp_die();
    }

    /**
 * AJAX: Job details (Admins + Applicants)
 */ 
    public function ajax_get_job_details() {
        // Verify nonce
        check_ajax_referer('tcat_ajax_nonce', 'nonce');

        // Check logged-in status
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $user = wp_get_current_user();
        // Allow only applicants or admins
        if (!array_intersect(['tcat_applicant', 'administrator'], (array)$user->roles)) {
            wp_send_json_error(['message' => 'No permission']);
        }

        // Get job ID from POST
        $job_id = intval($_POST['job_id'] ?? 0);
        if (!$job_id) wp_send_json_error(['message' => 'Invalid job ID']);

        $post = get_post($job_id);
        if (!$post || $post->post_type !== 'tcat_job') {
            wp_send_json_error(['message' => 'Job not found']);
        }

        // Prepare job data
        $data = [
            'title'          => get_the_title($job_id),
            'job_type'       => implode(', ', wp_get_post_terms($job_id, 'job_type', ['fields'=>'names'])),
            'job_category'   => implode(', ', wp_get_post_terms($job_id, 'job_category', ['fields'=>'names'])),
            'school'         => implode(', ', wp_get_post_terms($job_id, 'school', ['fields'=>'names'])),
            'location'       => get_post_meta($job_id, '_tcat_location', true) ?: 'N/A',
            'closing_date'   => get_post_meta($job_id, '_tcat_closing_date', true) ?: 'N/A',
            'salary'         => get_post_meta($job_id, '_tcat_salary', true) ?: 'N/A',
            'contract_type'  => get_post_meta($job_id, '_tcat_contract_type', true) ?: 'N/A',
            'contract_hours' => get_post_meta($job_id, '_tcat_contract_hours', true) ?: 'N/A',
            'overview'       => get_post_meta($job_id, '_tcat_overview', true) ?: 'No overview available.',
            'attachment'     => get_post_meta($job_id, '_tcat_attachment', true),
            'date_posted'    => get_the_date('d M Y', $job_id),
            'job_description'=> apply_filters('the_content', $post->post_content) ?: 'No description provided.',
        ];

        wp_send_json_success($data);
    }

}

new TCAT_Applicant_Dashboard();
