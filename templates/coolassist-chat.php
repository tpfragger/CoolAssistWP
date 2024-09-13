<div class="coolassist-ai-page">
    <h1>CoolAssist AI</h1>
    
    <div class="image-upload-section">
        <h2>Upload AC Unit Image</h2>
        <form id="image-upload-form" enctype="multipart/form-data">
            <input type="file" name="ac_image" accept="image/*" required>
            <button type="submit">Analyze Image</button>
        </form>
    </div>
    
    <div class="predefined-buttons">
        <h2>Quick Actions</h2>
        <button class="action-button" data-action="common-issues">5 Most Common Issues</button>
        <button class="action-button" data-action="maintenance-tips">Maintenance Tips</button>
        <button class="action-button" data-action="troubleshooting">Troubleshooting Guide</button>
    </div>
    
    <div class="chat-interface">
        <h2>Chat with CoolAssist</h2>
        <div id="chat-messages"></div>
        <form id="chat-form">
            <input type="text" id="user-message" placeholder="Type your message here..." required>
            <button type="submit">Send</button>
        </form>
    </div>
</div>
