<?php
/**
 * ZASK FDA Monitor
 * Monitors FDA warnings and peptide status changes
 *
 * @package ZASK_Age_Gate
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class ZASK_FDA_Monitor {
    
    /**
     * Category 2 peptides (prohibited for compounding)
     */
    private static $prohibited_peptides = array(
        'BPC-157',
        'TB-500',
        'Thymosin Beta-4',
        'AOD-9604',
        'CJC-1295',
        'Ipamorelin',
        'GHK-Cu',
        'Melanotan II',
        'KPV',
        'Selank',
        'Semax',
        'MOTS-c',
    );
    
    /**
     * Initialize FDA monitoring
     */
    public static function init() {
        if (!ZASK_License::has_feature('fda_monitor')) {
            return;
        }
        
        // Check for FDA updates daily
        add_action('zask_fda_check', array(__CLASS__, 'check_fda_updates'));
        
        if (!wp_next_scheduled('zask_fda_check')) {
            wp_schedule_event(time(), 'daily', 'zask_fda_check');
        }
    }
    
    /**
     * Check for FDA updates
     */
    public static function check_fda_updates() {
        // Check FDA RSS feed
        $feed_url = 'https://www.fda.gov/about-fda/contact-fda/stay-informed/rss-feeds/fda-newsroom/rss.xml';
        
        $response = wp_remote_get($feed_url);
        
        if (is_wp_error($response)) {
            return;
        }
        
        $xml = simplexml_load_string(wp_remote_retrieve_body($response));
        
        if (!$xml) {
            return;
        }
        
        foreach ($xml->channel->item as $item) {
            $title = (string) $item->title;
            $link = (string) $item->link;
            $description = (string) $item->description;
            $pubDate = (string) $item->pubDate;
            
            // Check if it's about peptides or compounding
            if (self::is_relevant_alert($title . ' ' . $description)) {
                self::create_alert(array(
                    'alert_type' => 'fda_warning',
                    'alert_title' => $title,
                    'alert_content' => $description,
                    'source_url' => $link,
                ));
            }
        }
    }
    
    /**
     * Check if alert is relevant
     */
    private static function is_relevant_alert($text) {
        $keywords = array('peptide', 'compounding', 'warning letter', 'enforcement', 'semaglutide', 'tirzepatide');
        
        $text = strtolower($text);
        
        foreach ($keywords as $keyword) {
            if (strpos($text, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Create alert
     */
    private static function create_alert($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'zask_fda_alerts';
        
        // Check if alert already exists
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE alert_title = %s",
            $data['alert_title']
        ));
        
        if ($exists) {
            return;
        }
        
        $wpdb->insert($table_name, $data);
        
        // Send email notification to admin
        self::send_admin_notification($data);
    }
    
    /**
     * Send admin notification
     */
    private static function send_admin_notification($alert) {
        $admin_email = get_option('zask_admin_email_alerts', get_option('admin_email'));
        
        $subject = '[ZASK Alert] ' . $alert['alert_title'];
        $message = "A new FDA alert has been detected:\n\n";
        $message .= "Title: " . $alert['alert_title'] . "\n\n";
        $message .= "Content: " . strip_tags($alert['alert_content']) . "\n\n";
        $message .= "Source: " . $alert['source_url'] . "\n\n";
        $message .= "View in admin: " . admin_url('admin.php?page=zask-fda-monitor');
        
        wp_mail($admin_email, $subject, $message);
    }
    
    /**
     * Check if peptide is prohibited
     */
    public static function is_peptide_prohibited($peptide_name) {
        $peptide_name = strtoupper(trim($peptide_name));
        
        foreach (self::$prohibited_peptides as $prohibited) {
            if (strpos($peptide_name, strtoupper($prohibited)) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get all alerts
     */
    public static function get_alerts($limit = 50, $unread_only = false) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'zask_fda_alerts';
        
        $where = $unread_only ? "WHERE is_read = 0" : "";
        
        return $wpdb->get_results("SELECT * FROM $table_name $where ORDER BY created_at DESC LIMIT $limit");
    }
    
    /**
     * Mark alert as read
     */
    public static function mark_as_read($alert_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'zask_fda_alerts';
        
        $wpdb->update(
            $table_name,
            array('is_read' => 1),
            array('id' => $alert_id)
        );
    }
    
    /**
     * Get unread count
     */
    public static function get_unread_count() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'zask_fda_alerts';
        
        return $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE is_read = 0");
    }
}

// Initialize FDA monitoring
ZASK_FDA_Monitor::init();
