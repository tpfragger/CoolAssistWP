<?php
class CoolAssist_User {
    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'coolassist_users';
    }

    public function get_current_user_id() {
        return isset($_SESSION['coolassist_user_id']) ? $_SESSION['coolassist_user_id'] : null;
    }

    public function create_user($first_name, $last_name, $password) {
        global $wpdb;

        $name = sanitize_text_field($first_name . ' ' . $last_name);
        $username = $this->generate_username($first_name, $last_name);
        $hashed_password = wp_hash_password($password);

        $result = $wpdb->insert(
            $this->table_name,
            array(
                'username' => $username,
                'password' => $hashed_password,
                'name' => $name
            ),
            array('%s', '%s', '%s')
        );

        return $result ? $wpdb->insert_id : false;
    }

    private function generate_username($first_name, $last_name) {
        $base_username = strtolower(sanitize_user($first_name . $last_name));
        $username = $base_username;
        $counter = 1;

        global $wpdb;
        while ($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$this->table_name} WHERE username = %s", $username))) {
            $username = $base_username . $counter;
            $counter++;
        }

        return $username;
    }

    public function get_all_users() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$this->table_name}");
    }

    public function delete_user($user_id) {
        global $wpdb;
        return $wpdb->delete($this->table_name, array('id' => $user_id), array('%d'));
    }

    public function reset_password($user_id, $new_password) {
        global $wpdb;
        $hashed_password = wp_hash_password($new_password);
        return $wpdb->update(
            $this->table_name,
            array('password' => $hashed_password),
            array('id' => $user_id),
            array('%s'),
            array('%d')
        );
    }

    public function authenticate($username, $password) {
        global $wpdb;
        $user = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_name} WHERE username = %s", $username));

        if ($user && wp_check_password($password, $user->password, $user->id)) {
            return $user;
        }

        return false;
    }

    public function is_logged_in() {
        return isset($_SESSION['coolassist_user_id']) && $_SESSION['coolassist_user_id'] > 0;
    }

    public function login($user_id) {
        $_SESSION['coolassist_user_id'] = $user_id;
    }

public function logout() {
    if (session_status() == PHP_SESSION_ACTIVE) {
        session_destroy();
    }
    unset($_SESSION['coolassist_user_id']);
}
}
