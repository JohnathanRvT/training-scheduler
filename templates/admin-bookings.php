<?php
/**
 * Admin Bookings List Template
 */
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap training-scheduler-admin-wrap">
    <div class="training-header">
        <h1>
            <span class="dashicons dashicons-list-view"></span>
            <?php esc_html_e('All Scheduled Sessions & Bookings', 'training-scheduler'); ?>
        </h1>
        <div class="training-header-actions">
            <button class="button button-primary" id="open-new-session-modal">
                <span class="dashicons dashicons-plus-alt"></span> <?php esc_html_e('Add New Booking', 'training-scheduler'); ?>
            </button>
        </div>
    </div>

    <div class="tablenav top">
        <div class="alignleft actions">
            <select id="filter-status">
                <option value=""><?php esc_html_e('All Session Statuses', 'training-scheduler'); ?></option>
                <option value="confirmed"><?php esc_html_e('Confirmed', 'training-scheduler'); ?></option>
                <option value="pending"><?php esc_html_e('Pending', 'training-scheduler'); ?></option>
                <option value="completed"><?php esc_html_e('Completed', 'training-scheduler'); ?></option>
                <option value="cancelled"><?php esc_html_e('Cancelled', 'training-scheduler'); ?></option>
            </select>
            <select id="filter-payment">
                <option value=""><?php esc_html_e('All Payment Statuses', 'training-scheduler'); ?></option>
                <option value="paid"><?php esc_html_e('Paid', 'training-scheduler'); ?></option>
                <option value="unpaid"><?php esc_html_e('Unpaid', 'training-scheduler'); ?></option>
            </select>
            <input type="text" id="search-bookings" placeholder="<?php esc_attr_e('Search client, package, phone...', 'training-scheduler'); ?>" class="regular-text" style="width: 240px;">
        </div>
    </div>

    <table class="wp-list-table widefat fixed striped table-view-list bookings-table">
        <thead>
            <tr>
                <th scope="col" style="width: 50px;"><?php esc_html_e('ID', 'training-scheduler'); ?></th>
                <th scope="col"><?php esc_html_e('Client', 'training-scheduler'); ?></th>
                <th scope="col"><?php esc_html_e('Contact', 'training-scheduler'); ?></th>
                <th scope="col"><?php esc_html_e('Package / Duration', 'training-scheduler'); ?></th>
                <th scope="col"><?php esc_html_e('Date & Time', 'training-scheduler'); ?></th>
                <th scope="col" style="width: 130px;"><?php esc_html_e('Payment', 'training-scheduler'); ?></th>
                <th scope="col" style="width: 110px;"><?php esc_html_e('Status', 'training-scheduler'); ?></th>
                <th scope="col" style="width: 140px;"><?php esc_html_e('Actions', 'training-scheduler'); ?></th>
            </tr>
        </thead>
        <tbody id="bookings-table-body">
            <?php if (empty($bookings)): ?>
                <tr class="no-items">
                    <td colspan="8" style="text-align:center; padding: 25px;"><?php esc_html_e('No bookings found. Click "Add New Booking" to schedule a training session.', 'training-scheduler'); ?></td>
                </tr>
            <?php else: ?>
                <?php foreach ($bookings as $b): ?>
                    <tr data-id="<?php echo esc_attr($b['id']); ?>" data-status="<?php echo esc_attr($b['status']); ?>" data-payment="<?php echo esc_attr($b['payment_status']); ?>" data-search="<?php echo esc_attr(strtolower($b['client_name'] . ' ' . $b['client_email'] . ' ' . $b['client_phone'] . ' ' . $b['package_name'])); ?>">
                        <td><strong>#<?php echo esc_html($b['id']); ?></strong></td>
                        <td>
                            <strong><?php echo esc_html($b['client_name']); ?></strong>
                            <?php if (!empty($b['notes'])): ?>
                                <div class="booking-notes-preview"><small><em><?php echo esc_html($b['notes']); ?></em></small></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($b['client_phone'])): ?>
                                <div><strong><?php echo esc_html($b['client_phone']); ?></strong></div>
                            <?php endif; ?>
                            <?php if (!empty($b['client_email'])): ?>
                                <small><a href="mailto:<?php echo esc_attr($b['client_email']); ?>"><?php echo esc_html($b['client_email']); ?></a></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="package-pill"><?php echo esc_html($b['package_name']); ?></span>
                            <div><small class="text-muted"><?php echo esc_html($b['duration']); ?> mins</small></div>
                        </td>
                        <td>
                            <strong><?php echo esc_html($b['date']); ?></strong><br>
                            <span class="time-range"><?php echo esc_html($b['start_time']); ?> &ndash; <?php echo esc_html($b['end_time']); ?></span>
                        </td>
                        <td>
                            <span class="payment-badge <?php echo esc_attr($b['payment_status']); ?>">
                                <?php echo esc_html(strtoupper($b['payment_status'])); ?>
                            </span>
                            <?php if ($b['payment_status'] === 'paid' && !empty($b['payment_method']) && $b['payment_method'] !== 'unpaid'): ?>
                                <div><small class="text-muted"><?php echo esc_html(strtoupper($b['payment_method'])); ?></small></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="status-badge <?php echo esc_attr($b['status']); ?>">
                                <?php echo esc_html(ucfirst($b['status'])); ?>
                            </span>
                        </td>
                        <td>
                            <button type="button" class="button button-small edit-booking-btn"
                                data-id="<?php echo esc_attr($b['id']); ?>"
                                data-name="<?php echo esc_attr($b['client_name']); ?>"
                                data-email="<?php echo esc_attr($b['client_email']); ?>"
                                data-phone="<?php echo esc_attr($b['client_phone']); ?>"
                                data-package="<?php echo esc_attr($b['package_id']); ?>"
                                data-date="<?php echo esc_attr($b['date']); ?>"
                                data-start="<?php echo esc_attr($b['start_time']); ?>"
                                data-duration="<?php echo esc_attr($b['duration']); ?>"
                                data-status="<?php echo esc_attr($b['status']); ?>"
                                data-payment-status="<?php echo esc_attr($b['payment_status']); ?>"
                                data-payment-method="<?php echo esc_attr($b['payment_method']); ?>"
                                data-notes="<?php echo esc_attr($b['notes']); ?>">
                                <?php esc_html_e('Edit', 'training-scheduler'); ?>
                            </button>
                            <button type="button" class="button button-small button-link-delete delete-booking-btn" data-id="<?php echo esc_attr($b['id']); ?>" style="color:#a00;">
                                <?php esc_html_e('Delete', 'training-scheduler'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal for Creating/Editing Session -->
<div id="session-modal" class="training-modal" style="display:none;">
    <div class="training-modal-content">
        <div class="training-modal-header">
            <h3 id="session-modal-title"><?php esc_html_e('Manage Training Session', 'training-scheduler'); ?></h3>
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
                <input type="text" name="notes" id="session-notes" class="regular-text" placeholder="e.g. Legs & Cardio">
            </div>

            <div class="training-modal-footer">
                <button type="button" class="button button-link-delete" id="delete-session-btn" style="display:none; color:#a00;"><?php esc_html_e('Delete Booking', 'training-scheduler'); ?></button>
                <div style="flex:1;"></div>
                <button type="button" class="button close-modal"><?php esc_html_e('Cancel', 'training-scheduler'); ?></button>
                <button type="submit" class="button button-primary" id="save-session-btn"><?php esc_html_e('Save Booking', 'training-scheduler'); ?></button>
            </div>
        </form>
    </div>
</div>
