<div class="coolassist-login-form">
    <h2>Login to CoolAssist</h2>
    <?php
    $args = array(
        'redirect' => home_url('/coolassist-ai'),
        'form_id' => 'coolassist_login_form',
        'label_username' => __('Username', 'coolassist'),
        'label_password' => __('Password', 'coolassist'),
        'label_remember' => __('Remember Me', 'coolassist'),
        'label_log_in' => __('Log In', 'coolassist'),
    );
    wp_login_form($args);
    ?>
</div>
