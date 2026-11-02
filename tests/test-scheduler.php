<?php
/**
 * Standalone Unit and Integration Test for Training Scheduler Core Logic
 */

// Mock WordPress environment functions
define('ABSPATH', __DIR__ . '/../');

class WP_Error {
    private $code;
    private $message;
    public function __construct($code = '', $message = '') {
        $this->code = $code;
        $this->message = $message;
    }
    public function get_error_message() {
        return $this->message;
    }
    public function get_error_code() {
        return $this->code;
    }
}

$mock_options = [];
$mock_posts = [];
$mock_post_meta = [];
$mock_post_id_seq = 100;

function get_option($name, $default = false) {
    global $mock_options;
    return $mock_options[$name] ?? $default;
}

function update_option($name, $value) {
    global $mock_options;
    $mock_options[$name] = $value;
    return true;
}

function register_setting($group, $option_name, $args = []) {}
function register_post_type($post_type, $args = []) {}
function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {}
function __($text, $domain = 'default') { return $text; }
function esc_html__($text, $domain = 'default') { return $text; }
function esc_attr__($text, $domain = 'default') { return $text; }
function sanitize_text_field($str) { return trim(strip_tags((string)$str)); }
function sanitize_email($email) { return filter_var(trim((string)$email), FILTER_SANITIZE_EMAIL); }
function sanitize_textarea_field($str) { return trim(strip_tags((string)$str)); }
function wp_kses_post($str) { return strip_tags((string)$str, '<p><a><strong><em><ul><ol><li><br>'); }
function wp_parse_args($args, $defaults = []) {
    if (is_array($args)) {
        return array_merge($defaults, $args);
    }
    return $defaults;
}
function is_wp_error($thing) { return $thing instanceof WP_Error; }

function wp_insert_post($postarr) {
    global $mock_posts, $mock_post_id_seq;
    $id = ++$mock_post_id_seq;
    $post_obj = (object)[
        'ID' => $id,
        'post_title' => $postarr['post_title'] ?? '',
        'post_content' => $postarr['post_content'] ?? '',
        'post_type' => $postarr['post_type'] ?? 'post',
        'post_status' => $postarr['post_status'] ?? 'publish',
        'post_date' => date('Y-m-d H:i:s'),
    ];
    $mock_posts[$id] = $post_obj;
    return $id;
}

function wp_update_post($postarr) {
    global $mock_posts;
    $id = intval($postarr['ID'] ?? 0);
    if (!isset($mock_posts[$id])) return 0;

    if (isset($postarr['post_title'])) $mock_posts[$id]->post_title = $postarr['post_title'];
    if (isset($postarr['post_content'])) $mock_posts[$id]->post_content = $postarr['post_content'];
    return $id;
}

function get_post($id) {
    global $mock_posts;
    return $mock_posts[$id] ?? null;
}

function wp_delete_post($id, $force = false) {
    global $mock_posts, $mock_post_meta;
    unset($mock_posts[$id]);
    unset($mock_post_meta[$id]);
    return true;
}

function get_posts($args = []) {
    global $mock_posts, $mock_post_meta;
    $results = [];
    $pt = $args['post_type'] ?? 'post';
    $meta_query = $args['meta_query'] ?? [];

    foreach ($mock_posts as $p) {
        if ($p->post_type !== $pt) continue;

        if (!empty($meta_query)) {
            $match = true;
            foreach ($meta_query as $mq) {
                $k = $mq['key'];
                $v = $mq['value'];
                $actual = $mock_post_meta[$p->ID][$k] ?? null;
                if ($actual != $v) {
                    $match = false;
                    break;
                }
            }
            if (!$match) continue;
        }

        $results[] = $p;
    }
    return $results;
}

function get_post_meta($post_id, $key, $single = false) {
    global $mock_post_meta;
    return $mock_post_meta[$post_id][$key] ?? '';
}

function update_post_meta($post_id, $key, $value) {
    global $mock_post_meta;
    if (!isset($mock_post_meta[$post_id])) {
        $mock_post_meta[$post_id] = [];
    }
    $mock_post_meta[$post_id][$key] = $value;
    return true;
}

// Load Core
require_once __DIR__ . '/../includes/class-training-scheduler.php';

echo "Running Training Scheduler Automated Tests...\n";

