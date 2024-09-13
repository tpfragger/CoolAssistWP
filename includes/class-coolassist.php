<?php
class CoolAssist {
    public function init() {
        add_action('init', array($this, 'register_post_types'));
        add_action('acf/init', array($this, 'register_acf_fields'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('rest_api_init', array($this, 'register_api_endpoints'));
        add_shortcode('coolassist_login', array($this, 'login_shortcode'));
        add_shortcode('coolassist_ai', array($this, 'ai_shortcode'));
        add_filter('login_redirect', array($this, 'login_redirect'), 10, 3);
        add_action('template_redirect', array($this, 'restrict_ai_page'));
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

    public function register_acf_fields() {
        if (function_exists('acf_add_local_field_group')) {
            acf_add_local_field_group(array(
                'key' => 'group_ac_manual',
                'title' => 'AC Manual Details',
                'fields' => array(
                    array(
                        'key' => 'field_manual_file',
                        'label' => 'Manual File',
                        'name' => 'manual_file',
                        'type' => 'file',
                        'return_format' => 'url',
                        'library' => 'all',
                        'mime_types' => 'pdf'
                    ),
                    array(
                        'key' => 'field_model_number',
                        'label' => 'Model Number',
                        'name' => 'model_number',
                        'type' => 'text'
                    )
                ),
                'location' => array(
                    array(
                        array(
                            'param' => 'post_type',
                            'operator' => '==',
                            'value' => 'ac_manual',
                        ),
                    ),
                ),
            ));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_style('coolassist-style', COOLASSIST_PLUGIN_URL . 'assets/css/coolassist-style.css');
        wp_enqueue_script('coolassist-script', COOLASSIST_PLUGIN_URL . 'assets/js/coolassist-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('coolassist-script', 'coolassist_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('coolassist-nonce')
        ));
    }

    public function add_admin_menu() {
        add_menu_page('CoolAssist Settings', 'CoolAssist', 'manage_options', 'coolassist-settings', array($this, 'settings_page'), 'dashicons-admin-generic', 6);
    }

    public function settings_page() {
        include COOLASSIST_PLUGIN_DIR . 'templates/admin-settings.php';
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

    public function login_shortcode() {
        ob_start();
        include COOLASSIST_PLUGIN_DIR . 'templates/login.php';
        return ob_get_clean();
    }

    public function ai_shortcode() {
        ob_start();
        include COOLASSIST_PLUGIN_DIR . 'templates/ai-page.php';
        return ob_get_clean();
    }

    public function login_redirect($redirect_to, $request, $user) {
        if (isset($user->roles) && is_array($user->roles)) {
            if (in_array('ac_technician', $user->roles)) {
                return home_url('/coolassist-ai');
            }
        }
        return $redirect_to;
    }

    public function restrict_ai_page() {
        if (is_page('coolassist-ai') && !current_user_can('access_coolassist')) {
            wp_redirect(home_url());
            exit;
        }
    }
}
