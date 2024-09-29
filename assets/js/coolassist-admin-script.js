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
    var formData = new FormData(this);
    formData.append('action', 'coolassist_upload_manual');
    formData.append('nonce', coolassist_ajax.nonce);

    $.ajax({
        url: coolassist_ajax.ajax_url,
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        success: function(response) {
            if (response.success) {
                alert('Manual uploaded successfully!');
                location.reload();
            } else {
                alert('Error uploading manual: ' + response.data);
            }
        },
        error: function(xhr, status, error) {
            console.error('Upload error:', xhr.responseText);
            alert('Error uploading manual: ' + error);
        }
    });
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
