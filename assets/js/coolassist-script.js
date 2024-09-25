jQuery(document).ready(function($) {
    var isProcessing = false;

    // Populate model number dropdown
    $.ajax({
        url: coolassist_ajax.ajax_url,
        type: 'POST',
        data: {
            action: 'coolassist_get_model_numbers',
            nonce: coolassist_ajax.nonce
        },
        success: function(response) {
            if (response.success) {
                var select = $('#model-number-select');
                $.each(response.data, function(index, modelNumber) {
                    select.append($('<option></option>').val(modelNumber).text(modelNumber));
                });
            }
        }
    });

    // Image upload handling
    $('#image-upload-form').on('submit', function(e) {
        e.preventDefault();
        if (isProcessing) return;

        isProcessing = true;
        showTypingIndicator();
        disableInputs();

        var formData = new FormData(this);
        formData.append('action', 'coolassist_upload_image');
        formData.append('nonce', coolassist_ajax.nonce);
        
        $.ajax({
            url: coolassist_ajax.ajax_url,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                if (response.success) {
                    displayMessage('User', '<img src="' + response.data.image_url + '" alt="Uploaded Image" style="max-width: 100%; height: auto;">');
                    displayMessage('AI', response.data.content[0].text);
                } else {
                    displayMessage('AI', 'Error: ' + (response.data || 'Unable to process your request. Please try again.'));
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                displayMessage('AI', 'Error: Unable to communicate with the server. Please try again later.');
            },
            complete: function() {
                isProcessing = false;
                hideTypingIndicator();
                enableInputs();
            }
        });
    });

    // Quick action buttons handling
    $('.action-button').on('click', function() {
        if (isProcessing) return;

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
        
        if (message) {
            sendMessage(message);
        }
    });

    // Chat form handling
    $('#chat-form').on('submit', function(e) {
        e.preventDefault();
        if (isProcessing) return;

        var message = $('#user-message').val();
        if (message.trim() !== '') {
            sendMessage(message);
            $('#user-message').val('');
        }
    });

    function sendMessage(message) {
        displayMessage('User', message);
        isProcessing = true;
        showTypingIndicator();
        disableInputs();
        
        var modelNumber = $('#model-number-select').val();
        
        $.ajax({
            url: coolassist_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'coolassist_chat',
                nonce: coolassist_ajax.nonce,
                message: message,
                model_number: modelNumber
            },
            success: function(response) {
                if (response.success) {
                    displayMessage('AI', response.data.content[0].text);
                } else {
                    displayMessage('AI', 'Error: ' + (response.data || 'Unable to process your request. Please try again.'));
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                displayMessage('AI', 'Error: Unable to communicate with the server. Please try again later.');
            },
            complete: function() {
                isProcessing = false;
                hideTypingIndicator();
                enableInputs();
            }
        });
    }

    function displayMessage(sender, message) {
        var messageHtml = '<div class="chat-message ' + (sender === 'User' ? 'user-message' : 'ai-message') + '">' +
                          '<strong>' + sender + ':</strong> ' + message + '</div>';
        $('#chat-messages').append(messageHtml);
        $('#chat-messages').scrollTop($('#chat-messages')[0].scrollHeight);
        saveChatHistory();
    }

    function showTypingIndicator() {
        $('#typing-indicator').show();
    }

    function hideTypingIndicator() {
        $('#typing-indicator').hide();
    }

    function disableInputs() {
        $('#user-message, #chat-form button, #image-upload, #image-upload-form button, .action-button').prop('disabled', true);
    }

    function enableInputs() {
        $('#user-message, #chat-form button, #image-upload, #image-upload-form button, .action-button').prop('disabled', false);
    }

    // Clear chat functionality
    $('#clear-chat').on('click', function() {
        $('#chat-messages').empty();
        $('#model-number-select').val('').trigger('change');
        $('#image-upload-form')[0].reset();
        sessionStorage.removeItem('chatHistory');
        displayWelcomeMessage();
    });

    // Model number selection
    $('#model-number-select').on('change', function() {
        var selectedModel = $(this).val();
        if (selectedModel) {
            displayModelSelection(selectedModel);
        }
    });

    function displayWelcomeMessage() {
        displayMessage('AI', 'Welcome to CoolAssist! How can I help you with your AC unit today?');
    }

    function displayModelSelection(modelNumber) {
        displayMessage('System', 'Selected AC Model: ' + modelNumber);
    }

    function saveChatHistory() {
        var chatHistory = $('#chat-messages').html();
        sessionStorage.setItem('chatHistory', chatHistory);
    }

    function loadChatHistory() {
        var chatHistory = sessionStorage.getItem('chatHistory');
        if (chatHistory) {
            $('#chat-messages').html(chatHistory);
        } else {
            displayWelcomeMessage();
        }
    }

    // Load chat history on page load
    loadChatHistory();

    // Login form handling
    $('#coolassist-login-form').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        formData += '&action=coolassist_login&nonce=' + coolassist_ajax.nonce;

        $.ajax({
            url: coolassist_ajax.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    window.location.href = response.data.redirect;
                } else {
                    alert('Login failed: ' + response.data);
                }
            },
            error: function(xhr, status, error) {
                alert('Login error: ' + error);
            }
        });
    });
});
