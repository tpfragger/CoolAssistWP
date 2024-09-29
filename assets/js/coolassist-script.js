jQuery(document).ready(function($) {
    var chatMessages = $('#chat-messages');
    var userInput = $('#user-message');
    var chatForm = $('#chat-form');
    var modelSelect = $('#model-number-select');
    var typingIndicator = $('#typing-indicator');
    var isProcessing = false;

    // Initial predefined questions
    var initialQuestions = [
        "My AC isn't cooling properly",
        "Strange noises coming from the unit",
        "AC is leaking water",
        "Unit won't turn on",
        "Thermostat issues",
        "Poor airflow from vents",
        "AC is short cycling",
        "Unusual odors when AC is running"
    ];

    // Display initial questions
    displayInitialQuestions();

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
                $.each(response.data, function(index, modelNumber) {
                    modelSelect.append($('<option></option>').val(modelNumber).text(modelNumber));
                });
            }
        }
    });

    function displayInitialQuestions() {
        var buttonsHtml = '<div class="chat-buttons">';
        initialQuestions.forEach(function(question) {
            buttonsHtml += '<button class="chat-button" data-action="initial-question">' + question + '</button>';
        });
        buttonsHtml += '</div>';
        chatMessages.append('<div class="ai-message">Please select your issue or type your question:</div>' + buttonsHtml);
    }

    // Handle initial question clicks
    $(document).on('click', '.chat-button[data-action="initial-question"]', function() {
        var question = $(this).text();
        if (modelSelect.val()) {
            sendMessage(question);
        } else {
            alert("Please select an AC model before proceeding with a question.");
        }
    });

    // Model number selection
    modelSelect.on('change', function() {
        var selectedModel = $(this).val();
        if (selectedModel) {
            sendMessage('Selected AC Model: ' + selectedModel);
        }
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
    chatForm.on('submit', function(e) {
        e.preventDefault();
        if (isProcessing) return;

        var message = userInput.val().trim();
        var imageFile = $('#image-upload')[0].files[0];

        if (message === '' && !imageFile) {
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
        formData.append('model_number', modelSelect.val());
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
                    if (response.data.buttons) {
                        displayButtons(response.data.buttons);
                    }
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
                userInput.val('');
                $('#image-upload').val('');
            }
        });
    }

    function displayMessage(sender, message) {
        var messageHtml = '<div class="chat-message ' + (sender === 'User' ? 'user-message' : 'ai-message') + '">' +
                          '<strong>' + sender + ':</strong> ' + message + '</div>';
        chatMessages.append(messageHtml);
        chatMessages.scrollTop(chatMessages[0].scrollHeight);
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
            chatMessages.append(imageHtml);
        });
        chatMessages.scrollTop(chatMessages[0].scrollHeight);
    }

    function displayButtons(buttons) {
        var buttonsHtml = '<div class="chat-buttons">';
        buttons.forEach(function(button) {
            buttonsHtml += '<button class="chat-button" data-action="' + button.action + '">' + button.text + '</button>';
        });
        buttonsHtml += '</div>';
        chatMessages.append(buttonsHtml);
        chatMessages.scrollTop(chatMessages[0].scrollHeight);

        $('.chat-button').on('click', function() {
            var action = $(this).data('action');
            var text = $(this).text();
            sendMessage(text);
        });
    }

    function showTypingIndicator() {
        typingIndicator.show();
    }

    function hideTypingIndicator() {
        typingIndicator.hide();
    }

    function disableInputs() {
        userInput.prop('disabled', true);
        chatForm.find('button[type="submit"]').prop('disabled', true);
        $('#image-upload').prop('disabled', true);
    }

    function enableInputs() {
        userInput.prop('disabled', false);
        chatForm.find('button[type="submit"]').prop('disabled', false);
        $('#image-upload').prop('disabled', false);
    }

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
                console.log('Full login response:', response);
                $('#login-loading').hide();
                if (response.success) {
                    $('#login-message').html('<p class="success">' + response.data.message + '</p>');
                    console.log('Login successful, reloading page');
                    setTimeout(function() {
                        location.reload();
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

    // Load chat history on page load
    loadChatHistory();

    function saveChatHistory() {
        var chatHistory = chatMessages.html();
        sessionStorage.setItem('chatHistory', chatHistory);
    }

    function loadChatHistory() {
        var chatHistory = sessionStorage.getItem('chatHistory');
        if (chatHistory) {
            chatMessages.html(chatHistory);
        } else {
            displayWelcomeMessage();
        }
    }

    function displayWelcomeMessage() {
        displayMessage('AI', 'Welcome to CoolAssist! Please select an AC model from the dropdown menu above, and I\'ll be ready to assist you with technical information and troubleshooting steps.');
    }

    // Save chat history before unloading the page
    $(window).on('beforeunload', function() {
        saveChatHistory();
    });
});
