<?php
/**
 * ZASK License Management
 * Handles license verification with zask.it server
 *
 * @package ZASK_Age_Gate
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class ZASK_License {
    
    const LICENSE_SERVER = 'https://zask.it/wp-json/zask-license/v1/';
    
    private static $instance = null;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize license system
     */
    public static function init() {
        $instance = self::get_instance();
        
        // Check license on admin pages
        add_action('admin_init', array($instance, 'check_license_status'));
        
        // AJAX handlers
        add_action('wp_ajax_zask_activate_license', array($instance, 'ajax_activate_license'));
        add_action('wp_ajax_zask_deactivate_license', array($instance, 'ajax_deactivate_license'));
        add_action('wp_ajax_zask_check_license', array($instance, 'ajax_check_license'));
    }
    
    /**
     * Activate license
     */
    public function ajax_activate_license() {
        check_ajax_referer('zask_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_zask_agegate')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'zask-age-gate')));
        }
        
        $license_key = sanitize_text_field($_POST['license_key']);
        $domain = parse_url(get_site_url(), PHP_URL_HOST);
        
        // Send activation request to license server
        $response = wp_remote_post(self::LICENSE_SERVER . 'activate', array(
            'body' => array(
                'license_key' => $license_key,
                'domain' => $domain,
                'product' => 'zask-age-gate',
            ),
            'timeout' => 15,
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => $response->get_error_message()));
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($body['success']) {
            update_option('zask_license_key', $license_key);
            update_option('zask_license_status', 'active');
            update_option('zask_license_expires', $body['expires']);
            update_option('zask_license_tier', $body['tier']);
            
            wp_send_json_success(array(
                'message' => __('License activated successfully!', 'zask-age-gate'),
                'data' => $body
            ));
        } else {
            wp_send_json_error(array('message' => $body['message']));
        }
    }
    
    /**
     * Deactivate license
     */
    public function ajax_deactivate_license() {
        check_ajax_referer('zask_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_zask_agegate')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'zask-age-gate')));
        }
        
        $license_key = get_option('zask_license_key');
        $domain = parse_url(get_site_url(), PHP_URL_HOST);
        
        // Send deactivation request
        $response = wp_remote_post(self::LICENSE_SERVER . 'deactivate', array(
            'body' => array(
                'license_key' => $license_key,
                'domain' => $domain,
            ),
            'timeout' => 15,
        ));
        
        // Clear local license data
        delete_option('zask_license_key');
        delete_option('zask_license_status');
        delete_option('zask_license_expires');
        delete_option('zask_license_tier');
        
        wp_send_json_success(array('message' => __('License deactivated successfully!', 'zask-age-gate')));
    }
    
    /**
     * Check license status
     */
    public function check_license_status() {
        $license_key = get_option('zask_license_key');
        
        if (empty($license_key)) {
            return;
        }
        
        // Check license once per day
        $last_check = get_transient('zask_license_last_check');
        if ($last_check) {
            return;
        }
        
        $domain = parse_url(get_site_url(), PHP_URL_HOST);
        
        $response = wp_remote_post(self::LICENSE_SERVER . 'check', array(
            'body' => array(
                'license_key' => $license_key,
                'domain' => $domain,
            ),
            'timeout' => 10,
        ));
        
        if (!is_wp_error($response)) {
            $body = json_decode(wp_remote_retrieve_body($response), true);
            
            if ($body['success']) {
                update_option('zask_license_status', $body['status']);
                update_option('zask_license_expires', $body['expires']);
                update_option('zask_license_tier', $body['tier']);
            } else {
                update_option('zask_license_status', 'invalid');
            }
        }
        
        // Set transient for 24 hours
        set_transient('zask_license_last_check', time(), DAY_IN_SECONDS);
    }
    
    /**
     * AJAX: Check license
     */
    public function ajax_check_license() {
        check_ajax_referer('zask_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_zask_agegate')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'zask-age-gate')));
        }
        
        delete_transient('zask_license_last_check');
        $this->check_license_status();
        
        wp_send_json_success(array(
            'status' => get_option('zask_license_status', 'inactive'),
            'expires' => get_option('zask_license_expires'),
            'tier' => get_option('zask_license_tier'),
        ));
    }
    
    /**
     * Get license status
     */
    public static function get_license_status() {
        return get_option('zask_license_status', 'inactive');
    }
    
    /**
     * Check if license is active
     */
    public static function is_active() {
        return self::get_license_status() === 'active';
    }
    
    /**
     * Get license tier
     */
    public static function get_tier() {
        return get_option('zask_license_tier', 'starter');
    }
    
    /**
     * Check if feature is available in current tier
     */
    public static function has_feature($feature) {
        $tier = self::get_tier();
        
        $features = array(
            'starter' => array('basic_gate', 'age_verification', 'terms_agreement'),
            'professional' => array('basic_gate', 'age_verification', 'terms_agreement', 'geo_compliance', 'fda_monitor', 'coa_management'),
            'enterprise' => array('basic_gate', 'age_verification', 'terms_agreement', 'geo_compliance', 'fda_monitor', 'coa_management', 'professional_verification', 'api_access', 'white_label'),
        );
        
        return in_array($feature, $features[$tier] ?? array());
    }
}
