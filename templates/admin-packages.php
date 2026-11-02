<?php
/**
 * Admin Packages Management Template
 */
if (!defined('ABSPATH')) {
    exit;
}
$currency = $business_info['currency'] ?? 'R';
?>
<div class="wrap training-scheduler-admin-wrap">
    <div class="training-header">
        <h1>
            <span class="dashicons dashicons-products"></span>
            <?php esc_html_e('Training Packages', 'training-scheduler'); ?>
        </h1>
        <div class="training-header-actions">
            <button class="button button-primary" id="open-new-package-modal">
                <span class="dashicons dashicons-plus-alt"></span> <?php esc_html_e('Create New Package', 'training-scheduler'); ?>
            </button>
        </div>
    </div>

    <p class="description">
        <?php esc_html_e('Create and manage training programs with duration and pricing in Rands for single training sessions.', 'training-scheduler'); ?>
    </p>

    <div class="packages-grid" id="packages-grid">
        <?php if (empty($packages)): ?>
            <div class="notice notice-info inline" style="grid-column: 1 / -1; padding: 20px;">
                <p><?php esc_html_e('No packages created yet. Click "Create New Package" to add your training options.', 'training-scheduler'); ?></p>
            </div>
        <?php else: ?>
            <?php foreach ($packages as $pkg): ?>
                <div class="package-card" data-id="<?php echo esc_attr($pkg['id']); ?>">
                    <h3 class="package-title"><?php echo esc_html($pkg['title']); ?></h3>
                    <div class="package-price">
                        <span class="currency"><?php echo esc_html($currency); ?></span>
                        <span class="amount"><?php echo esc_html(number_format($pkg['price'], 2)); ?></span>
                    </div>
                    <div class="package-meta-details">
                        <span><span class="dashicons dashicons-clock"></span> <?php echo esc_html($pkg['duration_minutes']); ?> mins / session</span>
                    </div>
                    <?php if (!empty($pkg['description'])): ?>
                        <p class="package-description"><?php echo esc_html($pkg['description']); ?></p>
                    <?php endif; ?>
                    <div class="package-card-actions">
                        <button type="button" class="button button-secondary edit-package-btn"
                            data-id="<?php echo esc_attr($pkg['id']); ?>"
                            data-title="<?php echo esc_attr($pkg['title']); ?>"
                            data-price="<?php echo esc_attr($pkg['price']); ?>"
                            data-duration="<?php echo esc_attr($pkg['duration_minutes']); ?>"
                            data-desc="<?php echo esc_attr($pkg['description']); ?>">
                            <?php esc_html_e('Edit', 'training-scheduler'); ?>
                        </button>
                        <button type="button" class="button button-link-delete delete-package-btn" data-id="<?php echo esc_attr($pkg['id']); ?>" style="color:#a00;">
                            <?php esc_html_e('Delete', 'training-scheduler'); ?>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal for Creating/Editing Package -->
<div id="package-modal" class="training-modal" style="display:none;">
    <div class="training-modal-content">
        <div class="training-modal-header">
            <h3 id="package-modal-title"><?php esc_html_e('Create Training Package', 'training-scheduler'); ?></h3>
            <button type="button" class="close-modal">&times;</button>
        </div>
        <form id="package-form">
            <input type="hidden" name="id" id="pkg-id" value="">
            <div class="form-row">
                <div class="form-group">
                    <label for="pkg-title"><?php esc_html_e('Package Title *', 'training-scheduler'); ?></label>
                    <input type="text" name="title" id="pkg-title" required class="regular-text" placeholder="e.g. 1-on-1 Personal Training">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group half">
                    <label for="pkg-price"><?php esc_html_e('Price (' . $currency . ') *', 'training-scheduler'); ?></label>
                    <input type="number" step="0.01" name="price" id="pkg-price" required class="regular-text" value="0.00">
                </div>
                <div class="form-group half">
                    <label for="pkg-duration"><?php esc_html_e('Session Duration (mins) *', 'training-scheduler'); ?></label>
                    <input type="number" name="duration_minutes" id="pkg-duration" step="5" min="15" required class="regular-text" value="60">
                </div>
            </div>

            <div class="form-group">
                <label for="pkg-desc"><?php esc_html_e('Description / Focus', 'training-scheduler'); ?></label>
                <textarea name="description" id="pkg-desc" rows="3" class="large-text" placeholder="e.g. 1-on-1 personal session focusing on hypertrophy and endurance."></textarea>
            </div>

            <div class="training-modal-footer">
                <button type="button" class="button close-modal"><?php esc_html_e('Cancel', 'training-scheduler'); ?></button>
                <button type="submit" class="button button-primary" id="save-package-btn"><?php esc_html_e('Save Package', 'training-scheduler'); ?></button>
            </div>
        </form>
    </div>
</div>
