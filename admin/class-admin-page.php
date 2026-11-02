<?php
class JRVT_Admin_Page {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu']);
    }

    public function add_menu() {
        add_menu_page(
            'JRVT Settings',
            'JRVT Plugin',
            'manage_options',
            'jrvt-plugin',
            [$this, 'settings_page'],
            'dashicons-admin-generic'
        );
    }

    public function settings_page() {
        echo '<h1>JRVT Plugin Settings</h1>';
    }
}

new JRVT_Admin_Page();