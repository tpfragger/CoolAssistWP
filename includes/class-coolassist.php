<?php
class CoolAssist {
    public function init() {
        add_action('init', array($this, 'register_post_types'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('rest_api_init', array($this, 'register_api_endpoints'));
        add_shortcode('coolassist_page', array($this, 'coolassist_page_shortcode'));
        add_filter('template_include', array($this, 'load_coolassist_template'));
    }

    public function register_post_types() {
        register_post_type('ac_manual', array(
            'labels' => array(
                'name' => __('AC Manuals', 'coolassist'),
                'singular_name' => __('AC Manual', 'coolassist')
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'thumbnail'),
            'menu_icon' => 'dashicons-media-document'
        ));
    }

    public function enqueue_scripts() {
        wp_enqueue_style('coolassist-style', COOLASSIST_PLUGIN_URL . 'assets/css/coolassist-style.css');
        wp_enqueue_script('coolassist-script', COOLASSIST_PLUGIN_URL . 'assets/js/coolassist-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('coolassist-script', 'coolassist_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('coolassist-nonce')
        ));
    }

    public function enqueue_admin_scripts($hook) {
        if ('toplevel_page_coolassist-settings' !== $hook) {
            return;
        }
        wp_enqueue_style('coolassist-admin-style', COOLASSIST_PLUGIN_URL . 'assets/css/coolassist-admin-style.css');
        wp_enqueue_script('coolassist-admin-script', COOLASSIST_PLUGIN_URL . 'assets/js/coolassist-admin-script.js', array('jquery'), '1.0.0', true);
    }

    public function add_admin_menu() {
        add_menu_page('CoolAssist Settings', 'CoolAssist', 'manage_options', 'coolassist-settings', array($this, 'render_settings_page'), 'dashicons-admin-generic', 6);
    }

    public function render_settings_page() {
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'general';
        ?>
        <div class="wrap">
            <h1>CoolAssist Settings</h1>
            <h2 class="nav-tab-wrapper">
                <a href="?page=coolassist-settings&tab=general" class="nav-tab <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>">General Settings</a>
                <a href="?page=coolassist-settings&tab=user_access" class="nav-tab <?php echo $active_tab == 'user_access' ? 'nav-tab-active' : ''; ?>">User Access</a>
                <a href="?page=coolassist-settings&tab=ac_manuals" class="nav-tab <?php echo $active_tab == 'ac_manuals' ? 'nav-tab-active' : ''; ?>">AC Manuals</a>
            </h2>

            <form method="post" action="options.php">
                <?php
                if ($active_tab == 'general') {
                    settings_fields('coolassist_general_settings');
                    do_settings_sections('coolassist_general_settings');
                } elseif ($active_tab == 'user_access') {
                    $this->render_user_access_tab();
                } elseif ($active_tab == 'ac_manuals') {
                    $this->render_ac_manuals_tab();
                }
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function register_settings() {
        register_setting('coolassist_general_settings', 'coolassist_claude_api_key');
        register_setting('coolassist_general_settings', 'coolassist_appearance');

        add_settings_section('coolassist_general_settings_section', 'General Settings', null, 'coolassist_general_settings');

        add_settings_field('coolassist_claude_api_key', 'Claude API Key', array($this, 'render_api_key_field'), 'coolassist_general_settings', 'coolassist_general_settings_section');
        add_settings_field('coolassist_appearance', 'Appearance', array($this, 'render_appearance_field'), 'coolassist_general_settings', 'coolassist_general_settings_section');
    }

    public function render_api_key_field() {
        $api_key = get_option('coolassist_claude_api_key');
        echo "<input type='text' name='coolassist_claude_api_key' value='" . esc_attr($api_key) . "' class='regular-text'>";
    }

    public function render_appearance_field() {
        $appearance = get_option('coolassist_appearance', 'light');
        ?>
        <select name="coolassist_appearance">
            <option value="light" <?php selected($appearance, 'light'); ?>>Light</option>
            <option value="dark" <?php selected($appearance, 'dark'); ?>>Dark</option>
        </select>
        <?php
    }

    public function render_user_access_tab() {
        ?>
        <h3>Create New AC Technician Account</h3>
        <table class="form-table">
            <tr>
                <th><label for="username">Username</label></th>
                <td><input type="text" name="username" id="username" class="regular-text" required></td>
            </tr>
            <tr>
                <th><label for="email">Email</label></th>
                <td><input type="email" name="email" id="email" class="regular-text" required></td>
            </tr>
            <tr>
                <th><label for="password">Password</label></th>
                <td><input type="password" name="password" id="password" class="regular-text" required></td>
            </tr>
        </table>
        <?php
    }

    public function render_ac_manuals_tab() {
        ?>
        <h3>AC Manuals</h3>
        <p>Manage AC manuals in the <a href="<?php echo admin_url('edit.php?post_type=ac_manual'); ?>">AC Manuals</a> section.</p>
        <?php
    }

    public function register_api_endpoints() {
        register_rest_route('coolassist/v1', '/chat', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_chat'),
            'permission_callback' => function() {
                return current_user_can('access_coolassist');
            }
        ));

        register_rest_route('coolassist/v1', '/upload-image', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_image_upload'),
            'permission_callback' => function() {
                return current_user_can('access_coolassist');
            }
        ));
    }

    public function handle_chat($request) {
        $parameters = $request->get_json_params();
        $user_message = sanitize_text_field($parameters['message']);

        // Implement Claude API call here
        // For now, we'll return a mock response
        return rest_ensure_response(array(
            'content' => array(
                array('text' => 'This is a mock response from the AI. Implement actual API call here.')
            )
        ));
    }

    public function handle_image_upload($request) {
        $files = $request->get_file_params();
        
        if (empty($files['image'])) {
            return new WP_Error('no_image', 'No image was uploaded.', array('status' => 400));
        }

        $file = $files['image'];
        $upload_overrides = array('test_form' => false);
        $movefile = wp_handle_upload($file, $upload_overrides);

        if ($movefile && !isset($movefile['error'])) {
            // Implement image analysis with Claude API here
            // For now, we'll return a mock response
            return rest_ensure_response(array(
                'content' => array(
                    array('text' => 'This is a mock response for image analysis. Implement actual API call here.')
                )
            ));
        } else {
            return new WP_Error('upload_error', $movefile['error'], array('status' => 500));
        }
    }

    public function coolassist_page_shortcode() {
        ob_start();
        if (is_user_logged_in() && current_user_can('access_coolassist')) {
            include COOLASSIST_PLUGIN_DIR . 'templates/coolassist-chat.php';
        } else {
            include COOLASSIST_PLUGIN_DIR . 'templates/coolassist-login.php';
        }
        return ob_get_clean();
    }

    public function load_coolassist_template($template) {
        if (is_page('coolassist')) {
            $new_template = COOLASSIST_PLUGIN_DIR . 'templates/page-coolassist.php';
            if (file_exists($new_template)) {
                return $new_template;
            }
        }
        return $template;
    }
}
