<?php
/**
 * Standalone Unit and Integration Test for Training Scheduler Core Logic
 */

// Mock WordPress environment functions
define('ABSPATH', __DIR__ . '/../');

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
function is_wp_error($thing) { return false; }

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

// Test 1: Business info and operating hours defaults & sanitization
echo "Test 1: Business Info & Hours Sanitize... ";
$raw_info = [
    'business_name' => '  PowerFit Gym  ',
    'email' => 'trainer@example.com',
    'phone' => '+1234567890',
    'address' => '123 Main St',
    'currency' => '$',
    'slot_duration' => '45',
    'booking_notice' => '3'
];
$sanitized_info = Training_Scheduler_Core::sanitize_business_info($raw_info);
assert($sanitized_info['business_name'] === 'PowerFit Gym');
assert($sanitized_info['slot_duration'] === 45);
assert($sanitized_info['booking_notice'] === 3);
update_option(Training_Scheduler_Core::OPTION_BUSINESS_INFO, $sanitized_info);
$retrieved_info = Training_Scheduler_Core::get_business_info();
assert($retrieved_info['business_name'] === 'PowerFit Gym');
echo "PASSED\n";

// Test 2: Training Package CRUD
echo "Test 2: Training Package CRUD... ";
$pkg_id = Training_Scheduler_Core::save_package([
    'title' => 'VIP 10-Session Pack',
    'price' => '450.00',
    'sessions_count' => 10,
    'duration_minutes' => 60,
    'badge' => 'Best Value',
    'description' => 'Personal coaching and weekly check-in',
    'features' => "Full body analysis\nNutrition guide"
]);
assert($pkg_id > 0);

$pkg = Training_Scheduler_Core::get_package($pkg_id);
assert($pkg['title'] === 'VIP 10-Session Pack');
assert($pkg['price'] === 450.0);
assert($pkg['sessions_count'] === 10);
assert($pkg['duration_minutes'] === 60);
assert($pkg['badge'] === 'Best Value');

$all_pkgs = Training_Scheduler_Core::get_packages();
assert(count($all_pkgs) === 1);
echo "PASSED\n";

// Test 3: Booking Creation and Retrieval
echo "Test 3: Booking Creation... ";
$booking_date = date('Y-m-d', strtotime('+5 days')); // safely in the future
$booking_id = Training_Scheduler_Core::save_booking([
    'client_name' => 'Sarah Connor',
    'client_email' => 'sarah@example.com',
    'client_phone' => '555-0101',
    'package_id' => $pkg_id,
    'date' => $booking_date,
    'start_time' => '10:00',
    'end_time' => '11:00',
    'status' => 'confirmed',
    'notes' => 'Strength training focus'
]);
assert($booking_id > 0);

$booking = Training_Scheduler_Core::get_booking($booking_id);
assert($booking['client_name'] === 'Sarah Connor');
assert($booking['package_name'] === 'VIP 10-Session Pack');
assert($booking['date'] === $booking_date);
assert($booking['start_time'] === '10:00');
assert($booking['end_time'] === '11:00');
assert($booking['status'] === 'confirmed');
echo "PASSED\n";

// Test 4: Available Slots Calculation and Overlap Exclusion
echo "Test 4: Available Slots & Conflict Prevention... ";
// Operating hours: Monday to Friday 08:00 - 18:00 (60 min slots)
$hours = Training_Scheduler_Core::get_default_business_hours();
update_option(Training_Scheduler_Core::OPTION_BUSINESS_HOURS, $hours);

// Determine the day of week for our booking date
$dow = strtolower(date('l', strtotime($booking_date)));
// If it's Sunday (closed in defaults), enable it for test
if (!$hours[$dow]['enabled']) {
    $hours[$dow] = ['enabled' => true, 'open' => '08:00', 'close' => '18:00'];
    update_option(Training_Scheduler_Core::OPTION_BUSINESS_HOURS, $hours);
}

$slots = Training_Scheduler_Core::get_available_slots($booking_date, 60);
assert(count($slots) > 0);

// Ensure 10:00 - 11:00 is NOT in available slots because Sarah Connor has booked it
$has_10am_slot = false;
foreach ($slots as $slot) {
    if ($slot['start'] === '10:00') {
        $has_10am_slot = true;
    }
}
assert($has_10am_slot === false, "Slot 10:00 must be unavailable due to booking conflict!");

// Ensure 09:00 - 10:00 and 11:00 - 12:00 ARE available
$has_9am = false;
$has_11am = false;
foreach ($slots as $slot) {
    if ($slot['start'] === '09:00') $has_9am = true;
    if ($slot['start'] === '11:00') $has_11am = true;
}
assert($has_9am === true, "09:00 slot should be free");
assert($has_11am === true, "11:00 slot should be free");
echo "PASSED\n";

// Test 5: Booking Deletion
echo "Test 5: Booking Deletion... ";
$del_res = Training_Scheduler_Core::delete_booking($booking_id);
assert($del_res === true);
$deleted_booking = Training_Scheduler_Core::get_booking($booking_id);
assert($deleted_booking === null);

// Now 10:00 slot should be available again
$slots_after_del = Training_Scheduler_Core::get_available_slots($booking_date, 60);
$has_10am_now = false;
foreach ($slots_after_del as $slot) {
    if ($slot['start'] === '10:00') $has_10am_now = true;
}
assert($has_10am_now === true, "10:00 slot should become free after booking deletion");
echo "PASSED\n";

// Test 6: Package Deletion
echo "Test 6: Package Deletion... ";
$del_pkg = Training_Scheduler_Core::delete_package($pkg_id);
assert($del_pkg === true);
assert(Training_Scheduler_Core::get_package($pkg_id) === null);
echo "PASSED\n";

echo "\nALL AUTOMATED TESTS PASSED SUCCESSFULLY!\n";
