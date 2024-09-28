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
                $('#login-loading').hide();
                if (response.success) {
                    $('#login-message').html('<p class="success">' + response.data.message + '</p>');
                    setTimeout(function() {
                        window.location.href = response.data.redirect;
                    }, 1000);
                } else {
                    $('#login-message').html('<p class="error">' + response.data + '</p>');
                }
            },
            error: function(xhr, status, error) {
                $('#login-loading').hide();
                $('#login-message').html('<p class="error">Login error: ' + error + '</p>');
            }
        });
    });

    // Logout functionality
    $('#coolassist-logout').on('click', function(e) {
        e.preventDefault();
        $.ajax({
            url: coolassist_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'coolassist_logout',
                nonce: coolassist_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    window.location.reload();
                } else {
                    alert('Logout failed. Please try again.');
                }
            },
            error: function() {
                alert('An error occurred during logout. Please try again.');
            }
        });
    });

    // Image upload handling
    $('#image-upload').on('change', function(e) {
        if (isProcessing) return;

        var file = e.target.files[0];
        if (file) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#image-preview').attr('src', e.target.result).show();
                sendMessage('', file);
            }
            reader.readAsDataURL(file);
        }
    });

    // Chat form handling
    $('#chat-form').on('submit', function(e) {
        e.preventDefault();
        if (isProcessing) return;

        var message = $('#user-message').val();
        var imageFile = $('#image-upload')[0].files[0];

        if (message.trim() === '' && !imageFile) {
            alert('Please enter a message or upload an image.');
            return;
        }

        sendMessage(message, imageFile);
    });

    function sendMessage(message, imageFile = null) {
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
                    displayMessage('AI', formatAIResponse(response.data.message));
                    if (response.data.manual_images) {
                        displayManualImages(response.data.manual_images);
                    }
                    displayButtons(response.data.buttons);
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
            }
        });
    }

    function displayMessage(sender, message) {
        var messageHtml = '<div class="chat-message ' + (sender === 'User' ? 'user-message' : 'ai-message') + '">' +
                          '<strong>' + sender + ':</strong> ' + message + '</div>';
        $('#chat-messages').append(messageHtml);
        $('#chat-messages').scrollTop($('#chat-messages')[0].scrollHeight);
    }

    function formatAIResponse(message) {
        var paragraphs = message.split('\n\n');
        var formattedParagraphs = paragraphs.map(function(paragraph) {
            if (paragraph.includes('\n- ')) {
                var listItems = paragraph.split('\n- ');
                var listHtml = '<ul>';
                listItems.forEach(function(item, index) {
                    if (index > 0) {
                        listHtml += '<li>' + item + '</li>';
                    }
                });
                listHtml += '</ul>';
                return listHtml;
            } else {
                return '<p>' + paragraph + '</p>';
            }
        });

        return formattedParagraphs.join('');
    }

    function displayManualImages(images) {
        images.forEach(function(image) {
            var imageHtml = '<div class="manual-image"><img src="' + image.url + '" alt="Manual Reference"><p>' + image.caption + '</p></div>';
            $('#chat-messages').append(imageHtml);
        });
        $('#chat-messages').scrollTop($('#chat-messages')[0].scrollHeight);
    }

    function displayButtons(buttons) {
        var buttonsHtml = '<div class="chat-buttons">';
        buttons.forEach(function(button) {
            buttonsHtml += '<button class="chat-button" data-action="' + button.action + '">' + button.text + '</button>';
        });
        buttonsHtml += '</div>';
        $('#chat-messages').append(buttonsHtml);
        $('#chat-messages').scrollTop($('#chat-messages')[0].scrollHeight);

        $('.chat-button').on('click', function() {
            var action = $(this).data('action');
            var text = $(this).text();
            sendMessage(text);
        });
    }

    function showTypingIndicator() {
        $('#typing-indicator').show();
    }

    function hideTypingIndicator() {
        $('#typing-indicator').hide();
    }

    function disableInputs() {
        $('#user-message, #chat-form button, #image-upload').prop('disabled', true);
    }

    function enableInputs() {
        $('#user-message, #chat-form button, #image-upload').prop('disabled', false);
    }

    // Model number selection
    $('#model-number-select').on('change', function() {
        var selectedModel = $(this).val();
        if (selectedModel) {
            sendMessage('Selected AC Model: ' + selectedModel);
        }
    });

    // Load chat history on page load
    loadChatHistory();

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

    function displayWelcomeMessage() {
        displayMessage('AI', 'Welcome to CoolAssist! How can I help you with your AC unit today?');
    }
    console.log(coolassist_ajax); // This should log the object if it's correctly localized

});
