<?php
/**
 * Admin Calendar View Template
 */
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap training-scheduler-admin-wrap">
    <div class="training-header">
        <h1>
            <span class="dashicons dashicons-calendar-alt"></span>
            <?php esc_html_e('Training Sessions Calendar', 'training-scheduler'); ?>
        </h1>
        <div class="training-header-actions">
            <button class="button button-primary" id="open-new-session-modal">
                <span class="dashicons dashicons-plus-alt"></span> <?php esc_html_e('Schedule New Session', 'training-scheduler'); ?>
            </button>
        </div>
    </div>

    <!-- Calendar View Container -->
    <div class="training-calendar-card">
        <div class="calendar-toolbar">
            <div class="calendar-nav-buttons">
                <button type="button" class="button" id="cal-prev">&laquo; <?php esc_html_e('Prev', 'training-scheduler'); ?></button>
                <button type="button" class="button" id="cal-today"><?php esc_html_e('Today', 'training-scheduler'); ?></button>
                <button type="button" class="button" id="cal-next"><?php esc_html_e('Next', 'training-scheduler'); ?> &raquo;</button>
            </div>
            <h2 id="calendar-title" class="calendar-current-title"></h2>
            <div class="calendar-view-toggles">
                <span class="legend-badge confirmed"><?php esc_html_e('Confirmed', 'training-scheduler'); ?></span>
                <span class="legend-badge pending"><?php esc_html_e('Pending', 'training-scheduler'); ?></span>
                <span class="legend-badge completed"><?php esc_html_e('Completed', 'training-scheduler'); ?></span>
                <span class="legend-badge cancelled"><?php esc_html_e('Cancelled', 'training-scheduler'); ?></span>
            </div>
        </div>

        <div id="training-admin-calendar-root" class="training-calendar-grid-wrapper">
            <!-- Rendered by calendar.js -->
        </div>
    </div>
</div>

<!-- Modal for Creating/Editing Session -->
<div id="session-modal" class="training-modal" style="display:none;">
    <div class="training-modal-content">
        <div class="training-modal-header">
            <h3 id="session-modal-title"><?php esc_html_e('Schedule Training Session', 'training-scheduler'); ?></h3>
            <button type="button" class="close-modal">&times;</button>
        </div>
        <form id="session-form">
            <input type="hidden" name="id" id="session-id" value="">

            <div id="modal-error-notice" class="notice notice-error inline" style="display:none; margin: 0 0 15px 0; padding: 10px;">
                <p id="modal-error-text"></p>
            </div>

            <div class="form-row">
                <div class="form-group half">
                    <label for="session-client-name"><?php esc_html_e('Client Name *', 'training-scheduler'); ?></label>
                    <input type="text" name="client_name" id="session-client-name" required class="regular-text">
                </div>
                <div class="form-group half">
                    <label for="session-client-phone"><?php esc_html_e('Phone Number', 'training-scheduler'); ?></label>
                    <input type="text" name="client_phone" id="session-client-phone" class="regular-text">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group half">
                    <label for="session-client-email"><?php esc_html_e('Client Email', 'training-scheduler'); ?></label>
                    <input type="email" name="client_email" id="session-client-email" class="regular-text">
                </div>
                <div class="form-group half">
                    <label for="session-package"><?php esc_html_e('Training Package *', 'training-scheduler'); ?></label>
                    <select name="package_id" id="session-package" class="regular-text">
                        <option value="0" data-duration="60"><?php esc_html_e('Custom Session (60 mins)', 'training-scheduler'); ?></option>
                        <?php foreach ($packages as $pkg): ?>
                            <option value="<?php echo esc_attr($pkg['id']); ?>" data-duration="<?php echo esc_attr($pkg['duration_minutes']); ?>" data-price="<?php echo esc_attr($pkg['price']); ?>">
                                <?php echo esc_html($pkg['title']); ?> (<?php echo esc_html($pkg['duration_minutes']); ?> mins - R<?php echo esc_html($pkg['price']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group half">
                    <label for="session-date"><?php esc_html_e('Session Date *', 'training-scheduler'); ?></label>
                    <input type="date" name="date" id="session-date" required class="regular-text">
                </div>
                <div class="form-group half">
                    <label for="session-start-time"><?php esc_html_e('Start Time *', 'training-scheduler'); ?></label>
                    <input type="time" name="start_time" id="session-start-time" required class="regular-text" step="900">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group half">
                    <label><?php esc_html_e('Session Duration & Calculated End Time', 'training-scheduler'); ?></label>
                    <div id="session-duration-calc" class="calculated-time-badge">
                        <span id="calc-duration-text">60 mins</span> &rarr; <span id="calc-end-time-text">--:--</span>
                    </div>
                </div>
                <div class="form-group half">
                    <label for="session-status"><?php esc_html_e('Session Status', 'training-scheduler'); ?></label>
                    <select name="status" id="session-status" class="regular-text">
                        <option value="confirmed"><?php esc_html_e('Confirmed', 'training-scheduler'); ?></option>
                        <option value="pending"><?php esc_html_e('Pending', 'training-scheduler'); ?></option>
                        <option value="completed"><?php esc_html_e('Completed', 'training-scheduler'); ?></option>
                        <option value="cancelled"><?php esc_html_e('Cancelled', 'training-scheduler'); ?></option>
                    </select>
                </div>
            </div>

            <!-- Payment Tracking -->
            <div class="form-row">
                <div class="form-group half">
                    <label for="session-payment-status"><?php esc_html_e('Payment Status *', 'training-scheduler'); ?></label>
                    <select name="payment_status" id="session-payment-status" class="regular-text">
                        <option value="unpaid"><?php esc_html_e('Unpaid', 'training-scheduler'); ?></option>
                        <option value="paid"><?php esc_html_e('Paid', 'training-scheduler'); ?></option>
                    </select>
                </div>
                <div class="form-group half">
                    <label for="session-payment-method"><?php esc_html_e('Payment Method', 'training-scheduler'); ?></label>
                    <select name="payment_method" id="session-payment-method" class="regular-text">
                        <option value="unpaid"><?php esc_html_e('-- Not Paid Yet --', 'training-scheduler'); ?></option>
                        <option value="cash"><?php esc_html_e('Cash', 'training-scheduler'); ?></option>
                        <option value="card"><?php esc_html_e('Card / POS', 'training-scheduler'); ?></option>
                        <option value="eft"><?php esc_html_e('EFT / Bank Transfer', 'training-scheduler'); ?></option>
                        <option value="other"><?php esc_html_e('Other', 'training-scheduler'); ?></option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="session-notes"><?php esc_html_e('Session Notes', 'training-scheduler'); ?></label>
                <input type="text" name="notes" id="session-notes" class="regular-text" placeholder="e.g. Legs & Cardio, first trial session">
            </div>

            <div class="training-modal-footer">
                <button type="button" class="button button-link-delete" id="delete-session-btn" style="display:none; color:#a00;"><?php esc_html_e('Delete Session', 'training-scheduler'); ?></button>
                <div style="flex:1;"></div>
                <button type="button" class="button close-modal"><?php esc_html_e('Cancel', 'training-scheduler'); ?></button>
                <button type="submit" class="button button-primary" id="save-session-btn"><?php esc_html_e('Save Session', 'training-scheduler'); ?></button>
            </div>
        </form>
    </div>
</div>
