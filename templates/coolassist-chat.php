<div class="coolassist-container">
    <div class="coolassist-header">
        <img src="<?php echo COOLASSIST_PLUGIN_URL . 'assets/images/fourstarlogo.png'; ?>" alt="FourStar Logo" class="company-logo">
        <h1>CoolAssist Chatbot</h1>
    </div>
    <div class="coolassist-main">
        <div class="chat-interface">
            <div class="model-selection">
                <select id="model-number-select">
                    <option value="">Select AC Model</option>
                </select>
            </div>
            <div id="chat-messages"></div>
            <div id="predefined-questions">
                <button class="predefined-question">Green light is flashing</button>
                <button class="predefined-question">How to check fault codes</button>
                <button class="predefined-question">Unit is not cooling</button>
                <button class="predefined-question">Unit is not heating</button>
                <button class="predefined-question">Unit is not operating</button>
                <button class="predefined-question">Unit leaks water</button>
                <button class="predefined-question">Unit makes strange sound</button>
                <button class="predefined-question">Remote control issues</button>
                <button class="predefined-question">Bad odor from unit</button>
                <button class="predefined-question">Request general service</button>
            </div>
            <div id="typing-indicator" style="display: none;">AI is typing...</div>
            <form id="chat-form">
                <input type="file" id="image-upload" name="image" accept="image/*" style="display: none;">
                <label for="image-upload" class="coolassist-button image-upload-button">
                    <i class="fas fa-image"></i>
                </label>
                <input type="text" id="user-message" placeholder="Type your message here..." required>
                <button type="submit" class="coolassist-button">Send</button>
            </form>
        </div>
    </div>
</div>
