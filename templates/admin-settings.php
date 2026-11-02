<?php
/**
 * Admin Business Info and Hours Settings Template
 */
if (!defined('ABSPATH')) {
    exit;
}

$days = [
    'monday'    => __('Monday', 'training-scheduler'),
    'tuesday'   => __('Tuesday', 'training-scheduler'),
    'wednesday' => __('Wednesday', 'training-scheduler'),
    'thursday'  => __('Thursday', 'training-scheduler'),
    'friday'    => __('Friday', 'training-scheduler'),
    'saturday'  => __('Saturday', 'training-scheduler'),
    'sunday'    => __('Sunday', 'training-scheduler'),
];
?>
<div class="wrap training-scheduler-admin-wrap">
    <div class="training-header">
        <h1>
            <span class="dashicons dashicons-admin-settings"></span>
            <?php esc_html_e('Business Info & Operating Hours', 'training-scheduler'); ?>
        </h1>
    </div>

    <?php if (isset($_GET['updated'])): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e('Business settings saved successfully!', 'training-scheduler'); ?></p>
        </div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="training_save_settings">
        <?php wp_nonce_field('training_save_settings_nonce'); ?>

        <div class="settings-grid">
            <!-- Business Info Card -->
            <div class="settings-card">
                <h2><?php esc_html_e('Business & Contact Information', 'training-scheduler'); ?></h2>
                <p class="description"><?php esc_html_e('These details are displayed on public booking forms, headers, and notifications.', 'training-scheduler'); ?></p>

                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="biz_name"><?php esc_html_e('Business Name', 'training-scheduler'); ?></label></th>
                        <td>
                            <input type="text" id="biz_name" name="business_info[business_name]" value="<?php echo esc_attr($business_info['business_name'] ?? ''); ?>" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="biz_email"><?php esc_html_e('Business Email', 'training-scheduler'); ?></label></th>
                        <td>
                            <input type="email" id="biz_email" name="business_info[email]" value="<?php echo esc_attr($business_info['email'] ?? ''); ?>" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="biz_phone"><?php esc_html_e('Phone Number', 'training-scheduler'); ?></label></th>
                        <td>
                            <input type="text" id="biz_phone" name="business_info[phone]" value="<?php echo esc_attr($business_info['phone'] ?? ''); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="biz_address"><?php esc_html_e('Studio Address / Location', 'training-scheduler'); ?></label></th>
                        <td>
                            <textarea id="biz_address" name="business_info[address]" rows="3" class="regular-text"><?php echo esc_textarea($business_info['address'] ?? ''); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="biz_currency"><?php esc_html_e('Currency Symbol', 'training-scheduler'); ?></label></th>
                        <td>
                            <input type="text" id="biz_currency" name="business_info[currency]" value="<?php echo esc_attr($business_info['currency'] ?? '$'); ?>" class="small-text">
                            <span class="description"><?php esc_html_e('e.g. $, €, £, R', 'training-scheduler'); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="biz_notice"><?php esc_html_e('Min. Advance Notice (hours)', 'training-scheduler'); ?></label></th>
                        <td>
                            <input type="number" id="biz_notice" name="business_info[booking_notice]" value="<?php echo esc_attr($business_info['booking_notice'] ?? 2); ?>" min="0" class="small-text">
                            <span class="description"><?php esc_html_e('How many hours ahead must a client book?', 'training-scheduler'); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="biz_about"><?php esc_html_e('About / Bio', 'training-scheduler'); ?></label></th>
                        <td>
                            <textarea id="biz_about" name="business_info[about]" rows="4" class="large-text"><?php echo esc_textarea($business_info['about'] ?? ''); ?></textarea>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Business Hours Card -->
            <div class="settings-card">
                <h2><?php esc_html_e('Weekly Operating Hours', 'training-scheduler'); ?></h2>
                <p class="description"><?php esc_html_e('Define your available opening and closing hours for each day of the week.', 'training-scheduler'); ?></p>

                <table class="hours-table widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Day', 'training-scheduler'); ?></th>
                            <th><?php esc_html_e('Open Status', 'training-scheduler'); ?></th>
                            <th><?php esc_html_e('Opening Time', 'training-scheduler'); ?></th>
                            <th><?php esc_html_e('Closing Time', 'training-scheduler'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($days as $day_key => $day_label):
                            $h = $business_hours[$day_key] ?? ['enabled' => false, 'open' => '08:00', 'close' => '18:00'];
                            $is_enabled = !empty($h['enabled']);
                        ?>
                            <tr>
                                <td><strong><?php echo esc_html($day_label); ?></strong></td>
                                <td>
                                    <label class="switch">
                                        <input type="checkbox" name="business_hours[<?php echo esc_attr($day_key); ?>][enabled]" value="1" <?php checked($is_enabled, true); ?> class="day-toggle" data-day="<?php echo esc_attr($day_key); ?>">
                                        <span class="slider"></span>
                                    </label>
                                    <span class="toggle-status-label"><?php echo $is_enabled ? esc_html__('Open', 'training-scheduler') : esc_html__('Closed', 'training-scheduler'); ?></span>
                                </td>
                                <td>
                                    <input type="time" name="business_hours[<?php echo esc_attr($day_key); ?>][open]" value="<?php echo esc_attr($h['open'] ?? '08:00'); ?>" class="time-input" <?php echo !$is_enabled ? 'disabled' : ''; ?>>
                                </td>
                                <td>
                                    <input type="time" name="business_hours[<?php echo esc_attr($day_key); ?>][close]" value="<?php echo esc_attr($h['close'] ?? '18:00'); ?>" class="time-input" <?php echo !$is_enabled ? 'disabled' : ''; ?>>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <p class="submit">
            <button type="submit" class="button button-primary button-large">
                <span class="dashicons dashicons-saved"></span> <?php esc_html_e('Save Business Settings', 'training-scheduler'); ?>
            </button>
        </p>
    </form>
</div>