// Test 1: Business info defaults & sanitization without garbage fields
echo "Test 1: Business Info Defaults & Currency in Rands... ";
$default_info = Training_Scheduler_Core::get_default_business_info();
assert($default_info['currency'] === 'R');
assert(!isset($default_info['about']), "Unused about/bio field should be removed");
assert(!isset($default_info['booking_notice']), "Unused booking notice field should be removed");

$raw_info = [
    'business_name' => '  Joburg High Performance Gym  ',
    'email'         => 'trainer@gym.co.za',
    'phone'         => '011 555 1234',
    'address'       => 'Sandton City, Johannesburg',
    'currency'      => 'R',
    'about'         => 'Garbage bio', // should be ignored
];
$sanitized = Training_Scheduler_Core::sanitize_business_info($raw_info);
assert($sanitized['business_name'] === 'Joburg High Performance Gym');
assert($sanitized['currency'] === 'R');
assert(!isset($sanitized['about']), "About field must not exist in sanitized info");
echo "PASSED\n";

// Test 2: Training Package CRUD (single-day sessions, no session counts/badges)
echo "Test 2: Streamlined Training Package CRUD... ";
$pkg_id = Training_Scheduler_Core::save_package([
    'title'            => '1-on-1 Hypertrophy Training',
    'price'            => '550.00',
    'duration_minutes' => 60,
    'description'      => 'Full personalized workout and form correction'
]);
assert($pkg_id > 0);

$pkg = Training_Scheduler_Core::get_package($pkg_id);
assert($pkg['title'] === '1-on-1 Hypertrophy Training');
assert($pkg['price'] === 550.0);
assert($pkg['duration_minutes'] === 60);
assert(!isset($pkg['sessions_count']), "Sessions count removed");
assert(!isset($pkg['badge']), "Badge removed");
echo "PASSED\n";

// Test 3: Operating Hours Configuration
echo "Test 3: Setup Business Hours for Tests... ";
$hours = [
    'monday'    => ['enabled' => true,  'open' => '08:00', 'close' => '17:00'],
    'tuesday'   => ['enabled' => true,  'open' => '08:00', 'close' => '17:00'],
    'wednesday' => ['enabled' => true,  'open' => '08:00', 'close' => '17:00'],
    'thursday'  => ['enabled' => true,  'open' => '08:00', 'close' => '17:00'],
    'friday'    => ['enabled' => true,  'open' => '08:00', 'close' => '16:00'],
    'saturday'  => ['enabled' => true,  'open' => '09:00', 'close' => '13:00'],
    'sunday'    => ['enabled' => false, 'open' => '09:00', 'close' => '12:00'],
];
update_option(Training_Scheduler_Core::OPTION_BUSINESS_HOURS, $hours);
echo "PASSED\n";

// Find next upcoming Monday for tests
$next_monday = date('Y-m-d', strtotime('next monday'));
// Find next upcoming Sunday (closed day)
$next_sunday = date('Y-m-d', strtotime('next sunday'));

// Test 4: Booking on Closed Day must be rejected
echo "Test 4: Rejection of Booking on Closed Day... ";
$closed_res = Training_Scheduler_Core::save_booking([
    'client_name'    => 'Sunday Client',
    'package_id'     => $pkg_id,
    'date'           => $next_sunday,
    'start_time'     => '10:00',
    'payment_status' => 'unpaid',
]);
assert(is_wp_error($closed_res), "Booking on closed day (Sunday) must fail");
assert($closed_res->get_error_code() === 'business_closed');
echo "PASSED\n";

// Test 5: Booking Outside Operating Hours must be rejected
echo "Test 5: Rejection of Booking Outside Operating Hours... ";
// Monday closes at 17:00. Session from 16:30 for 60 mins ends at 17:30 (outside hours).
$too_late_res = Training_Scheduler_Core::save_booking([
    'client_name'    => 'Late Client',
    'package_id'     => $pkg_id, // 60 mins
    'date'           => $next_monday,
    'start_time'     => '16:30',
    'payment_status' => 'unpaid',
]);
assert(is_wp_error($too_late_res), "Booking ending after close time must fail");
assert($too_late_res->get_error_code() === 'outside_hours');

// Monday opens at 08:00. Session starting at 07:00 must fail.
$too_early_res = Training_Scheduler_Core::save_booking([
    'client_name'    => 'Early Client',
    'package_id'     => $pkg_id,
    'date'           => $next_monday,
    'start_time'     => '07:00',
    'payment_status' => 'unpaid',
]);
assert(is_wp_error($too_early_res), "Booking starting before open time must fail");
assert($too_early_res->get_error_code() === 'outside_hours');
echo "PASSED\n";

