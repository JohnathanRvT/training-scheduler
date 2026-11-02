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
        register_post_type(self::POST_TYPE_PACKAGE, [
            'labels'             => [
                'name'          => __('Training Packages', 'training-scheduler'),
                'singular_name' => __('Training Package', 'training-scheduler'),
            ],
            'public'             => false,
            'show_ui'            => false,
            'show_in_menu'       => false,
            'supports'           => ['title', 'editor', 'custom-fields'],
            'capability_type'    => 'post',
        ]);

        // Training Booking CPT
        register_post_type(self::POST_TYPE_BOOKING, [
            'labels'             => [
                'name'          => __('Bookings', 'training-scheduler'),
                'singular_name' => __('Booking', 'training-scheduler'),
            ],
            'public'             => false,
            'show_ui'            => false,
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
            'business_name' => 'Pro Training Studio',
            'email'         => get_option('admin_email', 'trainer@example.com'),
            'phone'         => '+27 (0) 11 555 0192',
            'address'       => '123 Training Way, Sandton, Johannesburg',
            'currency'      => 'R',
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
        $clean['business_name'] = sanitize_text_field($input['business_name'] ?? '');
        $clean['email']         = sanitize_email($input['email'] ?? '');
        $clean['phone']         = sanitize_text_field($input['phone'] ?? '');
        $clean['address']       = sanitize_textarea_field($input['address'] ?? '');
        $clean['currency']      = sanitize_text_field($input['currency'] ?? 'R');
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
            'orderby'        => 'title',
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
            'duration_minutes' => intval(get_post_meta($post->ID, '_training_duration_minutes', true) ?: 60),
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
        update_post_meta($post_id, '_training_duration_minutes', max(15, intval($data['duration_minutes'] ?? 60)));

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
            'id'             => $post->ID,
            'title'          => $post->post_title,
            'client_name'    => get_post_meta($post->ID, '_booking_client_name', true) ?: '',
            'client_email'   => get_post_meta($post->ID, '_booking_client_email', true) ?: '',
            'client_phone'   => get_post_meta($post->ID, '_booking_client_phone', true) ?: '',
            'package_id'     => $package_id,
            'package_name'   => $package ? $package['title'] : __('Custom / General Session', 'training-scheduler'),
            'duration'       => intval(get_post_meta($post->ID, '_booking_duration', true) ?: ($package ? $package['duration_minutes'] : 60)),
            'date'           => get_post_meta($post->ID, '_booking_date', true) ?: '',
            'start_time'     => get_post_meta($post->ID, '_booking_start_time', true) ?: '',
            'end_time'       => get_post_meta($post->ID, '_booking_end_time', true) ?: '',
            'status'         => get_post_meta($post->ID, '_booking_status', true) ?: 'confirmed',
            'payment_status' => get_post_meta($post->ID, '_booking_payment_status', true) ?: 'unpaid',
            'payment_method' => get_post_meta($post->ID, '_booking_payment_method', true) ?: 'unpaid',
            'notes'          => get_post_meta($post->ID, '_booking_notes', true) ?: '',
            'created_at'     => $post->post_date,
        ];
    }

    /**
     * Save / Update Booking with Strict Validation
     * Validates business open hours and prevents overlapping / double bookings.
     * Automatically computes end_time from start_time and duration.
     *
     * @param array $data
     * @return int|WP_Error Post ID on success or WP_Error on validation failure
     */
    public static function save_booking($data) {
        $id = !empty($data['id']) ? intval($data['id']) : 0;
        $client_name = sanitize_text_field($data['client_name'] ?? '');
        $date = sanitize_text_field($data['date'] ?? '');
        $start_time = sanitize_text_field($data['start_time'] ?? '');
        $package_id = intval($data['package_id'] ?? 0);
        $status = sanitize_text_field($data['status'] ?? 'confirmed');
        $payment_status = sanitize_text_field($data['payment_status'] ?? 'unpaid');
        $payment_method = sanitize_text_field($data['payment_method'] ?? 'cash');

        if (empty($client_name)) {
            return new WP_Error('missing_client_name', __('Client name is required.', 'training-scheduler'));
        }

        if (empty($date) || empty($start_time)) {
            return new WP_Error('missing_datetime', __('Date and start time are required.', 'training-scheduler'));
        }

        // Determine duration: if package exists, use its duration; otherwise fallback to input or default 60
        $duration = 60;
        if ($package_id > 0) {
            $package = self::get_package($package_id);
            if ($package && !empty($package['duration_minutes'])) {
                $duration = intval($package['duration_minutes']);
            }
        } elseif (!empty($data['duration_minutes'])) {
            $duration = max(15, intval($data['duration_minutes']));
        }

        // Calculate end_time
        $start_ts = strtotime($date . ' ' . $start_time);
        if (!$start_ts) {
            return new WP_Error('invalid_datetime', __('Invalid date or start time format.', 'training-scheduler'));
        }
        $end_ts = $start_ts + ($duration * 60);
        $end_time = date('H:i', $end_ts);

        // 1. Validate Business Day & Hours
        $day_of_week = strtolower(date('l', $start_ts));
        $hours = self::get_business_hours();

        if (empty($hours[$day_of_week]) || empty($hours[$day_of_week]['enabled'])) {
            return new WP_Error(
                'business_closed',
                sprintf(__('The business is closed on %ss. Please select an open business day.', 'training-scheduler'), ucfirst($day_of_week))
            );
        }

        $biz_open_ts  = strtotime($date . ' ' . $hours[$day_of_week]['open']);
        $biz_close_ts = strtotime($date . ' ' . $hours[$day_of_week]['close']);

        if ($start_ts < $biz_open_ts || $end_ts > $biz_close_ts) {
            return new WP_Error(
                'outside_hours',
                sprintf(
                    __('Session (%s to %s) is outside of %s operating hours (%s - %s).', 'training-scheduler'),
                    $start_time,
                    $end_time,
                    ucfirst($day_of_week),
                    $hours[$day_of_week]['open'],
                    $hours[$day_of_week]['close']
                )
            );
        }

        // 2. Validate Overlap / Conflict with existing non-cancelled bookings
        $existing_bookings = self::get_bookings_for_date($date);
        foreach ($existing_bookings as $b) {
            if ($id && $b['id'] == $id) {
                continue; // Skip self when editing
            }
            if ($b['status'] === 'cancelled') {
                continue; // Cancelled sessions don't block slots
            }

            $b_start = strtotime($date . ' ' . $b['start_time']);
            $b_end   = strtotime($date . ' ' . $b['end_time']);

            // Overlap check
            if (max($start_ts, $b_start) < min($end_ts, $b_end)) {
                return new WP_Error(
                    'time_conflict',
                    sprintf(
                        __('Booking conflict: Another session is already scheduled on %s from %s to %s (%s).', 'training-scheduler'),
                        $date,
                        $b['start_time'],
                        $b['end_time'],
                        $b['client_name']
                    )
                );
            }
        }

        $title = sprintf('%s - %s %s', $client_name, $date, $start_time);

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
            return new WP_Error('save_failed', __('Failed to save booking to database.', 'training-scheduler'));
        }

        update_post_meta($post_id, '_booking_client_name', $client_name);
        update_post_meta($post_id, '_booking_client_email', sanitize_email($data['client_email'] ?? ''));
        update_post_meta($post_id, '_booking_client_phone', sanitize_text_field($data['client_phone'] ?? ''));
        update_post_meta($post_id, '_booking_package_id', $package_id);
        update_post_meta($post_id, '_booking_duration', $duration);
        update_post_meta($post_id, '_booking_date', $date);
        update_post_meta($post_id, '_booking_start_time', $start_time);
        update_post_meta($post_id, '_booking_end_time', $end_time);
        update_post_meta($post_id, '_booking_status', $status);
        update_post_meta($post_id, '_booking_payment_status', $payment_status);
        update_post_meta($post_id, '_booking_payment_method', $payment_method);
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
