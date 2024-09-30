<?php
class CoolAssist_Manual {
    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'coolassist_manuals';
    }

    public function upload_manual($model_number, $file, $chunk = 0, $chunks = 1) {
    $upload_dir = wp_upload_dir();
    $coolassist_upload_dir = $upload_dir['basedir'] . '/coolassist_manuals';
    
    if (!file_exists($coolassist_upload_dir)) {
        if (!wp_mkdir_p($coolassist_upload_dir)) {
            return new WP_Error('directory_creation_failed', 'Failed to create upload directory');
        }
    }

    if (!is_writable($coolassist_upload_dir)) {
        return new WP_Error('directory_not_writable', 'Upload directory is not writable');
    }

    $file_name = sanitize_file_name($file['name']);
    $file_path = $coolassist_upload_dir . '/' . $file_name;

    error_log('Attempting to move uploaded chunk to: ' . $file_path);

    if ($chunk == 0) {
        $out = @fopen("{$file_path}.part", "wb");
    } else {
        $out = @fopen("{$file_path}.part", "ab");
    }

    if ($out) {
        $in = @fopen($file['tmp_name'], "rb");
        if ($in) {
            while ($buff = fread($in, 4096)) {
                fwrite($out, $buff);
            }
            @fclose($in);
        }
        @fclose($out);
        @unlink($file['tmp_name']);
    } else {
        return new WP_Error('file_open_failed', 'Failed to open output stream');
    }

    if ($chunk == $chunks - 1) {
        rename("{$file_path}.part", $file_path);
        chmod($file_path, 0644);
        global $wpdb;
        $result = $wpdb->insert(
            $this->table_name,
            array(
                'model_number' => sanitize_text_field($model_number),
                'file_name' => $file_name,
                'file_path' => wp_normalize_path($file_path)
            ),
            array('%s', '%s', '%s')
        );

        if ($result === false) {
            unlink($file_path);
            return new WP_Error('database_insert_failed', 'Failed to insert manual into database: ' . $wpdb->last_error);
        }

        error_log('Manual uploaded successfully: ' . $file_path);
        return $wpdb->insert_id;
    }

    return true;
}

    public function get_manual_url($manual_id) {
    global $wpdb;
    $manual = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $manual_id));
    if ($manual) {
        $upload_dir = wp_upload_dir();
        $file_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $manual->file_path);
        return $file_url;
    }
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
