<?php
/**
 * Plugin Name: Training Session Scheduler
 * Plugin URI: https://jrvt.co.za/plugins/training-scheduler
 * Description: A complete training session scheduler with package management, business operating hours & info, and interactive calendar views.
 * Version: 1.0.0
 * Author: Johnathan Van Tonder (JRVT Pty Ltd)
 * Author URI: https://jrvt.co.za/
 * Developer: Johnathan Van Tonder
 * Developer URI: https://jrvt.co.za/
 * Text Domain: training-scheduler
 * Domain Path: /languages
 *
 * Requires at least: 6.0
 * Tested up to: 6.5
 * Requires PHP: 7.4
 *
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */

if (!defined('ABSPATH')) {
    exit;
}

define('TRAINING_SCHEDULER_VERSION', '1.0.0');
define('TRAINING_SCHEDULER_PATH', plugin_dir_path(__FILE__));
define('TRAINING_SCHEDULER_URL', plugin_dir_url(__FILE__));

require_once TRAINING_SCHEDULER_PATH . 'includes/class-plugin-loader.php';
JRVT_Plugin_Loader::init();
