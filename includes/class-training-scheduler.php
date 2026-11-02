<?php
/**
 * Core business logic, data models, post types, and utility functions for Training Scheduler.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Training_Scheduler_Core {

    const POST_TYPE_PACKAGE = 'training_package';
    const POST_TYPE_BOOKING = 'training_booking';

    const OPTION_BUSINESS_INFO = 'training_business_info';
    const OPTION_BUSINESS_HOURS = 'training_business_hours';

    public static function init() {
        add_action('init', [__CLASS__, 'register_post_types']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
    }

    /**
     * Register Custom Post Types
     */
    public static function register_post_types() {
        // Training Package CPT
        $package_labels = [
            'name'               => __('Training Packages', 'training-scheduler'),
            'singular_name'      => __('Training Package', 'training-scheduler'),
            'add_new'            => __('Add New Package', 'training-scheduler'),
            'add_new_item'       => __('Add New Training Package', 'training-scheduler'),
            'edit_item'          => __('Edit Training Package', 'training-scheduler'),
            'new_item'           => __('New Training Package', 'training-scheduler'),
            'view_item'          => __('View Training Package', 'training-scheduler'),
            'search_items'       => __('Search Training Packages', 'training-scheduler'),
            'not_found'          => __('No packages found', 'training-scheduler'),
            'menu_name'          => __('Packages', 'training-scheduler'),
        ];

        register_post_type(self::POST_TYPE_PACKAGE, [
            'labels'             => $package_labels,
            'public'             => false,
            'show_ui'            => false, // Managed via custom admin menu
            'show_in_menu'       => false,
            'supports'           => ['title', 'editor', 'custom-fields'],
            'capability_type'    => 'post',
        ]);

        // Training Booking CPT
        $booking_labels = [
            'name'               => __('Bookings', 'training-scheduler'),
            'singular_name'      => __('Booking', 'training-scheduler'),
            'add_new'            => __('Add New Booking', 'training-scheduler'),
            'add_new_item'       => __('Add New Booking', 'training-scheduler'),
            'edit_item'          => __('Edit Booking', 'training-scheduler'),
            'new_item'           => __('New Booking', 'training-scheduler'),
            'view_item'          => __('View Booking', 'training-scheduler'),
            'search_items'       => __('Search Bookings', 'training-scheduler'),
            'not_found'          => __('No bookings found', 'training-scheduler'),
            'menu_name'          => __('Bookings', 'training-scheduler'),
        ];

        register_post_type(self::POST_TYPE_BOOKING, [
            'labels'             => $booking_labels,
            'public'             => false,
            'show_ui'            => false, // Managed via custom admin menu
            'show_in_menu'       => false,
            'supports'           => ['title', 'custom-fields'],
            'capability_type'    => 'post',
        ]);
    }

    /**
     * Register Plugin Settings
     */
    public static function register_settings() {
        register_setting('training_scheduler_group', self::OPTION_BUSINESS_INFO, [
            'type'              => 'array',
            'sanitize_callback' => [__CLASS__, 'sanitize_business_info'],
            'default'           => self::get_default_business_info(),
        ]);

        register_setting('training_scheduler_group', self::OPTION_BUSINESS_HOURS, [
            'type'              => 'array',
            'sanitize_callback' => [__CLASS__, 'sanitize_business_hours'],
            'default'           => self::get_default_business_hours(),
        ]);
    }

    public static function get_default_business_info() {
        return [
            'business_name'   => 'Pro Training Studio',
            'email'           => get_option('admin_email', 'trainer@example.com'),
            'phone'           => '+27 (0) 11 555 0192',
            'address'         => '123 Training Way, Sandton, Johannesburg',
            'currency'        => 'R',
            'slot_duration'   => 60, // in minutes
            'booking_notice'  => 2, // minimum hours in advance
            'cancellation_notice' => 24, // hours before session
            'about'           => 'Empowering individuals to achieve their health, fitness, and professional coaching goals with certified trainers.',
        ];
    }

    public static function get_default_business_hours() {
        return [
            'monday'    => ['enabled' => true,  'open' => '08:00', 'close' => '18:00'],
            'tuesday'   => ['enabled' => true,  'open' => '08:00', 'close' => '18:00'],
            'wednesday' => ['enabled' => true,  'open' => '08:00', 'close' => '18:00'],
            'thursday'  => ['enabled' => true,  'open' => '08:00', 'close' => '18:00'],
            'friday'    => ['enabled' => true,  'open' => '08:00', 'close' => '17:00'],
            'saturday'  => ['enabled' => true,  'open' => '09:00', 'close' => '14:00'],
            'sunday'    => ['enabled' => false, 'open' => '09:00', 'close' => '13:00'],
        ];
    }

    public static function sanitize_business_info($input) {
        $clean = [];
        $clean['business_name']   = sanitize_text_field($input['business_name'] ?? '');
        $clean['email']           = sanitize_email($input['email'] ?? '');
        $clean['phone']           = sanitize_text_field($input['phone'] ?? '');
        $clean['address']         = sanitize_textarea_field($input['address'] ?? '');
        $clean['currency']        = sanitize_text_field($input['currency'] ?? 'R');
        $clean['slot_duration']   = max(15, intval($input['slot_duration'] ?? 60));
        $clean['booking_notice']  = max(0, intval($input['booking_notice'] ?? 2));
        $clean['cancellation_notice'] = max(0, intval($input['cancellation_notice'] ?? 24));
        $clean['about']           = sanitize_textarea_field($input['about'] ?? '');
        return $clean;
    }

    public static function sanitize_business_hours($input) {
        $clean = [];
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        foreach ($days as $day) {
            $enabled = !empty($input[$day]['enabled']);
            $open = sanitize_text_field($input[$day]['open'] ?? '08:00');
            $close = sanitize_text_field($input[$day]['close'] ?? '18:00');
            $clean[$day] = [
                'enabled' => $enabled,
                'open'    => $open,
                'close'   => $close,
            ];
        }
        return $clean;
    }

    public static function get_business_info() {
        $info = get_option(self::OPTION_BUSINESS_INFO, []);
        return wp_parse_args($info, self::get_default_business_info());
    }

    public static function get_business_hours() {
        $hours = get_option(self::OPTION_BUSINESS_HOURS, []);
        return wp_parse_args($hours, self::get_default_business_hours());
    }

    /**
     * Package Helper Methods
     */
    public static function get_packages($args = []) {
        $default = [
            'post_type'      => self::POST_TYPE_PACKAGE,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'menu_order title',
            'order'          => 'ASC',
        ];
        $query_args = wp_parse_args($args, $default);
        $posts = get_posts($query_args);
        $packages = [];

        foreach ($posts as $post) {
            $packages[] = self::format_package($post);
        }

        return $packages;
    }

    public static function get_package($package_id) {
        $post = get_post($package_id);
        if (!$post || $post->post_type !== self::POST_TYPE_PACKAGE) {
            return null;
        }
        return self::format_package($post);
    }

    private static function format_package($post) {
        return [
            'id'               => $post->ID,
            'title'            => $post->post_title,
            'description'      => $post->post_content,
            'price'            => floatval(get_post_meta($post->ID, '_training_price', true) ?: 0),
            'sessions_count'   => intval(get_post_meta($post->ID, '_training_sessions_count', true) ?: 1),
            'duration_minutes' => intval(get_post_meta($post->ID, '_training_duration_minutes', true) ?: 60),
            'features'         => get_post_meta($post->ID, '_training_features', true) ?: '',
            'badge'            => get_post_meta($post->ID, '_training_badge', true) ?: '',
        ];
    }

    public static function save_package($data) {
        $id = !empty($data['id']) ? intval($data['id']) : 0;
        $title = sanitize_text_field($data['title'] ?? 'Untitled Package');
        $description = wp_kses_post($data['description'] ?? '');

        $post_data = [
            'post_title'   => $title,
            'post_content' => $description,
            'post_type'    => self::POST_TYPE_PACKAGE,
            'post_status'  => 'publish',
        ];

        if ($id > 0) {
            $post_data['ID'] = $id;
            $post_id = wp_update_post($post_data);
        } else {
            $post_id = wp_insert_post($post_data);
        }

        if (is_wp_error($post_id) || !$post_id) {
            return false;
        }

        update_post_meta($post_id, '_training_price', floatval($data['price'] ?? 0));
        update_post_meta($post_id, '_training_sessions_count', max(1, intval($data['sessions_count'] ?? 1)));
        update_post_meta($post_id, '_training_duration_minutes', max(15, intval($data['duration_minutes'] ?? 60)));
        update_post_meta($post_id, '_training_features', sanitize_textarea_field($data['features'] ?? ''));
        update_post_meta($post_id, '_training_badge', sanitize_text_field($data['badge'] ?? ''));

        return $post_id;
    }

    public static function delete_package($id) {
        $post = get_post($id);
        if ($post && $post->post_type === self::POST_TYPE_PACKAGE) {
            return wp_delete_post($id, true);
        }
        return false;
    }

    /**
     * Booking Helper Methods
     */
    public static function get_bookings($args = []) {
        $default = [
            'post_type'      => self::POST_TYPE_BOOKING,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'ID',
            'order'          => 'DESC',
        ];
        $query_args = wp_parse_args($args, $default);
        $posts = get_posts($query_args);
        $bookings = [];

        foreach ($posts as $post) {
            $bookings[] = self::format_booking($post);
        }

        return $bookings;
    }

    public static function get_booking($booking_id) {
        $post = get_post($booking_id);
        if (!$post || $post->post_type !== self::POST_TYPE_BOOKING) {
            return null;
        }
        return self::format_booking($post);
    }

    private static function format_booking($post) {
        $package_id = intval(get_post_meta($post->ID, '_booking_package_id', true));
        $package = $package_id ? self::get_package($package_id) : null;

        return [
            'id'           => $post->ID,
            'title'        => $post->post_title,
            'client_name'  => get_post_meta($post->ID, '_booking_client_name', true) ?: '',
            'client_email' => get_post_meta($post->ID, '_booking_client_email', true) ?: '',
            'client_phone' => get_post_meta($post->ID, '_booking_client_phone', true) ?: '',
            'package_id'   => $package_id,
            'package_name' => $package ? $package['title'] : __('Custom / General Session', 'training-scheduler'),
            'date'         => get_post_meta($post->ID, '_booking_date', true) ?: '',
            'start_time'   => get_post_meta($post->ID, '_booking_start_time', true) ?: '',
            'end_time'     => get_post_meta($post->ID, '_booking_end_time', true) ?: '',
            'status'       => get_post_meta($post->ID, '_booking_status', true) ?: 'confirmed',
            'notes'        => get_post_meta($post->ID, '_booking_notes', true) ?: '',
            'created_at'   => $post->post_date,
        ];
    }

    public static function save_booking($data) {
        $id = !empty($data['id']) ? intval($data['id']) : 0;
        $client_name = sanitize_text_field($data['client_name'] ?? '');
        $date = sanitize_text_field($data['date'] ?? '');
        $start_time = sanitize_text_field($data['start_time'] ?? '');
        $end_time = sanitize_text_field($data['end_time'] ?? '');
        $package_id = intval($data['package_id'] ?? 0);
        $status = sanitize_text_field($data['status'] ?? 'confirmed');

        $title = sprintf('%s - %s %s', $client_name ?: 'Client', $date, $start_time);

        $post_data = [
            'post_title'   => $title,
            'post_type'    => self::POST_TYPE_BOOKING,
            'post_status'  => 'publish',
        ];

        if ($id > 0) {
            $post_data['ID'] = $id;
            $post_id = wp_update_post($post_data);
        } else {
            $post_id = wp_insert_post($post_data);
        }

        if (is_wp_error($post_id) || !$post_id) {
            return false;
        }

        update_post_meta($post_id, '_booking_client_name', $client_name);
        update_post_meta($post_id, '_booking_client_email', sanitize_email($data['client_email'] ?? ''));
        update_post_meta($post_id, '_booking_client_phone', sanitize_text_field($data['client_phone'] ?? ''));
        update_post_meta($post_id, '_booking_package_id', $package_id);
        update_post_meta($post_id, '_booking_date', $date);
        update_post_meta($post_id, '_booking_start_time', $start_time);
        update_post_meta($post_id, '_booking_end_time', $end_time);
        update_post_meta($post_id, '_booking_status', $status);
        update_post_meta($post_id, '_booking_notes', sanitize_textarea_field($data['notes'] ?? ''));

        return $post_id;
    }

    public static function delete_booking($id) {
        $post = get_post($id);
        if ($post && $post->post_type === self::POST_TYPE_BOOKING) {
            return wp_delete_post($id, true);
        }
        return false;
    }

    /**
     * Calculate Available Slots for a Given Date
     *
     * @param string $date YYYY-MM-DD
     * @param int $duration_minutes
     * @param int $exclude_booking_id
     * @return array List of slot strings (e.g. ['09:00', '10:00'])
     */
    public static function get_available_slots($date, $duration_minutes = 60, $exclude_booking_id = 0) {
        $timestamp = strtotime($date);
        if (!$timestamp) {
            return [];
        }

        $day_of_week = strtolower(date('l', $timestamp));
        $hours = self::get_business_hours();

        if (empty($hours[$day_of_week]) || empty($hours[$day_of_week]['enabled'])) {
            return []; // Business closed on this day
        }

        $open_str  = $hours[$day_of_week]['open'];
        $close_str = $hours[$day_of_week]['close'];

        $open_ts  = strtotime($date . ' ' . $open_str);
        $close_ts = strtotime($date . ' ' . $close_str);

        if ($close_ts <= $open_ts) {
            return [];
        }

        // Fetch existing bookings on this date (exclude cancelled bookings)
        $bookings = self::get_bookings_for_date($date);

        $slots = [];
        $slot_length_sec = $duration_minutes * 60;
        $current = $open_ts;

        // Minimum booking notice
        $info = self::get_business_info();
        $notice_hours = intval($info['booking_notice'] ?? 2);
        $min_allowed_time = time() + ($notice_hours * 3600);

        while (($current + $slot_length_sec) <= $close_ts) {
            $slot_start_time = date('H:i', $current);
            $slot_end_time   = date('H:i', $current + $slot_length_sec);

            // Check if slot is in the past or within booking notice
            if ($current < $min_allowed_time) {
                $current += $slot_length_sec;
                continue;
            }

            // Check overlap with any existing non-cancelled bookings
            $conflict = false;
            foreach ($bookings as $b) {
                if ($exclude_booking_id && $b['id'] == $exclude_booking_id) {
                    continue;
                }
                if ($b['status'] === 'cancelled') {
                    continue;
                }

                $b_start = strtotime($date . ' ' . $b['start_time']);
                $b_end   = strtotime($date . ' ' . $b['end_time']);

                // Overlap condition: max(startA, startB) < min(endA, endB)
                $slot_start_ts = $current;
                $slot_end_ts   = $current + $slot_length_sec;

                if (max($slot_start_ts, $b_start) < min($slot_end_ts, $b_end)) {
                    $conflict = true;
                    break;
                }
            }

            if (!$conflict) {
                $slots[] = [
                    'start' => $slot_start_time,
                    'end'   => $slot_end_time,
                    'label' => sprintf('%s - %s', $slot_start_time, $slot_end_time),
                ];
            }

            $current += $slot_length_sec;
        }

        return $slots;
    }

    public static function get_bookings_for_date($date) {
        $args = [
            'post_type'      => self::POST_TYPE_BOOKING,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => [
                [
                    'key'     => '_booking_date',
                    'value'   => $date,
                    'compare' => '=',
                ],
            ],
        ];

        $posts = get_posts($args);
        $results = [];
        foreach ($posts as $p) {
            $results[] = self::format_booking($p);
        }
        return $results;
    }
}
