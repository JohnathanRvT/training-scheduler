<?php
if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'class-training-scheduler.php';

class JRVT_Plugin_Loader {
    public static function init() {
        // Initialize Core Data Models and Settings
        Training_Scheduler_Core::init();

        // Load Admin functionality for managing packages, calendar, bookings & business info
        if (is_admin()) {
            require_once plugin_dir_path(__FILE__) . '../admin/class-admin-page.php';
        }
    }
}
