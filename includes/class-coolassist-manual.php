<?php
class CoolAssist_Manual {
    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'coolassist_manuals';
    }

    public function upload_manual($model_number, $file) {
        $upload_dir = wp_upload_dir();
        $file_name = wp_unique_filename($upload_dir['path'], $file['name']);
        $file_path = $upload_dir['path'] . '/' . $file_name;

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

            return $result ? $wpdb->insert_id : false;
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
