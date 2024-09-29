<?php
// Start output buffering to capture any unexpected output
ob_start();

// Check if the user is logged in using the CoolAssist_User class
$coolassist_user = new CoolAssist_User();
if (!$coolassist_user->is_logged_in()) {
    error_log('CoolAssist: User not logged in, including login template');
    include COOLASSIST_PLUGIN_DIR . 'templates/coolassist-login.php';
} else {
    error_log('CoolAssist: User logged in, displaying chat interface');
?>
<div class="coolassist-container">
    <div class="coolassist-header">
        <img src="<?php echo esc_url(COOLASSIST_PLUGIN_URL . 'assets/images/fourstarlogo.png'); ?>" alt="FourStar Logo" class="company-logo">
        <h1>CoolAssist Chatbot</h1>
        <button id="coolassist-logout" class="coolassist-button">Logout</button>
    </div>
    <div class="coolassist-main">
        <div class="chat-interface">
            <div class="model-selection">
                <select id="model-number-select">
                    <option value="">Select AC Model</option>
                </select>
            </div>
            <div id="chat-messages"></div>
            <div id="typing-indicator" style="display: none;">AI is typing...</div>
            <form id="chat-form">
                <div id="message-input-container">
                    <input type="text" id="user-message" placeholder="Type your message here...">
                    <div id="predefined-questions"></div>
                </div>
                <input type="file" id="image-upload" name="image" accept="image/*" style="display: none;">
                <label for="image-upload" class="coolassist-button image-upload-button">
                    <i class="fas fa-image"></i>
                </label>
                <button type="submit" class="coolassist-button">Send</button>
            </form>
        </div>
    </div>
</div>
<script>
    console.log('CoolAssist: Chat interface loaded');
    // You can add more JavaScript here to initialize the chat functionality
</script>
<?php
}

// Capture and log any unexpected output
$unexpected_output = ob_get_clean();
if (!empty($unexpected_output)) {
    error_log('CoolAssist: Unexpected output in chat template: ' . $unexpected_output);
}

// Output the captured content
echo $unexpected_output;
?>
