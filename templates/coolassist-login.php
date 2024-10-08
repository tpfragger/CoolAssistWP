<div class="coolassist-login-form">
    <h2>Login to CoolAssist</h2>
    <form id="coolassist-login-form" method="post">
        <?php wp_nonce_field('coolassist-nonce', 'coolassist_nonce'); ?>
        <p>
            <label for="username">Username:</label>
            <input type="text" name="username" id="username" required>
        </p>
        <p>
            <label for="password">Password:</label>
            <input type="password" name="password" id="password" required>
        </p>
        <p>
            <input type="submit" value="Log In" class="coolassist-button">
        </p>
    </form>
    <div id="login-message"></div>
    <div id="login-loading" style="display: none;">Logging in...</div>
</div>
