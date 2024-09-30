<?php
class CoolAssist_Manual {
    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'coolassist_manuals';
    }

    public function upload_manual($model_number, $file) {
    $upload_dir = wp_upload_dir();
    $coolassist_upload_dir = $upload_dir['basedir'] . '/coolassist_manuals';
    
    if (!file_exists($coolassist_upload_dir)) {
        wp_mkdir_p($coolassist_upload_dir);
        chmod($coolassist_upload_dir, 0755);
    }

    $file_name = wp_unique_filename($coolassist_upload_dir, $file['name']);
    $file_path = $coolassist_upload_dir . '/' . $file_name;

    error_log('Attempting to move uploaded file to: ' . $file_path);

    if (move_uploaded_file($file['tmp_name'], $file_path)) {
        global $wpdb;
        $result = $wpdb->insert(
            $this->table_name,
            array(
                'model_number' => sanitize_text_field($model_number),
                'file_name' => $file_name,
                'file_path' => $file_path
            ),
            array('%s', '%s', '%s')
        );

        if ($result === false) {
            error_log('Database insert failed: ' . $wpdb->last_error);
            return false;
        }

        error_log('Manual uploaded successfully: ' . $file_path);
        return $wpdb->insert_id;
    }

    error_log('File upload failed: ' . print_r($file, true));
    return false;
}

    public function get_all_manuals() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$this->table_name}");
    }

    public function get_manual_by_model_number($model_number) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_name} WHERE model_number = %s", $model_number));
    }

    public function delete_manual($manual_id) {
        global $wpdb;
        $manual = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $manual_id));

        if ($manual) {
            if (file_exists($manual->file_path)) {
                unlink($manual->file_path);
            }

            return $wpdb->delete($this->table_name, array('id' => $manual_id), array('%d'));
        }

        return false;
    }

    public function get_all_model_numbers() {
        global $wpdb;
        return $wpdb->get_col("SELECT model_number FROM {$this->table_name}");
    }
}
