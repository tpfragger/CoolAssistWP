jQuery(document).ready(function($) {
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
                    displayMessage('AI', response.data.content[0].text);
                } else {
                    displayMessage('AI', 'Error: ' + (response.data || 'Unable to process your request. Please try again.'));
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                displayMessage('AI', 'Error: Unable to communicate with the server. Please try again later.');
            }
        });
    });

    // Quick action buttons handling
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
        
        if (message) {
            sendMessage(message);
        }
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
            }
        });
    }

    function displayMessage(sender, message) {
        var messageHtml = '<p><strong>' + sender + ':</strong> ' + message + '</p>';
        $('#chat-messages').append(messageHtml);
        $('#chat-messages').scrollTop($('#chat-messages')[0].scrollHeight);
    }

    // Clear chat functionality
    $('#clear-chat').on('click', function() {
        $('#chat-messages').empty();
        $('#model-number-select').val('').trigger('change');
        $('#image-upload-form')[0].reset();
    });

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