// Test 6: Valid Booking with Automatic End Time Calculation & Payment Tracking
echo "Test 6: Valid Booking with Payment Tracking & Auto End Time... ";
$booking_id_1 = Training_Scheduler_Core::save_booking([
    'client_name'    => 'Thabo Mokoena',
    'client_phone'   => '082 111 2233',
    'client_email'   => 'thabo@example.co.za',
    'package_id'     => $pkg_id, // 60 mins
    'date'           => $next_monday,
    'start_time'     => '09:00',
    'status'         => 'confirmed',
    'payment_status' => 'paid',
    'payment_method' => 'card',
    'notes'          => 'Paid via credit card at reception'
]);
assert(!is_wp_error($booking_id_1), "Valid booking must succeed");
assert($booking_id_1 > 0);

$booking_1 = Training_Scheduler_Core::get_booking($booking_id_1);
assert($booking_1['client_name'] === 'Thabo Mokoena');
assert($booking_1['start_time'] === '09:00');
assert($booking_1['end_time'] === '10:00', "End time must be auto-calculated to 10:00 (09:00 + 60m)");
assert($booking_1['payment_status'] === 'paid');
assert($booking_1['payment_method'] === 'card');
echo "PASSED\n";

// Test 7: Double Booking / Overlap Prevention
echo "Test 7: Rejection of Overlapping Booking... ";
// Try to book overlapping session from 09:30 to 10:30 on same date
$overlap_res = Training_Scheduler_Core::save_booking([
    'client_name'    => 'Conflicting Client',
    'package_id'     => $pkg_id,
    'date'           => $next_monday,
    'start_time'     => '09:30',
    'payment_status' => 'unpaid',
]);
assert(is_wp_error($overlap_res), "Overlapping booking must fail");
assert($overlap_res->get_error_code() === 'time_conflict');

// Try exact same time (09:00)
$exact_res = Training_Scheduler_Core::save_booking([
    'client_name'    => 'Exact Conflict Client',
    'package_id'     => $pkg_id,
    'date'           => $next_monday,
    'start_time'     => '09:00',
    'payment_status' => 'unpaid',
]);
assert(is_wp_error($exact_res), "Exact slot conflict must fail");
assert($exact_res->get_error_code() === 'time_conflict');
echo "PASSED\n";

// Test 8: Non-overlapping booking immediately adjacent (10:00 - 11:00) must succeed
echo "Test 8: Adjacent Non-overlapping Booking... ";
$booking_id_2 = Training_Scheduler_Core::save_booking([
    'client_name'    => 'Lindiwe Dlamini',
    'client_phone'   => '083 999 8877',
    'package_id'     => $pkg_id,
    'date'           => $next_monday,
    'start_time'     => '10:00',
    'payment_status' => 'unpaid',
    'payment_method' => 'unpaid',
]);
assert(!is_wp_error($booking_id_2), "Adjacent booking starting at 10:00 must succeed");
$booking_2 = Training_Scheduler_Core::get_booking($booking_id_2);
assert($booking_2['start_time'] === '10:00');
assert($booking_2['end_time'] === '11:00');
assert($booking_2['payment_status'] === 'unpaid');
echo "PASSED\n";

// Test 9: Deleting or Cancelling booking frees up slot
echo "Test 9: Cancelled / Deleted Booking Slot Release... ";
// Update booking 1 to cancelled
Training_Scheduler_Core::save_booking([
    'id'             => $booking_id_1,
    'client_name'    => 'Thabo Mokoena',
    'package_id'     => $pkg_id,
    'date'           => $next_monday,
    'start_time'     => '09:00',
    'status'         => 'cancelled',
    'payment_status' => 'paid',
    'payment_method' => 'card',
]);
// Now booking at 09:00 should succeed since previous session is cancelled
$rebook_res = Training_Scheduler_Core::save_booking([
    'client_name'    => 'New Rebook Client',
    'package_id'     => $pkg_id,
    'date'           => $next_monday,
    'start_time'     => '09:00',
    'payment_status' => 'paid',
    'payment_method' => 'eft',
]);
assert(!is_wp_error($rebook_res), "Rebooking slot after cancellation must succeed");
echo "PASSED\n";

echo "\nALL TRAINING SCHEDULER TESTS COMPLETED AND PASSED!\n";
