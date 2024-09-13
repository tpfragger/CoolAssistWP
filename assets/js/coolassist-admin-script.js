jQuery(document).ready(function($) {
    // Handle AC technician account creation
    $('#create-ac-technician-form').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    alert('AC technician account created successfully!');
                    $('#create-ac-technician-form')[0].reset();
                } else {
                    alert('Error creating AC technician account: ' + response.data.message);
                }
            },
            error: function(xhr, status, error) {
                alert('Error creating AC technician account: ' + error);
            }
        });
    });
});
