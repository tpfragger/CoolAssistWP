jQuery(document).ready(function($) {
    // Image upload handling
    $('#image-upload-form').on('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        
        $.ajax({
            url: coolassist_ajax.ajax_url,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            headers: {
                'X-WP-Nonce': coolassist_ajax.nonce
            },
            success: function(response) {
                displayMessage('AI', response.content[0].text);
            },
            error: function(xhr, status, error) {
                alert('Error uploading image: ' + error);
            }
        });
    });

    // Predefined button handling
    $('.action-button').on('click', function() {
        var action = $(this).data('action');
        var message = '';
        
        switch(action) {
            case 'common-issues':
                message = 'What are the 5 most common issues with this AC unit?';
                break;
            case 'maintenance-tips':
                message = 'Provide maintenance tips for this AC unit.';
                break;
            case 'troubleshooting':
                message = 'Give me a troubleshooting guide for this AC unit.';
                break;
        }
        
        sendMessage(message);
    });

    // Chat form handling
    $('#chat-form').on('submit', function(e) {
        e.preventDefault();
        var message = $('#user-message').val();
        if (message.trim() !== '') {
            sendMessage(message);
            $('#user-message').val('');
        }
    });

    function sendMessage(message) {
        displayMessage('User', message);
        
        $.ajax({
            url: coolassist_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'coolassist_chat',
                message: message,
                nonce: coolassist_ajax.nonce
            },
            success: function(response) {
                displayMessage('AI', response.content[0].text);
            },
            error: function(xhr, status, error) {
                alert('Error sending message: ' + error);
            }
        });
    }

    function displayMessage(sender, message) {
        var messageHtml = '<p><strong>' + sender + ':</strong> ' + message + '</p>';
        $('#chat-messages').append(messageHtml);
        $('#chat-messages').scrollTop($('#chat-messages')[0].scrollHeight);
    }
});
