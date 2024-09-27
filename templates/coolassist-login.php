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

<script>
jQuery(document).ready(function($) {
    $('#coolassist-login-form').on('submit', function(e) {
        e.preventDefault();
        $('#login-loading').show();
        var formData = $(this).serialize();
        formData += '&action=coolassist_login&nonce=' + coolassist_ajax.nonce;

        $.ajax({
            url: coolassist_ajax.ajax_url,
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                console.log('Login response:', response);
                $('#login-loading').hide();
                if (response.success) {
                    $('#login-message').html('<p class="success">' + response.data.message + '</p>');
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                } else {
                    $('#login-message').html('<p class="error">' + response.data + '</p>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Login error:', status, error);
                $('#login-loading').hide();
                $('#login-message').html('<p class="error">Login error: ' + error + '</p>');
            }
        });
    });
});
</script>
