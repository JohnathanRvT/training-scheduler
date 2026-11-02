<?php
/**
 * Admin Management Class for Training Scheduler.
 */

if (!defined('ABSPATH')) {
    exit;
}

class JRVT_Admin_Page {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_training_save_booking', [$this, 'ajax_save_booking']);
        add_action('wp_ajax_training_delete_booking', [$this, 'ajax_delete_booking']);
        add_action('wp_ajax_training_save_package', [$this, 'ajax_save_package']);
        add_action('wp_ajax_training_delete_package', [$this, 'ajax_delete_package']);
        add_action('wp_ajax_training_get_calendar_events', [$this, 'ajax_get_calendar_events']);
        add_action('admin_post_training_save_settings', [$this, 'handle_save_settings']);
    }

    public function add_menu() {
        // Main Menu - Calendar
        add_menu_page(
            __('Training Scheduler', 'training-scheduler'),
            __('Training Sessions', 'training-scheduler'),
            'manage_options',
            'training-scheduler',
            [$this, 'render_calendar_page'],
            'dashicons-calendar-alt',
            26
        );

        // Submenu: Calendar (same as main)
        add_submenu_page(
            'training-scheduler',
            __('Calendar View', 'training-scheduler'),
            __('Calendar', 'training-scheduler'),
            'manage_options',
            'training-scheduler',
            [$this, 'render_calendar_page']
        );

        // Submenu: Bookings List
        add_submenu_page(
            'training-scheduler',
            __('All Bookings', 'training-scheduler'),
            __('Bookings', 'training-scheduler'),
            'manage_options',
            'training-bookings',
            [$this, 'render_bookings_page']
        );

        // Submenu: Packages
        add_submenu_page(
            'training-scheduler',
            __('Training Packages', 'training-scheduler'),
            __('Packages', 'training-scheduler'),
            'manage_options',
            'training-packages',
            [$this, 'render_packages_page']
        );

        // Submenu: Business Settings
        add_submenu_page(
            'training-scheduler',
            __('Business Info & Hours', 'training-scheduler'),
            __('Business Settings', 'training-scheduler'),
            'manage_options',
            'training-settings',
            [$this, 'render_settings_page']
        );
    }

    public function enqueue_assets($hook) {
        if (strpos($hook, 'training-') === false) {
            return;
        }

        wp_enqueue_style(
            'training-scheduler-admin',
            TRAINING_SCHEDULER_URL . 'assets/css/calendar.css',
            [],
            TRAINING_SCHEDULER_VERSION
        );

        wp_enqueue_script(
            'training-scheduler-calendar',
            TRAINING_SCHEDULER_URL . 'assets/js/calendar.js',
            ['jquery'],
            TRAINING_SCHEDULER_VERSION,
            true
        );

        wp_localize_script('training-scheduler-calendar', 'TrainingAdminData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('training_admin_nonce'),
            'currency' => Training_Scheduler_Core::get_business_info()['currency'] ?? '$',
        ]);
    }

    public function render_calendar_page() {
        $packages = Training_Scheduler_Core::get_packages();
        $business_info = Training_Scheduler_Core::get_business_info();
        require TRAINING_SCHEDULER_PATH . 'templates/admin-calendar.php';
    }

    public function render_bookings_page() {
        $bookings = Training_Scheduler_Core::get_bookings();
        $packages = Training_Scheduler_Core::get_packages();
        require TRAINING_SCHEDULER_PATH . 'templates/admin-bookings.php';
    }

    public function render_packages_page() {
        $packages = Training_Scheduler_Core::get_packages();
        $business_info = Training_Scheduler_Core::get_business_info();
        require TRAINING_SCHEDULER_PATH . 'templates/admin-packages.php';
    }

    public function render_settings_page() {
        $business_info = Training_Scheduler_Core::get_business_info();
        $business_hours = Training_Scheduler_Core::get_business_hours();
        require TRAINING_SCHEDULER_PATH . 'templates/admin-settings.php';
    }

    public function handle_save_settings() {
        if (!current_user_can('manage_options') || !check_admin_referer('training_save_settings_nonce')) {
            wp_die(__('Unauthorized action.', 'training-scheduler'));
        }

        if (isset($_POST['business_info'])) {
            $info = Training_Scheduler_Core::sanitize_business_info($_POST['business_info']);
            update_option(Training_Scheduler_Core::OPTION_BUSINESS_INFO, $info);
        }

        if (isset($_POST['business_hours'])) {
            $hours = Training_Scheduler_Core::sanitize_business_hours($_POST['business_hours']);
            update_option(Training_Scheduler_Core::OPTION_BUSINESS_HOURS, $hours);
        }

        wp_redirect(add_query_arg(['page' => 'training-settings', 'updated' => 'true'], admin_url('admin.php')));
        exit;
    }

    public function ajax_get_calendar_events() {
        check_ajax_referer('training_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $bookings = Training_Scheduler_Core::get_bookings();
        $events = [];

        foreach ($bookings as $b) {
            $events[] = [
                'id'          => $b['id'],
                'title'       => $b['client_name'] . ' (' . $b['package_name'] . ')',
                'date'        => $b['date'],
                'start_time'  => $b['start_time'],
                'end_time'    => $b['end_time'],
                'status'      => $b['status'],
                'client_name' => $b['client_name'],
                'client_email'=> $b['client_email'],
                'client_phone'=> $b['client_phone'],
                'package_id'  => $b['package_id'],
                'package_name'=> $b['package_name'],
                'notes'       => $b['notes'],
            ];
        }

        wp_send_json_success(['events' => $events]);
    }

    public function ajax_save_booking() {
        check_ajax_referer('training_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $booking_id = Training_Scheduler_Core::save_booking($_POST);
        if ($booking_id) {
            wp_send_json_success([
                'message' => __('Booking saved successfully!', 'training-scheduler'),
                'booking' => Training_Scheduler_Core::get_booking($booking_id),
            ]);
        } else {
            wp_send_json_error(['message' => __('Failed to save booking.', 'training-scheduler')]);
        }
    }

    public function ajax_delete_booking() {
        check_ajax_referer('training_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $booking_id = intval($_POST['id'] ?? 0);
        if ($booking_id && Training_Scheduler_Core::delete_booking($booking_id)) {
            wp_send_json_success(['message' => __('Booking removed.', 'training-scheduler')]);
        } else {
            wp_send_json_error(['message' => __('Failed to remove booking.', 'training-scheduler')]);
        }
    }

    public function ajax_save_package() {
        check_ajax_referer('training_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $package_id = Training_Scheduler_Core::save_package($_POST);
        if ($package_id) {
            wp_send_json_success([
                'message' => __('Package saved successfully!', 'training-scheduler'),
                'package' => Training_Scheduler_Core::get_package($package_id),
            ]);
        } else {
            wp_send_json_error(['message' => __('Failed to save package.', 'training-scheduler')]);
        }
    }

    public function ajax_delete_package() {
        check_ajax_referer('training_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $package_id = intval($_POST['id'] ?? 0);
        if ($package_id && Training_Scheduler_Core::delete_package($package_id)) {
            wp_send_json_success(['message' => __('Package deleted.', 'training-scheduler')]);
        } else {
            wp_send_json_error(['message' => __('Failed to delete package.', 'training-scheduler')]);
        }
    }
}

new JRVT_Admin_Page();
