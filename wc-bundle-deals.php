<?php
/**
 * Plugin Name: WooCommerce Bundle Deals
 * Plugin URI: https://example.com/
 * Description: A plugin to create and manage product bundle deals in WooCommerce.
 * Version: 1.0.2
 * Author: Wagner
 * Author URI: https://example.com/
 * Text Domain: wc-bundle-deals
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Define plugin path
define( 'WC_BUNDLE_DEALS_PATH', plugin_dir_path( __FILE__ ) );

// Initialize the plugin
class WC_Bundle_Deals {

    public function __construct() {
        // Defer admin and frontend initialization until WordPress is fully loaded
        add_action( 'plugins_loaded', array( $this, 'init' ) );
    }

    public function init() {
        if ( is_admin() ) {
            // Load admin-specific functionality
            $this->load_admin();
        } else {
            // Load frontend-specific functionality
            $this->load_frontend();
        }
    }

    private function load_admin() {
        // Include admin class only when needed
        require_once WC_BUNDLE_DEALS_PATH . 'includes/class-wc-bundle-deals-admin.php';
        new WC_Bundle_Deals_Admin();
    }

    private function load_frontend() {
        // Include frontend class only when needed
        require_once WC_BUNDLE_DEALS_PATH . 'includes/class-wc-bundle-deals-frontend.php';
        new WC_Bundle_Deals_Frontend();
    }
}

// Instantiate the main class
new WC_Bundle_Deals();
