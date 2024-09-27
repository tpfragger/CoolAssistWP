<?php
class CoolAssist {
    private $api_key;

    public function __construct() {
        $this->api_key = get_option('coolassist_claude_api_key');
    }

    public function init() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_ajax_coolassist_chat', array($this, 'handle_chat'));
        add_action('wp_ajax_nopriv_coolassist_chat', array($this, 'handle_chat'));
        add_action('wp_ajax_coolassist_upload_image', array($this, 'handle_image_upload'));
        add_action('wp_ajax_coolassist_create_user', array($this, 'ajax_create_user'));
        add_action('wp_ajax_coolassist_delete_user', array($this, 'ajax_delete_user'));
        add_action('wp_ajax_coolassist_reset_password', array($this, 'ajax_reset_password'));
        add_action('wp_ajax_coolassist_upload_manual', array($this, 'ajax_upload_manual'));
        add_action('wp_ajax_coolassist_delete_manual', array($this, 'ajax_delete_manual'));
        add_action('wp_ajax_coolassist_login', array($this, 'ajax_login'));
        add_action('wp_ajax_nopriv_coolassist_login', array($this, 'ajax_login'));
        add_action('wp_ajax_coolassist_get_model_numbers', array($this, 'ajax_get_model_numbers'));
        add_action('wp_ajax_nopriv_coolassist_get_model_numbers', array($this, 'ajax_get_model_numbers'));
        add_shortcode('coolassist_page', array($this, 'coolassist_page_shortcode'));
    }

    public function enqueue_scripts() {
        wp_enqueue_style('coolassist-style', COOLASSIST_PLUGIN_URL . 'assets/css/coolassist-style.css', array(), '1.0.2');
        wp_enqueue_script('coolassist-script', COOLASSIST_PLUGIN_URL . 'assets/js/coolassist-script.js', array('jquery'), '1.0.2', true);
        wp_localize_script('coolassist-script', 'coolassist_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('coolassist-nonce')
        ));
    }

    public function enqueue_admin_scripts($hook) {
        if ('toplevel_page_coolassist-settings' !== $hook) {
            return;
        }
        wp_enqueue_style('coolassist-admin-style', COOLASSIST_PLUGIN_URL . 'assets/css/coolassist-admin-style.css', array(), '1.0.1');
        wp_enqueue_script('coolassist-admin-script', COOLASSIST_PLUGIN_URL . 'assets/js/coolassist-admin-script.js', array('jquery'), '1.0.1', true);
        wp_localize_script('coolassist-admin-script', 'coolassist_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('coolassist-nonce')
        ));
    }

    public function add_admin_menu() {
        add_menu_page('CoolAssist Settings', 'CoolAssist', 'manage_options', 'coolassist-settings', array($this, 'render_settings_page'), 'dashicons-admin-generic', 6);
    }

    public function render_settings_page() {
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
        ?>
        <div class="wrap coolassist-settings-page">
            <h1>CoolAssist Settings</h1>
            <h2 class="nav-tab-wrapper">
                <a href="?page=coolassist-settings&tab=general" class="nav-tab <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>">General Settings</a>
                <a href="?page=coolassist-settings&tab=users" class="nav-tab <?php echo $active_tab == 'users' ? 'nav-tab-active' : ''; ?>">Users</a>
                <a href="?page=coolassist-settings&tab=manuals" class="nav-tab <?php echo $active_tab == 'manuals' ? 'nav-tab-active' : ''; ?>">AC Manuals</a>
                <a href="?page=coolassist-settings&tab=chat_history" class="nav-tab <?php echo $active_tab == 'chat_history' ? 'nav-tab-active' : ''; ?>">Chat History</a>
            </h2>

            <?php
            if ($active_tab == 'general') {
                $this->render_general_settings_tab();
            } elseif ($active_tab == 'users') {
                $this->render_users_tab();
            } elseif ($active_tab == 'manuals') {
                $this->render_manuals_tab();
            } elseif ($active_tab == 'chat_history') {
                $this->render_chat_history_tab();
            }
            ?>
        </div>
        <?php
    }

    public function render_general_settings_tab() {
        ?>
        <form method="post" action="options.php">
            <?php
            settings_fields('coolassist_general_settings');
            do_settings_sections('coolassist_general_settings');
            ?>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="coolassist_claude_api_key">Claude API Key</label>
                    </th>
                    <td>
                        <div class="api-key-wrapper">
                            <input type="password" id="coolassist_claude_api_key" name="coolassist_claude_api_key" value="<?php echo esc_attr(get_option('coolassist_claude_api_key')); ?>" class="regular-text" />
                            <button type="button" id="toggle-api-key" class="button">Show API Key</button>
                        </div>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <script>
        jQuery(document).ready(function($) {
            $('#toggle-api-key').click(function() {
                var $input = $('#coolassist_claude_api_key');
                if ($input.attr('type') === 'password') {
                    $input.attr('type', 'text');
                    $(this).text('Hide API Key');
                } else {
                    $input.attr('type', 'password');
                    $(this).text('Show API Key');
                }
            });
        });
        </script>
        <?php
    }

    public function render_users_tab() {
        $coolassist_user = new CoolAssist_User();
        ?>
        <h3>Create New User</h3>
        <form id="create-user-form" method="post">
            <?php wp_nonce_field('create_coolassist_user', 'create_user_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th><label for="first_name">First Name</label></th>
                    <td><input type="text" name="first_name" id="first_name" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label for="last_name">Last Name</label></th>
                    <td><input type="text" name="last_name" id="last_name" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label for="password">Password</label></th>
                    <td><input type="password" name="password" id="password" class="regular-text" required></td>
                </tr>
            </table>
            <?php submit_button('Create User'); ?>
        </form>

        <h3>User List</h3>
        <?php
        $users = $coolassist_user->get_all_users();
        if (!empty($users)) {
            ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user) { ?>
                        <tr>
                            <td><?php echo esc_html($user->username); ?></td>
                            <td><?php echo esc_html($user->name); ?></td>
                            <td>
                                <button class="button delete-user" data-user-id="<?php echo esc_attr($user->id); ?>">Delete</button>
                                <button class="button reset-password" data-user-id="<?php echo esc_attr($user->id); ?>">Reset Password</button>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
            <?php
        } else {
            echo '<p>No users found.</p>';
        }
    }

    public function render_manuals_tab() {
        $coolassist_manual = new CoolAssist_Manual();
        ?>
        <h3>Upload AC Manual</h3>
        <form id="upload-manual-form" method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('upload_ac_manual', 'upload_manual_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th><label for="model_number">Model Number</label></th>
                    <td><input type="text" name="model_number" id="model_number" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label for="manual_file">Manual PDF</label></th>
                    <td><input type="file" name="manual_file" id="manual_file" accept=".pdf" required></td>
                </tr>
            </table>
            <?php submit_button('Upload Manual'); ?>
        </form>

        <h3>AC Manuals List</h3>
        <?php
        $manuals = $coolassist_manual->get_all_manuals();
        if (!empty($manuals)) {
            ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Model Number</th>
                        <th>File Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($manuals as $manual) { ?>
                        <tr>
                            <td><?php echo esc_html($manual->model_number); ?></td>
                            <td><?php echo esc_html($manual->file_name); ?></td>
                            <td>
                                <a href="<?php echo esc_url($manual->file_path); ?>" target="_blank" class="button">Preview</a>
                                <a href="<?php echo esc_url($manual->file_path); ?>" download class="button">Download</a>
                                <button class="button delete-manual" data-manual-id="<?php echo esc_attr($manual->id); ?>">Delete</button>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
            <?php
        } else {
            echo '<p>No manuals found.</p>';
        }
    }

    public function render_chat_history_tab() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'coolassist_chat_history';
        $chat_history = $wpdb->get_results("SELECT * FROM $table_name ORDER BY timestamp DESC LIMIT 100");
        ?>
        <h3>Recent Chat History</h3>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>Sender</th>
                    <th>Message</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($chat_history as $entry): ?>
                    <tr>
                        <td><?php echo esc_html($entry->user_id); ?></td>
                        <td><?php echo esc_html($entry->sender); ?></td>
                        <td><?php echo wp_kses_post($entry->message); ?></td>
                        <td><?php echo esc_html($entry->timestamp); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    public function register_settings() {
        register_setting('coolassist_general_settings', 'coolassist_claude_api_key', array(
            'sanitize_callback' => 'sanitize_text_field',
        ));
    }

    public function handle_chat() {
        check_ajax_referer('coolassist-nonce', 'nonce');

        $message = isset($_POST['message']) ? sanitize_text_field($_POST['message']) : '';
        $model_number = isset($_POST['model_number']) ? sanitize_text_field($_POST['model_number']) : '';

        if (empty($message)) {
            wp_send_json_error('Message cannot be empty');
            return;
        }

        try {
            $coolassist_user = new CoolAssist_User();
            $user_id = $coolassist_user->get_current_user_id();
            
            // Store user message
            $this->store_chat_history($user_id, 'user', $message);
            
            $response = $this->call_claude_api($message, $model_number);
            if (isset($response['content'][0]['text'])) {
                // Store AI response
                $this->store_chat_history($user_id, 'ai', $response['content'][0]['text']);
                
                wp_send_json_success($response);
            } else {
                error_log('CoolAssist Error: Invalid response structure from call_claude_api');
                wp_send_json_error('Failed to get a valid response from the AI service');
            }
        } catch (Exception $e) {
            error_log('CoolAssist Error: ' . $e->getMessage());
            wp_send_json_error('An error occurred while processing your request: ' . $e->getMessage());
        }
    }

public function handle_image_upload() {
        check_ajax_referer('coolassist-nonce', 'nonce');

        if (!isset($_FILES['image'])) {
            wp_send_json_error('No image was uploaded.');
        }

        $uploaded_file = $_FILES['image'];
        $upload_overrides = array('test_form' => false);
        $movefile = wp_handle_upload($uploaded_file, $upload_overrides);

        if ($movefile && !isset($movefile['error'])) {
            $image_path = $movefile['file'];
            $image_url = $movefile['url'];
            $response = $this->analyze_image_with_claude($image_path);

            $coolassist_user = new CoolAssist_User();
            $user_id = $coolassist_user->get_current_user_id();

            // Store image upload in chat history
            $this->store_chat_history($user_id, 'user', '<img src="' . $image_url . '" alt="Uploaded Image" style="max-width: 100%; height: auto;">');

            if (isset($response['content'][0]['text'])) {
                $ai_response = $response['content'][0]['text'];
                // Store AI response
                $this->store_chat_history($user_id, 'ai', $ai_response);
            }

            wp_send_json_success(array('image_url' => $image_url, 'content' => $response['content']));
        } else {
            wp_send_json_error($movefile['error']);
        }
    }

    private function call_claude_api($message, $model_number = '') {
        if (empty($this->api_key)) {
            error_log('Claude API Error: API key is not set');
            return $this->generate_error_response("API key is not set. Please configure the API key in the plugin settings.");
        }

        $url = 'https://api.anthropic.com/v1/messages';
        $headers = array(
            'Content-Type' => 'application/json',
            'x-api-key' => $this->api_key,
            'anthropic-version' => '2023-06-01'
        );

        $system_prompt = "You are an AI assistant specialized in AC and HVAC repairs. Only provide information related to AC units, HVAC systems, and their repair and maintenance. If asked about unrelated topics, politely redirect the conversation back to AC and HVAC matters.";
        
        $user_content = $system_prompt . "\n\nUser query: " . $message;
        if (!empty($model_number)) {
            $manual_content = $this->get_manual_content($model_number);
            $user_content .= "\n\nRelevant AC manual information for model $model_number: " . $manual_content;
        }

        $body = array(
            'model' => 'claude-3-opus-20240229',
            'max_tokens' => 1000,
            'messages' => array(
                array('role' => 'user', 'content' => $user_content)
            )
        );

        $args = array(
            'headers' => $headers,
            'body'    => wp_json_encode($body),
            'method'  => 'POST',
            'timeout' => 60,
        );

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            error_log('Claude API Error: ' . $response->get_error_message());
            return $this->generate_error_response("Unable to connect to the AI service. Please try again later.");
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        if ($response_code !== 200) {
            error_log('Claude API Error: Unexpected response code ' . $response_code);
            return $this->generate_error_response("Received an unexpected response from the AI service. Please try again.");
        }

        $body = json_decode($response_body, true);
        
        if (isset($body['content'][0]['text'])) {
            return array(
                'content' => array(
                    array('text' => $body['content'][0]['text'])
                )
            );
        } else {
            error_log('Unexpected Claude API response: ' . print_r($response_body, true));
            return $this->generate_error_response("Unexpected response from AI service. Please try again.");
        }
    }

    private function analyze_image_with_claude($image_path) {
        $url = 'https://api.anthropic.com/v1/messages';
        $headers = array(
            'Content-Type' => 'application/json',
            'x-api-key' => $this->api_key,
            'anthropic-version' => '2023-06-01'
        );

        $image_data = base64_encode(file_get_contents($image_path));
        $body = array(
            'model' => 'claude-3-opus-20240229',
            'max_tokens' => 1000,
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => array(
                        array('type' => 'image', 'source' => array('type' => 'base64', 'media_type' => 'image/jpeg', 'data' => $image_data)),
                        array('type' => 'text', 'text' => "Analyze this image of an AC unit. Identify the model number if visible, and describe any visible issues or potential problems. Focus only on AC-related information.")
                    )
                )
            )
        );

        $args = array(
            'headers' => $headers,
            'body'    => wp_json_encode($body),
            'method'  => 'POST',
            'timeout' => 60,
        );

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            error_log('Claude API Image Analysis Error: ' . $response->get_error_message());
            return $this->generate_error_response("Error analyzing image: Unable to connect to the AI service. Please try again later.");
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        if ($response_code !== 200) {
            error_log('Claude API Image Analysis Error: Unexpected response code ' . $response_code);
            return $this->generate_error_response("Received an unexpected response from the AI service during image analysis. Please try again.");
        }

        $body = json_decode($response_body, true);
        if (isset($body['content'][0]['text'])) {
            return array(
                'content' => array(
                    array('text' => $body['content'][0]['text'])
                )
            );
        } else {
            return $this->generate_error_response("Unexpected response from AI service during image analysis. Please try again.");
        }
    }

    private function get_manual_content($model_number) {
        $coolassist_manual = new CoolAssist_Manual();
        $manual = $coolassist_manual->get_manual_by_model_number($model_number);
        if ($manual) {
            $file_content = file_get_contents($manual->file_path);
            if ($file_content !== false) {
                // For simplicity, we'll return the first 1000 characters of the file
                return substr($file_content, 0, 1000);
            }
        }
        return "No specific manual content found for model number $model_number.";
    }

    private function store_chat_history($user_id, $sender, $message) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'coolassist_chat_history';
        
        $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'sender' => $sender,
                'message' => $message,
                'timestamp' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s')
        );
    }

    public function coolassist_page_shortcode() {
        ob_start();
        $coolassist_user = new CoolAssist_User();
        if ($coolassist_user->is_logged_in()) {
            include COOLASSIST_PLUGIN_DIR . 'templates/coolassist-chat.php';
        } else {
            include COOLASSIST_PLUGIN_DIR . 'templates/coolassist-login.php';
        }
        return ob_get_clean();
    }

    public function ajax_create_user() {
        check_ajax_referer('coolassist-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized access');
        }

        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $password = $_POST['password'];

        $coolassist_user = new CoolAssist_User();
        $result = $coolassist_user->create_user($first_name, $last_name, $password);

        if ($result) {
            wp_send_json_success('User created successfully');
        } else {
            wp_send_json_error('Failed to create user');
        }
    }

    public function ajax_delete_user() {
        check_ajax_referer('coolassist-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized access');
        }

        $user_id = intval($_POST['user_id']);

        $coolassist_user = new CoolAssist_User();
        $result = $coolassist_user->delete_user($user_id);

        if ($result) {
            wp_send_json_success('User deleted successfully');
        } else {
            wp_send_json_error('Failed to delete user');
        }
    }

    public function ajax_reset_password() {
        check_ajax_referer('coolassist-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized access');
        }

        $user_id = intval($_POST['user_id']);
        $new_password = wp_generate_password(12, true, true);

        $coolassist_user = new CoolAssist_User();
        $result = $coolassist_user->reset_password($user_id, $new_password);

        if ($result) {
            wp_send_json_success(array('message' => 'Password reset successfully', 'new_password' => $new_password));
        } else {
            wp_send_json_error('Failed to reset password');
        }
    }

    public function ajax_upload_manual() {
        check_ajax_referer('coolassist-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized access');
        }

        $model_number = sanitize_text_field($_POST['model_number']);
        $file = $_FILES['manual_file'];

        $coolassist_manual = new CoolAssist_Manual();
        $result = $coolassist_manual->upload_manual($model_number, $file);

        if ($result) {
            wp_send_json_success('Manual uploaded successfully');
        } else {
            wp_send_json_error('Failed to upload manual');
        }
    }

    public function ajax_delete_manual() {
        check_ajax_referer('coolassist-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized access');
        }

        $manual_id = intval($_POST['manual_id']);

        $coolassist_manual = new CoolAssist_Manual();
        $result = $coolassist_manual->delete_manual($manual_id);

        if ($result) {
            wp_send_json_success('Manual deleted successfully');
        } else {
            wp_send_json_error('Failed to delete manual');
        }
    }

    public function ajax_login() {
        check_ajax_referer('coolassist-nonce', 'nonce');

        $username = sanitize_user($_POST['username']);
        $password = $_POST['password'];

        $coolassist_user = new CoolAssist_User();
        $user = $coolassist_user->authenticate($username, $password);

        if ($user) {
            $_SESSION['coolassist_user_id'] = $user->id;
            wp_send_json_success(array('message' => 'Login successful', 'redirect' => home_url('/coolassist')));
        } else {
            wp_send_json_error('Invalid username or password');
        }
    }

    public function ajax_get_model_numbers() {
        check_ajax_referer('coolassist-nonce', 'nonce');

        $coolassist_manual = new CoolAssist_Manual();
        $model_numbers = $coolassist_manual->get_all_model_numbers();

        wp_send_json_success($model_numbers);
    }

    private function generate_error_response($message) {
        return array(
            'content' => array(
                array('text' => "Error: $message")
            )
        );
    }
}
