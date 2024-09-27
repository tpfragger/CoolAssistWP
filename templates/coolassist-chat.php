<div class="coolassist-container">
    <div class="coolassist-header">
        <img src="<?php echo COOLASSIST_PLUGIN_URL . 'assets/images/fourstarlogo.png'; ?>" alt="FourStar Logo" class="company-logo">
        <h1>CoolAssist AI</h1>
    </div>
    <div class="coolassist-main">
        <div class="coolassist-sidebar">
            <div class="image-upload-section">
                <h2>Upload AC Unit Image</h2>
                <form id="image-upload-form" enctype="multipart/form-data">
                    <div class="file-input-wrapper">
                        <input type="file" name="image" accept="image/*" required id="image-upload">
                        <label for="image-upload">Choose File</label>
                    </div>
                    <button type="submit" class="coolassist-button">Analyze Image</button>
                </form>
            </div>

            <div class="model-selection">
                <h2>Select AC Model</h2>
                <select id="model-number-select">
                    <option value="">Select a model number</option>
                </select>
            </div>

            <div class="quick-actions">
                <h2>Quick Actions</h2>
                <button class="action-button coolassist-button" data-action="common-issues">5 Most Common Issues</button>
                <button class="action-button coolassist-button" data-action="maintenance-tips">Maintenance Tips</button>
                <button class="action-button coolassist-button" data-action="troubleshooting">Troubleshooting Guide</button>
            </div>
        </div>
        <div class="chat-interface">
        <h2>Chat with CoolAssist</h2>
        <div id="chat-messages">
            <?php
            $coolassist_user = new CoolAssist_User();
            $user_id = $coolassist_user->get_current_user_id();
            $coolassist = new CoolAssist();
            $chat_history = $coolassist->get_chat_history($user_id);
            if ($chat_history) {
                foreach ($chat_history as $message) {
                    $sender = $message->sender === 'user' ? 'User' : 'AI';
                    echo '<div class="chat-message ' . ($message->sender === 'user' ? 'user-message' : 'ai-message') . '">';
                    echo '<strong>' . $sender . ':</strong> ' . wp_kses_post($message->message);
                    echo '</div>';
                }
            }
            ?>
        </div>

            <div id="typing-indicator" style="display: none;">AI is typing...</div>
            <form id="chat-form">
                <input type="text" id="user-message" placeholder="Type your message here..." required>
                <button type="submit" class="coolassist-button">Send</button>
            </form>
            <button id="clear-chat" class="coolassist-button">Clear Chat</button>
        </div>
    </div>
</div>
