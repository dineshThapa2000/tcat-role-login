<?php
if (!defined('ABSPATH')) exit;

class TCAT_Login {

    public function __construct() {
        add_shortcode('tcat_custom_login', [$this, 'login_form']);
        add_action('init', [$this, 'handle_login']);
        add_action('template_redirect', [$this, 'redirect_if_logged_in']); // handle logged-in visitors
        add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);
    }

    public function enqueue_styles() {
        wp_enqueue_style(
            'tcat-login-style',
            plugin_dir_url(__FILE__) . '../assets/css/tcat-admin-login.css',
            [],
            '1.0'
        );
    }

    /**
     * Redirect user if they are already logged in and visiting /login
     */
    public function redirect_if_logged_in() {
        if (is_user_logged_in() && is_page('login')) {
            $this->redirect_by_role(wp_get_current_user());
        }
    }

    /**
     * Render login form shortcode
     */
    public function login_form() {
        wp_enqueue_style('tcat-login-style');

        ob_start();
        if (isset($_GET['login']) && $_GET['login'] === 'failed') {
            echo '<p class="tcat-error">Login failed. Please try again.</p>';
        }
        ?>
        <div class="tcat-login-wrapper">
            <form method="post" class="tcat-login-form">
                <?php wp_nonce_field('tcat_login_action', 'tcat_login_nonce'); ?>
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
                    <a href="<?php echo esc_url(home_url('/signup')); ?>">Don't have an account? Sign Up</a>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Handle login submission
     */
    public function handle_login() {
        if (!isset($_POST['tcat_login'])) return;
        if (!isset($_POST['tcat_login_nonce']) || !wp_verify_nonce($_POST['tcat_login_nonce'], 'tcat_login_action')) return;

        $username = sanitize_text_field($_POST['log']);
        $password = $_POST['pwd'];

        // If user entered email, get actual username
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
            wp_safe_redirect(home_url('/login?login=failed'));
            exit;
        }

        // Redirect based on role
        $this->redirect_by_role($user);
    }

    /**
     * Redirect user to correct dashboard based on role
     */
    private function redirect_by_role($user) {
        if (in_array('administrator', $user->roles, true) || in_array('tcat_admin', $user->roles, true)) {
            wp_safe_redirect(home_url('/admin-dashboard'));
        } elseif (in_array('tcat_applicant', $user->roles, true)) {
            wp_safe_redirect(home_url('/applicant-dashboard'));
        } else {
            wp_safe_redirect(home_url());
        }
        exit;
    }
}

new TCAT_Login();
