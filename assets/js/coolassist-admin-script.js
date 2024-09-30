jQuery(document).ready(function($) {
    // Create user form handling
    $('#create-user-form').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        formData += '&action=coolassist_create_user&nonce=' + coolassist_ajax.nonce;

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    alert('User created successfully!');
                    location.reload();
                } else {
                    alert('Error creating user: ' + response.data);
                }
            },
            error: function(xhr, status, error) {
                alert('Error creating user: ' + error);
            }
        });
    });

    // Delete user
    $('.delete-user').on('click', function() {
        var userId = $(this).data('user-id');
        if (confirm('Are you sure you want to delete this user?')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'coolassist_delete_user',
                    nonce: coolassist_ajax.nonce,
                    user_id: userId
                },
                success: function(response) {
                    if (response.success) {
                        alert('User deleted successfully!');
                        location.reload();
                    } else {
                        alert('Error deleting user: ' + response.data);
                    }
                },
                error: function(xhr, status, error) {
                    alert('Error deleting user: ' + error);
                }
            });
        }
    });

    // Reset password
    $('.reset-password').on('click', function() {
        var userId = $(this).data('user-id');
        if (confirm('Are you sure you want to reset this user\'s password?')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'coolassist_reset_password',
                    nonce: coolassist_ajax.nonce,
                    user_id: userId
                },
                success: function(response) {
                    if (response.success) {
                        alert('Password reset successfully. New password: ' + response.data.new_password);
                    } else {
                        alert('Error resetting password: ' + response.data);
                    }
                },
                error: function(xhr, status, error) {
                    alert('Error resetting password: ' + error);
                }
            });
        }
    });

    $('#upload-manual-form').on('submit', function(e) {
    e.preventDefault();
    var file = $('#manual_file')[0].files[0];
    var chunkSize = 500 * 1024; // 500KB chunks
    var chunks = Math.ceil(file.size / chunkSize);
    var currentChunk = 0;
    var model_number = $('#model_number').val();

    function uploadChunk() {
        var start = currentChunk * chunkSize;
        var end = Math.min(file.size, start + chunkSize);
        var chunk = file.slice(start, end);

        var formData = new FormData();
        formData.append('action', 'coolassist_upload_manual');
        formData.append('upload_manual_nonce', $('#upload_manual_nonce').val());
        formData.append('model_number', model_number);
        formData.append('manual_file', chunk, file.name);
        formData.append('chunk', currentChunk);
        formData.append('chunks', chunks);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                if (response.success) {
                    currentChunk++;
                    if (currentChunk < chunks) {
                        uploadChunk();
                    } else {
                        alert('Manual uploaded successfully!');
                        location.reload();
                    }
                } else {
                    alert('Error uploading manual: ' + response.data);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('Error details:', {
                    status: jqXHR.status,
                    statusText: jqXHR.statusText,
                    responseText: jqXHR.responseText
                });
                alert('Error uploading manual. Please check the console for details.');
            }
        });
    }

    uploadChunk();
});

    // Delete manual
    $('.delete-manual').on('click', function() {
        var manualId = $(this).data('manual-id');
        if (confirm('Are you sure you want to delete this manual?')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'coolassist_delete_manual',
                    nonce: coolassist_ajax.nonce,
                    manual_id: manualId
                },
                success: function(response) {
                    if (response.success) {
                        alert('Manual deleted successfully!');
                        location.reload();
                    } else {
                        alert('Error deleting manual: ' + response.data);
                    }
                },
                error: function(xhr, status, error) {
                    alert('Error deleting manual: ' + error);
                }
            });
        }
    });
});
