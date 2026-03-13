<?php
/**
 * Plugin Name: ZASK Age-Gate & Compliance System
 * Plugin URI: https://zask.it/age-gate
 * Description: Advanced age verification and compliance system with geographic restrictions, FDA monitoring, and professional verification for WooCommerce stores selling research chemicals and high-risk products
 * Version: 2.0.1
 * Requires at least: 6.0.0
 * Requires PHP: 7.4
 * Author: ZASK Digital Solutions
 * Author URI: https://zask.it
 * License: GPL v3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: zask-age-gate
 * Domain Path: /languages
 * WC requires at least: 6.0
 * WC tested up to: 9.5
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('ZASK_AGEGATE_VERSION', '2.0.1');
define('ZASK_AGEGATE_DIR', plugin_dir_path(__FILE__));
define('ZASK_AGEGATE_URL', plugin_dir_url(__FILE__));
define('ZASK_AGEGATE_BASENAME', plugin_basename(__FILE__));

// WooCommerce HPOS compatibility
add_action('before_woocommerce_init', function() {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

// Require core files
require_once ZASK_AGEGATE_DIR . 'includes/class-zask-license.php';
require_once ZASK_AGEGATE_DIR . 'includes/class-zask-compliance-engine.php';
require_once ZASK_AGEGATE_DIR . 'includes/class-zask-geo-compliance.php';
require_once ZASK_AGEGATE_DIR . 'includes/class-zask-fda-monitor.php';

/**
 * Initialize the plugin
 */
function zask_agegate_init() {
    // Check for WooCommerce
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'zask_agegate_woocommerce_notice');
        return;
    }
    
    // Initialize license system
    ZASK_License::init();
    
    // Initialize compliance engine
    ZASK_Compliance_Engine::init();
}
add_action('plugins_loaded', 'zask_agegate_init');

/**
 * WooCommerce missing notice
 */
function zask_agegate_woocommerce_notice() {
    ?>
    <div class="notice notice-error">
        <p><?php _e('ZASK Age-Gate requires WooCommerce to be installed and active. However, the plugin will work with any e-commerce system for basic age verification.', 'zask-age-gate'); ?></p>
    </div>
    <?php
}

/**
 * Activation hook
 */
function zask_agegate_activate() {
    // Grant custom capability to administrator
    $admin = get_role('administrator');
    if ($admin) {
        $admin->add_cap('manage_zask_agegate');
    }
    
    ZASK_Compliance_Engine::activate();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'zask_agegate_activate');

/**
 * Deactivation hook
 */
function zask_agegate_deactivate() {
    // Clean up custom capability from all roles
    foreach (wp_roles()->roles as $role_name => $role_info) {
        $role = get_role($role_name);
        if ($role) {
            $role->remove_cap('manage_zask_agegate');
        }
    }
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'zask_agegate_deactivate');

/**
 * Add settings link on plugins page
 */
function zask_agegate_settings_link($links) {
    $settings_link = '<a href="admin.php?page=zask-age-gate">' . __('Settings', 'zask-age-gate') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . ZASK_AGEGATE_BASENAME, 'zask_agegate_settings_link');

/**
 * Load plugin textdomain
 */
function zask_agegate_load_textdomain() {
    load_plugin_textdomain('zask-age-gate', false, dirname(ZASK_AGEGATE_BASENAME) . '/languages');
}
add_action('init', 'zask_agegate_load_textdomain');
