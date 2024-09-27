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
    $('#image-upload').on('change', function(e) {
        if (isProcessing) return;

        isProcessing = true;
        showTypingIndicator();
        disableInputs();

        var formData = new FormData();
        formData.append('action', 'coolassist_upload_image');
        formData.append('nonce', coolassist_ajax.nonce);
        formData.append('image', e.target.files[0]);
        
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
                    displayFollowUpQuestions(response.data.follow_up_questions);
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
                    displayMessage('AI', response.data.message);
                    displayFollowUpQuestions(response.data.follow_up_questions);
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

    function displayFollowUpQuestions(questions) {
        if (questions && questions.length > 0) {
            var $questionContainer = $('<div class="follow-up-questions"></div>');
            questions.forEach(function(question) {
                $questionContainer.append('<button class="follow-up-question">' + question + '</button>');
            });
            $('#chat-messages').append($questionContainer);
        }
    }

    $(document).on('click', '.follow-up-question', function() {
        var question = $(this).text();
        sendMessage(question);
    });

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

    // Handle file input change
    $('#image-upload').on('change', function() {
        if (this.files && this.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#image-preview').attr('src', e.target.result).show();
            }
            reader.readAsDataURL(this.files[0]);
        }
    });

    // Handle chat form submission with image
    $('#chat-form').on('submit', function(e) {
        e.preventDefault();
        if (isProcessing) return;

        var message = $('#user-message').val();
        var imageFile = $('#image-upload')[0].files[0];

        if (message.trim() === '' && !imageFile) {
            alert('Please enter a message or upload an image.');
            return;
        }

        isProcessing = true;
        showTypingIndicator();
        disableInputs();

        var formData = new FormData();
        formData.append('action', 'coolassist_chat');
        formData.append('nonce', coolassist_ajax.nonce);
        formData.append('message', message);
        formData.append('model_number', $('#model-number-select').val());
        if (imageFile) {
            formData.append('image', imageFile);
        }

        $.ajax({
            url: coolassist_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    if (imageFile) {
                        displayMessage('User', '<img src="' + response.data.image_url + '" alt="Uploaded Image" style="max-width: 100%; height: auto;">');
                    }
                    if (message.trim() !== '') {
                        displayMessage('User', message);
                    }
                    displayMessage('AI', response.data.message);
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
                $('#user-message').val('');
                $('#image-upload').val('');
                $('#image-preview').attr('src', '').hide();
            }
        });
    });
});
