<div class="wrap">
    <h1>CoolAssist Settings</h1>
    
    <h2>AC Manuals</h2>
    <p>Manage AC manuals in the <a href="<?php echo admin_url('edit.php?post_type=ac_manual'); ?>">AC Manuals</a> section.</p>
    
    <h2>User Management</h2>
    <p>Manage AC technician accounts in the <a href="<?php echo admin_url('users.php'); ?>">Users</a> section.</p>
    
    <h3>Create New AC Technician Account</h3>
    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
        <?php wp_nonce_field('create_ac_technician', 'create_ac_technician_nonce'); ?>
        <input type="hidden" name="action" value="create_ac_technician">
        
        <table class="form-table">
            <tr>
                <th><label for="username">Username</label></th>
                <td><input type="text" name="username" id="username" class="regular-text" required></td>
            </tr>
            <tr>
                <th><label for="email">Email</label></th>
                <td><input type="email" name="email" id="email" class="regular-text" required></td>
            </tr>
            <tr>
                <th><label for="password">Password</label></th>
                <td><input type="password" name="password" id="password" class="regular-text" required></td>
            </tr>
        </table>
        
        <?php submit_button('Create AC Technician Account'); ?>
    </form>
</div>
