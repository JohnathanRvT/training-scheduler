<?php
class JRVT_Public_Display {
    public function __construct() {
        add_shortcode('jrvt_hello', [$this, 'render_shortcode']);
    }

    public function render_shortcode() {
        return '<p>Hello from JRVT Plugin!</p>';
    }
}

new JRVT_Public_Display();