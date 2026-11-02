<?php
/**
 * Plugin Name: JRVT Plugin Boilerplate
 * Plugin URI: https://jrvt.co.za/plugins/jrvt-plugin-boilerplate
 * Description: A developer-friendly starter plugin with GPL + Commercial license support.
 * Version: 1.0.0
 * Author: Johnathan Van Tonder (JRVT Pty Ltd)
 * Author URI: https://jrvt.co.za/
 * Developer: Johnathan Van Tonder
 * Developer URI: https://jrvt.co.za/
 * Text Domain: jrvt
 * Domain Path: /languages
 *
 * Requires at least: 6.0
 * Tested up to: 6.5
 * Requires PHP: 7.4
 *
 * WC requires at least: 8.0
 * WC tested up to: 8.3
 *
 * License: Commercial / GPLv3
 * License URI: https://jrvt.co.za/plugin-license
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'includes/class-plugin-loader.php';
JRVT_Plugin_Loader::init();