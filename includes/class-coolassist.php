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
        add_action('wp_ajax_coolassist_login', array($this, 'ajax_login'));
        add_action('wp_ajax_nopriv_coolassist_login', array($this, 'ajax_login'));
        add_action('wp_ajax_coolassist_logout', array($this, 'ajax_logout'));
        add_action('wp_ajax_coolassist_get_model_numbers', array($this, 'ajax_get_model_numbers'));
        add_action('wp_ajax_nopriv_coolassist_get_model_numbers', array($this, 'ajax_get_model_numbers'));
        add_action('wp_ajax_coolassist_create_user', array($this, 'ajax_create_user'));
        add_action('wp_ajax_coolassist_delete_user', array($this, 'ajax_delete_user'));
        add_action('wp_ajax_coolassist_reset_password', array($this, 'ajax_reset_password'));
        add_action('wp_ajax_coolassist_upload_manual', array($this, 'ajax_upload_manual'));
        add_action('wp_ajax_nopriv_coolassist_upload_manual', array($this, 'ajax_upload_manual'));
        add_action('wp_ajax_coolassist_delete_manual', array($this, 'ajax_delete_manual'));
        add_action('wp_ajax_get_chat_history', array($this, 'get_chat_history'));
        add_shortcode('coolassist_page', array($this, 'coolassist_page_shortcode'));
    }


    public function enqueue_scripts() {
    wp_enqueue_style('coolassist-style', COOLASSIST_PLUGIN_URL . 'assets/css/coolassist-style.css', array(), '1.0.4');
    wp_enqueue_script('coolassist-script', COOLASSIST_PLUGIN_URL . 'assets/js/coolassist-script.js', array('jquery'), '1.0.4', true);
    wp_localize_script('coolassist-script', 'coolassist_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('coolassist-nonce')
    ));
}

    public function enqueue_admin_scripts($hook) {
        if ('toplevel_page_coolassist-settings' !== $hook) {
            return;
        }
        wp_enqueue_style('coolassist-admin-style', COOLASSIST_PLUGIN_URL . 'assets/css/coolassist-admin-style.css', array(), '1.0.2');
        wp_enqueue_script('coolassist-admin-script', COOLASSIST_PLUGIN_URL . 'assets/js/coolassist-admin-script.js', array('jquery'), '1.0.2', true);
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
                        <input type="password" id="coolassist_claude_api_key" name="coolassist_claude_api_key" value="<?php echo esc_attr(get_option('coolassist_claude_api_key')); ?>" class="regular-text">
                        <button type="button" id="toggle-api-key" class="button">Show API Key</button>
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
<a href="<?php echo esc_url($coolassist_manual->get_manual_url($manual->id)); ?>" target="_blank" class="button">Preview</a>
<a href="<?php echo esc_url($coolassist_manual->get_manual_url($manual->id)); ?>" download class="button">Download</a>                                <button class="button delete-manual" data-manual-id="<?php echo esc_attr($manual->id); ?>">Delete</button>
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
        ?>
        <h3>Chat History</h3>
        <form id="chat-history-filter">
            <input type="text" id="username-filter" placeholder="Filter by username">
            <input type="date" id="date-filter" placeholder="Filter by date">
            <button type="submit">Filter</button>
        </form>

        <table id="chat-history-table" class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>User Message</th>
                    <th>AI Response</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>

        <script>
        jQuery(document).ready(function($) {
            function loadChatHistory(username = '', date = '') {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'get_chat_history',
                        username: username,
                        date: date,
                        nonce: '<?php echo wp_create_nonce('get_chat_history_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            var tbody = $('#chat-history-table tbody');
                            tbody.empty();
                            $.each(response.data, function(index, row) {
                                var tr = $('<tr>');
                                tr.append($('<td>').text(row.username));
                                tr.append($('<td>').text(row.user_message));
                                tr.append($('<td>').text(row.ai_response));
                                tr.append($('<td>').text(row.timestamp));
                                tbody.append(tr);
                            });
                        }
                    }
                });
            }

            loadChatHistory();

            $('#chat-history-filter').on('submit', function(e) {
                e.preventDefault();
                var username = $('#username-filter').val();
                var date = $('#date-filter').val();
                loadChatHistory(username, date);
            });
        });
        </script>
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

    $image_url = '';
    if (isset($_FILES['image']) && !empty($_FILES['image']['tmp_name'])) {
        $image_url = $this->handle_image_upload($_FILES['image']);
    }

    if (empty($message) && empty($image_url)) {
        wp_send_json_error('Please provide a message or upload an image.');
        return;
    }

    // Start or resume session
    if (!session_id()) {
        session_start();
    }

    // Initialize conversation history if not exists
    if (!isset($_SESSION['conversation_history'])) {
        $_SESSION['conversation_history'] = array();
    }

    // Add user message to history
    $_SESSION['conversation_history'][] = array('role' => 'user', 'content' => $message);

    try {
        // Store user message in database
        $this->store_chat_history($message, $model_number, 'user');

        // Prepare prompt for Claude API
        $prompt = $this->prepare_prompt_with_history($message, $model_number, $image_url);

        // Generate AI response
        $response = $this->call_claude_api($prompt, $image_url);

        if (isset($response['content'][0]['text'])) {
            $ai_response = $response['content'][0]['text'];
            
            // Add AI response to history
            $_SESSION['conversation_history'][] = array('role' => 'assistant', 'content' => $ai_response);

            // Limit history to last 10 messages
            if (count($_SESSION['conversation_history']) > 10) {
                array_shift($_SESSION['conversation_history']);
            }

            // Generate buttons based on AI response
            $buttons = $this->generate_buttons($ai_response);

            // Store AI response in database
            $this->store_chat_history($ai_response, $model_number, 'ai');

            wp_send_json_success(array(
                'message' => $ai_response,
                'image_url' => $image_url,
                'buttons' => $buttons
            ));
        } else {
            wp_send_json_error('Failed to get a valid response from the AI service');
        }
    } catch (Exception $e) {
        wp_send_json_error('An error occurred while processing your request: ' . $e->getMessage());
    }
}

    private function perform_rag_search($query, $model_number) {
        $coolassist_manual = new CoolAssist_Manual();
        $manual = $coolassist_manual->get_manual_by_model_number($model_number);

        if (!$manual) {
            return array('content' => '', 'images' => array());
        }

        $pdf_content = $this->extract_pdf_content($manual->file_path);
        $relevant_content = $this->search_relevant_content($pdf_content, $query);
        $images = $this->extract_images_from_pdf($manual->file_path);

        return array(
            'content' => $relevant_content,
            'images' => $images
        );
    }

    private function extract_pdf_content($pdf_path) {
        $content = shell_exec("pdftotext '{$pdf_path}' -");
        return $content ? $content : '';
    }

    private function search_relevant_content($content, $query) {
        $sentences = preg_split('/(?<=[.!?])\s+/', $content);
        $relevant_sentences = array();

        foreach ($sentences as $sentence) {
            if (stripos($sentence, $query) !== false) {
                $relevant_sentences[] = $sentence;
            }
        }

        return implode(' ', array_slice($relevant_sentences, 0, 5));
    }

    private function extract_images_from_pdf($pdf_path) {
        $images = array();
        $output_dir = wp_upload_dir()['path'] . '/coolassist_temp_images/';
        wp_mkdir_p($output_dir);

        shell_exec("pdfimages -j '{$pdf_path}' '{$output_dir}/image'");

        $image_files = glob($output_dir . 'image-*.jpg');

        foreach ($image_files as $index => $image_file) {
            $new_file_name = 'manual_image_' . $index . '.jpg';
            $new_file_path = $output_dir . $new_file_name;
            rename($image_file, $new_file_path);

            $images[] = array(
                'url' => wp_upload_dir()['url'] . '/coolassist_temp_images/' . $new_file_name,
                'caption' => 'Manual Image ' . ($index + 1)
            );

            if (count($images) >= 3) {
                break;
            }
        }

        return $images;
    }

    private function prepare_prompt_with_history($message, $model_number, $image_url) {
    $prompt = "You are an AI assistant specialized in AC and HVAC repairs, addressing professional AC repairmen. ";
    $prompt .= "Provide concise, technical responses without unnecessary explanations. ";
    $prompt .= "Focus on the most statistically probable causes and solutions for the described issue. ";
    
    // Add conversation history
    foreach ($_SESSION['conversation_history'] as $entry) {
        $prompt .= "{$entry['role']}: {$entry['content']}\n\n";
    }
    
    $prompt .= "Current query: $message\n\n";
    
    if (!empty($model_number)) {
        $prompt .= "AC Model: $model_number\n\n";
    }
    
    if (!empty($image_url)) {
        $prompt .= "An image has been uploaded. Please analyze it and provide relevant technical information.\n\n";
    }
    
    $prompt .= "Please provide a direct, technical response to the repairman's query, incorporating previous context when relevant. Suggest 2-3 possible next steps or diagnostic actions.";
    
    return $prompt;
}

    private function call_claude_api($prompt, $image_url = '') {
        $url = 'https://api.anthropic.com/v1/messages';
        $headers = array(
            'Content-Type' => 'application/json',
            'x-api-key' => $this->api_key,
            'anthropic-version' => '2023-06-01'
        );

        $body = array(
            'model' => 'claude-3-opus-20240229',
            'max_tokens' => 1000,
            'messages' => array(
                array('role' => 'user', 'content' => array())
            )
        );

        $body['messages'][0]['content'][] = array('type' => 'text', 'text' => $prompt);

        if (!empty($image_url)) {
            $image_data = base64_encode(file_get_contents($image_url));
            $body['messages'][0]['content'][] = array(
                'type' => 'image',
                'source' => array(
                    'type' => 'base64',
                    'media_type' => 'image/jpeg',
                    'data' => $image_data
                )
            );
        }

        $args = array(
            'headers' => $headers,
            'body'    => wp_json_encode($body),
            'method'  => 'POST',
            'timeout' => 60,
        );

        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            throw new Exception("Unable to connect to the AI service: " . $response->get_error_message());
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        if ($response_code !== 200) {
            throw new Exception("Received an unexpected response from the AI service: $response_code");
        }

        return json_decode($response_body, true);
    }


    private function generate_buttons($ai_response) {
        $buttons = array();
        $lines = explode("\n", $ai_response);
        $capturing = false;

        foreach ($lines as $line) {
            if (strpos($line, 'Follow-up questions:') !== false || strpos($line, 'Possible actions:') !== false) {
                $capturing = true;
                continue;
            }

            if ($capturing && !empty(trim($line))) {
                $buttons[] = array(
                    'text' => trim($line),
                    'action' => 'send_message'
                );

                if (count($buttons) >= 5) {
                    break;
                }
            }
        }

        return $buttons;
    }

    public function handle_image_upload($file) {
        $upload_dir = wp_upload_dir();
        $file_name = wp_unique_filename($upload_dir['path'], $file['name']);
        $file_path = $upload_dir['path'] . '/' . $file_name;

        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            return $upload_dir['url'] . '/' . $file_name;
        }

        return '';
    }

    private function store_chat_history($message, $model_number, $sender) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'coolassist_chat_history';
    
    // Get the custom user ID from the session
    $custom_user_id = isset($_SESSION['coolassist_user_id']) ? $_SESSION['coolassist_user_id'] : 0;
    
    $wpdb->insert(
        $table_name,
        array(
            'user_id' => $custom_user_id,
            'message' => $message,
            'model_number' => $model_number,
            'sender' => $sender,
            'timestamp' => current_time('mysql')
        ),
        array('%d', '%s', '%s', '%s', '%s')
    );
}

    public function ajax_login() {
    check_ajax_referer('coolassist-nonce', 'nonce');

    $username = sanitize_user($_POST['username']);
    $password = $_POST['password'];

    $coolassist_user = new CoolAssist_User();
    $user = $coolassist_user->authenticate($username, $password);

    if ($user) {
        $coolassist_user->login($user->id);
        $_SESSION['coolassist_user_id'] = $user->id;
        wp_send_json_success(array('message' => 'Login successful'));
    } else {
        wp_send_json_error('Invalid username or password');
    }
}

    public function ajax_logout() {
        check_ajax_referer('coolassist-nonce', 'nonce');

        $coolassist_user = new CoolAssist_User();
        $coolassist_user->logout();

        wp_send_json_success('Logout successful');
    }

    public function ajax_get_model_numbers() {
        check_ajax_referer('coolassist-nonce', 'nonce');

        $coolassist_manual = new CoolAssist_Manual();
        $model_numbers = $coolassist_manual->get_all_model_numbers();

        wp_send_json_success($model_numbers);
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
    error_log('ajax_upload_manual called');
    
    if (!check_ajax_referer('upload_ac_manual', 'upload_manual_nonce', false)) {
        error_log('Nonce check failed');
        wp_send_json_error('Nonce verification failed');
        return;
    }

    if (!current_user_can('manage_options')) {
        error_log('Unauthorized access');
        wp_send_json_error('Unauthorized access');
        return;
    }

    if (!isset($_POST['model_number']) || !isset($_FILES['manual_file'])) {
        error_log('Missing required fields');
        wp_send_json_error('Missing required fields');
        return;
    }

    $model_number = sanitize_text_field($_POST['model_number']);
    $file = $_FILES['manual_file'];
    $chunk = isset($_POST['chunk']) ? intval($_POST['chunk']) : 0;
    $chunks = isset($_POST['chunks']) ? intval($_POST['chunks']) : 1;

    error_log('Uploading manual for model: ' . $model_number . ', Chunk: ' . $chunk . '/' . $chunks);

    $coolassist_manual = new CoolAssist_Manual();
    $result = $coolassist_manual->upload_manual($model_number, $file, $chunk, $chunks);

    if (is_wp_error($result)) {
        error_log('Upload failed: ' . $result->get_error_message());
        wp_send_json_error($result->get_error_message());
    } else {
        error_log('Chunk uploaded successfully');
        wp_send_json_success('Chunk uploaded successfully');
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

    public function get_chat_history() {
        check_ajax_referer('get_chat_history_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized access');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'coolassist_chat_history';

        $username = isset($_POST['username']) ? sanitize_text_field($_POST['username']) : '';
        $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';

        $query = "SELECT ch.*, u.user_login as username
                  FROM $table_name ch
                  JOIN {$wpdb->users} u ON ch.user_id = u.ID
                  WHERE 1=1";

        if (!empty($username)) {
            $query .= $wpdb->prepare(" AND u.user_login LIKE %s", '%' . $wpdb->esc_like($username) . '%');
        }

        if (!empty($date)) {
            $query .= $wpdb->prepare(" AND DATE(ch.timestamp) = %s", $date);
        }

        $query .= " ORDER BY ch.timestamp DESC LIMIT 100";

        $results = $wpdb->get_results($query);

        wp_send_json_success($results);
    }

    public function coolassist_page_shortcode() {
    error_log('coolassist_page_shortcode called');
    $coolassist_user = new CoolAssist_User();
    $is_logged_in = $coolassist_user->is_logged_in();
    
    error_log('User logged in: ' . ($is_logged_in ? 'Yes' : 'No'));
    
    ob_start();
    if ($is_logged_in) {
        error_log('Attempting to include chat template');
        include COOLASSIST_PLUGIN_DIR . 'templates/coolassist-chat.php';
    } else {
        error_log('Attempting to include login template');
        include COOLASSIST_PLUGIN_DIR . 'templates/coolassist-login.php';
    }
    $output = ob_get_clean();
    error_log('Template included. Output length: ' . strlen($output));
    return $output;
}

    private function clean_temp_images() {
        $temp_dir = wp_upload_dir()['path'] . '/coolassist_temp_images/';
        $files = glob($temp_dir . '*');
        $now = time();

        foreach ($files as $file) {
            if (is_file($file)) {
                if ($now - filemtime($file) >= 24 * 3600) { // 24 hours
                    unlink($file);
                }
            }
        }
    }

    public function __destruct() {
        $this->clean_temp_images();
    }
}
