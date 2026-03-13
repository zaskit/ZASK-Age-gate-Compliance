<?php
/**
 * ZASK Geographic Compliance
 * Handles state-specific age requirements and restrictions
 *
 * @package ZASK_Age_Gate
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class ZASK_Geo_Compliance {
    
    /**
     * State-specific age requirements
     */
    private static $state_rules = array(
        'NY' => array('min_age' => 18, 'requires_parental' => false, 'restricted_products' => array('muscle', 'weight')),
        'NJ' => array('min_age' => 18, 'requires_parental' => true, 'restricted_products' => array('performance')),
        'CA' => array('min_age' => 18, 'requires_parental' => false, 'restricted_products' => array('muscle', 'weight')),
        'MA' => array('min_age' => 18, 'requires_parental' => false, 'restricted_products' => array('muscle')),
        'IL' => array('min_age' => 18, 'requires_parental' => false, 'restricted_products' => array('muscle', 'weight')),
        'VA' => array('min_age' => 18, 'requires_parental' => false, 'restricted_products' => array('muscle')),
        'TX' => array('min_age' => 18, 'requires_parental' => false, 'restricted_products' => array('muscle')),
        'NH' => array('min_age' => 18, 'requires_parental' => false, 'restricted_products' => array('muscle')),
    );
    
    /**
     * Get user's location from IP
     */
    public static function get_user_location() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        
        // Check if we have cached location
        $cached = get_transient('zask_geo_' . md5($ip));
        if ($cached) {
            return $cached;
        }
        
        // Use free IP geolocation API
        $response = wp_remote_get('http://ip-api.com/json/' . $ip);
        
        if (is_wp_error($response)) {
            return array('country' => 'US', 'state' => '', 'city' => '');
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        $location = array(
            'country' => $data['countryCode'] ?? 'US',
            'state' => $data['region'] ?? '',
            'city' => $data['city'] ?? '',
        );
        
        // Cache for 1 hour
        set_transient('zask_geo_' . md5($ip), $location, HOUR_IN_SECONDS);
        
        return $location;
    }
    
    /**
     * Get minimum age for user's location
     */
    public static function get_minimum_age() {
        if (!ZASK_License::has_feature('geo_compliance')) {
            return get_option('zask_minimum_age', 21);
        }
        
        $location = self::get_user_location();
        $state = $location['state'];
        
        if (isset(self::$state_rules[$state])) {
            return self::$state_rules[$state]['min_age'];
        }
        
        return get_option('zask_minimum_age', 21);
    }
    
    /**
     * Check if product is restricted in user's state
     */
    public static function is_product_restricted($product_id) {
        if (!ZASK_License::has_feature('geo_compliance')) {
            return false;
        }
        
        $location = self::get_user_location();
        $state = $location['state'];
        
        if (!isset(self::$state_rules[$state])) {
            return false;
        }
        
        $product_categories = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'slugs'));
        $restricted = self::$state_rules[$state]['restricted_products'];
        
        foreach ($product_categories as $category) {
            foreach ($restricted as $restricted_term) {
                if (strpos($category, $restricted_term) !== false) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Get state-specific message
     */
    public static function get_state_message() {
        $location = self::get_user_location();
        $state = $location['state'];
        
        if (!isset(self::$state_rules[$state])) {
            return '';
        }
        
        $rules = self::$state_rules[$state];
        
        $message = sprintf(
            __('Due to %s state law, you must be %d or older to purchase certain products.', 'zask-age-gate'),
            $state,
            $rules['min_age']
        );
        
        if ($rules['requires_parental']) {
            $message .= ' ' . __('Minors require parental consent.', 'zask-age-gate');
        }
        
        return $message;
    }
}
