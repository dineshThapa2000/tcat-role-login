<?php
if (!defined('ABSPATH')) exit;

class TCAT_Signup {

    public function __construct() {
        add_shortcode('tcat_custom_signup', [$this, 'signup_form']);
        add_action('init', [$this, 'handle_signup']);
    }

    public function signup_form() {
        if (is_user_logged_in()) {
            return '<p>You are already logged in. <a href="' . esc_url(wp_logout_url(home_url())) . '">Logout</a></p>';
        }

        ob_start();
        if (isset($_GET['signup'])) {
            switch ($_GET['signup']) {
                case 'success':
                    echo '<p class="tcat-success">Registration successful! You are now logged in.</p>';
                    break;
                case 'exists':
                    echo '<p class="tcat-error">Username or email already exists.</p>';
                    break;
                case 'mismatch':
                    echo '<p class="tcat-error">Email or password do not match.</p>';
                    break;
                case 'failed':
                    echo '<p class="tcat-error">Registration failed. Try again.</p>';
                    break;
            }
        }
        ?>
        <div class="tcat-signup-wrapper">
            <form method="post" class="tcat-login-form">
                <?php wp_nonce_field('tcat_register_action', 'tcat_register_nonce'); ?>
                <h2>Applicant Registration</h2>

                <div class="tcat-form-group">
                    <label>Title</label>
                    <select name="reg_title" required>
                        <option value="">Please select</option>
                        <option value="Mr">Mr</option>
                        <option value="Mrs">Mrs</option>
                        <option value="Miss">Miss</option>
                        <option value="Ms">Ms</option>
                        <option value="Dr">Dr</option>
                    </select>
                </div>

                <div class="tcat-form-group">
                    <input type="text" name="reg_forename" placeholder="Forename" required />
                </div>

                <div class="tcat-form-group">
                    <input type="text" name="reg_surname" placeholder="Surname" required />
                </div>

                <div class="tcat-form-group">
                    <input type="text" name="reg_username" placeholder="Username" required />
                </div>

                <div class="tcat-form-group">
                    <input type="email" name="reg_email" placeholder="Email address" required />
                </div>

                <div class="tcat-form-group">
                    <input type="email" name="reg_email_confirm" placeholder="Confirm email address" required />
                </div>

                <div class="tcat-form-group">
                    <input type="password" name="reg_pwd" placeholder="Password" required />
                </div>

                <div class="tcat-form-group">
                    <input type="password" name="reg_pwd_confirm" placeholder="Confirm password" required />
                </div>

                <div class="tcat-form-group">
                    <input type="submit" name="tcat_register" value="Register" />
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public function handle_signup() {
        if (!isset($_POST['tcat_register'])) return;
        if (!isset($_POST['tcat_register_nonce']) || !wp_verify_nonce($_POST['tcat_register_nonce'], 'tcat_register_action')) return;

        if (!get_role('tcat_applicant')) {
            add_role('tcat_applicant', 'TCAT Applicant', ['read' => true]);
        }

        $title      = sanitize_text_field($_POST['reg_title']);
        $forename   = sanitize_text_field($_POST['reg_forename']);
        $surname    = sanitize_text_field($_POST['reg_surname']);
        $username   = sanitize_user($_POST['reg_username']);
        $email      = sanitize_email($_POST['reg_email']);
        $email_c    = sanitize_email($_POST['reg_email_confirm']);
        $password   = $_POST['reg_pwd'];
        $password_c = $_POST['reg_pwd_confirm'];

        if ($email !== $email_c || $password !== $password_c) {
            wp_safe_redirect(home_url('/signup?signup=mismatch'));
            exit;
        }

        if (username_exists($username) || email_exists($email)) {
            wp_safe_redirect(home_url('/signup?signup=exists'));
            exit;
        }

        $user_id = wp_create_user($username, $password, $email);
        if (is_wp_error($user_id)) {
            wp_safe_redirect(home_url('/signup?signup=failed'));
            exit;
        }

        $user = new WP_User($user_id);
        $user->set_role('tcat_applicant');

        update_user_meta($user_id, 'title', $title);
        update_user_meta($user_id, 'forename', $forename);
        update_user_meta($user_id, 'surname', $surname);

        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);
        wp_safe_redirect(home_url('/applicant-dashboard'));
        exit;
    }
}

new TCAT_Signup();
