<?php
class JRVT_Plugin_Loader {
    public static function init() {
        // Load admin/public functionality
        if (is_admin()) {
            require_once plugin_dir_path(__FILE__) . '../admin/class-admin-page.php';
        } else {
            require_once plugin_dir_path(__FILE__) . '../public/class-public-display.php';
        }
    }
}
