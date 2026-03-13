<?php
/**
 * ZASK Compliance Engine
 * Main class handling all compliance and age-gate functionality
 *
 * @package ZASK_Age_Gate
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class ZASK_Compliance_Engine {
    
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
     * Initialize the plugin
     */
    public static function init() {
        $instance = self::get_instance();
        
        // Admin hooks
        add_action('admin_menu', array($instance, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($instance, 'admin_enqueue_scripts'));
        add_action('admin_init', array($instance, 'register_settings'));
        add_action('admin_init', array($instance, 'maybe_upgrade_db'));
        add_action('admin_notices', array($instance, 'license_warning_notice'));
        
        // Frontend hooks
        add_action('wp_enqueue_scripts', array($instance, 'frontend_enqueue_scripts'), 9999);
        add_action('wp_footer', array($instance, 'render_gate_modal'), 1);
        add_action('template_redirect', array($instance, 'check_gate_access'), 1);
        add_filter('body_class', array($instance, 'add_gate_body_class'));
        
        // Block other plugins/WooCommerce from redirecting when gate should show
        add_filter('wp_redirect', array($instance, 'intercept_redirects'), 1, 2);
        add_filter('woocommerce_login_redirect', array($instance, 'force_home_redirect'), 9999);
        add_filter('woocommerce_registration_redirect', array($instance, 'force_home_redirect'), 9999);
        add_filter('login_redirect', array($instance, 'force_home_redirect'), 9999);
        
        // AJAX handlers
        add_action('wp_ajax_zask_verify_age', array($instance, 'ajax_verify_age'));
        add_action('wp_ajax_nopriv_zask_verify_age', array($instance, 'ajax_verify_age'));
        add_action('wp_ajax_zask_login', array($instance, 'ajax_login'));
        add_action('wp_ajax_nopriv_zask_login', array($instance, 'ajax_login'));
        add_action('wp_ajax_zask_register', array($instance, 'ajax_register'));
        add_action('wp_ajax_nopriv_zask_register', array($instance, 'ajax_register'));
        add_action('wp_ajax_zask_send_verification', array($instance, 'ajax_send_verification'));
        add_action('wp_ajax_nopriv_zask_send_verification', array($instance, 'ajax_send_verification'));
        add_action('wp_ajax_zask_verify_code', array($instance, 'ajax_verify_code'));
        add_action('wp_ajax_nopriv_zask_verify_code', array($instance, 'ajax_verify_code'));
        add_action('wp_ajax_zask_reset_password', array($instance, 'ajax_reset_password'));
        add_action('wp_ajax_nopriv_zask_reset_password', array($instance, 'ajax_reset_password'));
        
        // Admin AJAX
        add_action('wp_ajax_zask_save_settings', array($instance, 'ajax_save_settings'));
        add_action('wp_ajax_zask_activate_license', array($instance, 'ajax_activate_license'));
        add_action('wp_ajax_zask_export_csv', array($instance, 'ajax_export_csv'));
        add_action('wp_ajax_zask_delete_record', array($instance, 'ajax_delete_record'));
        add_action('wp_ajax_zask_delete_old_records', array($instance, 'ajax_delete_old_records'));
        add_action('wp_ajax_zask_refresh_users', array($instance, 'ajax_refresh_users'));
        add_action('wp_ajax_zask_refresh_fda', array($instance, 'ajax_refresh_fda'));
        add_action('wp_ajax_zask_add_fda_product', array($instance, 'ajax_add_fda_product'));
        add_action('wp_ajax_zask_remove_fda_product', array($instance, 'ajax_remove_fda_product'));
        add_action('wp_ajax_zask_resolve_fda_alert', array($instance, 'ajax_resolve_fda_alert'));
        add_action('wp_ajax_zask_scan_single_product', array($instance, 'ajax_scan_single_product'));
        
        // Session management - Only for age gate verification
        add_action('clear_auth_cookie', array($instance, 'handle_logout'));
    }
    
    /**
     * Plugin activation
     */
    public static function activate() {
        self::create_tables();
        
        // Set default options
        $defaults = array(
            'zask_gate_enabled' => '1',
            'zask_gate_stage' => 'stage1',
            'zask_gate_display_mode' => 'modal',
            'zask_minimum_age' => '21',
            'zask_require_terms' => '1',
            'zask_require_email_verification' => '0',
            'zask_session_duration' => '120',
            'zask_logout_on_browser_close' => '0',
            'zask_geo_compliance_enabled' => '0',
            'zask_fda_monitor_enabled' => '0',
            'zask_admin_email_alerts' => get_option('admin_email'),
        );
        
        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
        
        // Store DB version for future upgrade checks
        update_option('zask_agegate_db_version', ZASK_AGEGATE_VERSION);
    }
    
    /**
     * Create / update all plugin tables.
     * Uses dbDelta() which safely adds new columns & tables without destroying data.
     * Called on activation AND on admin_init when version mismatch detected.
     *
     * IMPORTANT dbDelta() rules followed:
     *  - NO "IF NOT EXISTS" (dbDelta handles existence check itself)
     *  - Each column on its own line
     *  - TWO spaces before "(id)" in PRIMARY KEY
     *  - Table name immediately after CREATE TABLE with no extra whitespace
     *  - $charset_collate appended at end
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $prefix = $wpdb->prefix;
        
        // Table 1: Compliance records
        $sql1 = "CREATE TABLE {$prefix}zask_compliance_records (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) DEFAULT NULL,
            session_id varchar(100) DEFAULT NULL,
            full_name varchar(255) DEFAULT NULL,
            email varchar(255) DEFAULT NULL,
            email_verified tinyint(1) DEFAULT 0,
            email_verified_at datetime DEFAULT NULL,
            email_verified_ip varchar(100) DEFAULT NULL,
            age_verified tinyint(1) DEFAULT 0,
            age_attestation_date datetime DEFAULT NULL,
            terms_agreed tinyint(1) DEFAULT 0,
            terms_version_id bigint(20) DEFAULT NULL,
            terms_snapshot longtext DEFAULT NULL,
            business_type varchar(255) DEFAULT NULL,
            business_license varchar(255) DEFAULT NULL,
            professional_verified tinyint(1) DEFAULT 0,
            ip_address varchar(100) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            geo_country varchar(10) DEFAULT NULL,
            geo_state varchar(10) DEFAULT NULL,
            geo_city varchar(100) DEFAULT NULL,
            custom_data longtext DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY session_id (session_id),
            KEY email (email)
        ) $charset_collate;";
        
        // Table 2: Terms versions
        $sql2 = "CREATE TABLE {$prefix}zask_terms_versions (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            terms_text longtext NOT NULL,
            terms_type varchar(50) DEFAULT 'age_terms',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        
        // Table 3: FDA alerts
        $sql3 = "CREATE TABLE {$prefix}zask_fda_alerts (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            product_id bigint(20) NOT NULL,
            product_name varchar(255) NOT NULL,
            warning_details longtext NOT NULL,
            fda_url varchar(500) DEFAULT NULL,
            detected_at datetime DEFAULT CURRENT_TIMESTAMP,
            status varchar(20) DEFAULT 'active',
            is_resolved tinyint(1) DEFAULT 0,
            resolved_at datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY product_id (product_id),
            KEY status (status)
        ) $charset_collate;";
        
        // Table 4: FDA products
        $sql4 = "CREATE TABLE {$prefix}zask_fda_products (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            product_name varchar(255) NOT NULL,
            sku varchar(100) DEFAULT NULL,
            last_scanned_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY product_name (product_name)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql1);
        dbDelta($sql2);
        dbDelta($sql3);
        dbDelta($sql4);
        
        // Fallback: if dbDelta didn't add custom_data on old installs, force it via ALTER
        $col = $wpdb->get_results("SHOW COLUMNS FROM {$prefix}zask_compliance_records LIKE 'custom_data'");
        if (empty($col)) {
            $wpdb->query("ALTER TABLE {$prefix}zask_compliance_records ADD COLUMN custom_data longtext DEFAULT NULL AFTER geo_city");
        }
        
        // Log any errors for debugging
        if (!empty($wpdb->last_error)) {
            error_log('ZASK Age-Gate: DB error during table creation – ' . $wpdb->last_error);
        }
    }
    
    /**
     * Check DB version on admin_init and run create_tables if needed.
     * Catches cases where activation hook didn't fire (FTP copy, multisite, etc.)
     */
    public function maybe_upgrade_db() {
        $installed_ver = get_option('zask_agegate_db_version', '0');
        if (version_compare($installed_ver, ZASK_AGEGATE_VERSION, '<')) {
            self::create_tables();
            update_option('zask_agegate_db_version', ZASK_AGEGATE_VERSION);
        }
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('ZASK Age-Gate', 'zask-age-gate'),
            __('Age-Gate', 'zask-age-gate'),
            'manage_zask_agegate',
            'zask-age-gate',
            array($this, 'render_admin_page'),
            'dashicons-shield',
            30
        );
        
        add_submenu_page(
            'zask-age-gate',
            __('Settings', 'zask-age-gate'),
            __('Settings', 'zask-age-gate'),
            'manage_zask_agegate',
            'zask-age-gate',
            array($this, 'render_admin_page')
        );
        
        add_submenu_page(
            'zask-age-gate',
            __('Compliance Records', 'zask-age-gate'),
            __('Compliance Records', 'zask-age-gate'),
            'manage_zask_agegate',
            'zask-compliance-records',
            array($this, 'render_records_page')
        );
        
        add_submenu_page(
            'zask-age-gate',
            __('FDA Monitor', 'zask-age-gate'),
            __('FDA Monitor', 'zask-age-gate'),
            'manage_zask_agegate',
            'zask-fda-monitor',
            array($this, 'render_fda_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        // We'll handle settings via AJAX for better UX
        register_setting('zask_agegate_settings', 'zask_gate_enabled');
        register_setting('zask_agegate_settings', 'zask_gate_stage');
        register_setting('zask_agegate_settings', 'zask_gate_display_mode');
        register_setting('zask_agegate_settings', 'zask_minimum_age');
        register_setting('zask_agegate_settings', 'zask_require_terms');
        register_setting('zask_agegate_settings', 'zask_session_duration');
        register_setting('zask_agegate_settings', 'zask_logout_on_browser_close');
    }
    
    /**
     * Enqueue admin scripts
     */
    public function admin_enqueue_scripts($hook) {
        if (strpos($hook, 'zask-') === false) {
            return;
        }
        
        // Enqueue media library
        wp_enqueue_media();
        
        wp_enqueue_style('zask-admin', ZASK_AGEGATE_URL . 'assets/css/admin.css', array(), ZASK_AGEGATE_VERSION);
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script('zask-admin', ZASK_AGEGATE_URL . 'assets/js/admin.js', array('jquery', 'jquery-ui-sortable'), ZASK_AGEGATE_VERSION, true);
        
        wp_localize_script('zask-admin', 'zaskAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('zask_admin_nonce'),
            'strings' => array(
                'saving' => __('Saving...', 'zask-age-gate'),
                'saved' => __('Settings saved!', 'zask-age-gate'),
                'error' => __('Error saving settings', 'zask-age-gate'),
            )
        ));
    }
    
    /**
     * Enqueue frontend scripts
     */
    public function frontend_enqueue_scripts() {
        if (!$this->should_show_gate()) {
            return;
        }
        
        wp_enqueue_style('zask-gate', ZASK_AGEGATE_URL . 'assets/css/gate.css', array(), ZASK_AGEGATE_VERSION . '.' . time());
        
        // Inject custom appearance overrides
        $btn1  = esc_attr(get_option('zask_style_btn_color', '#667eea'));
        $btn2  = esc_attr(get_option('zask_style_btn_color_end', '#764ba2'));
        $btntx = esc_attr(get_option('zask_style_btn_text', '#ffffff'));
        $fmbg  = esc_attr(get_option('zask_style_form_bg', '#ffffff'));
        $fmtx  = esc_attr(get_option('zask_style_form_text', '#111827'));
        $fmw   = intval(get_option('zask_style_form_width', 500));
        $fmr   = intval(get_option('zask_style_form_radius', 20));
        $bdc   = esc_attr(get_option('zask_style_backdrop', '#000000'));
        $bdo   = intval(get_option('zask_style_backdrop_opacity', 85));
        $bdo_f = round($bdo / 100, 2);
        
        // Convert hex backdrop to rgba
        $r = hexdec(substr($bdc, 1, 2));
        $g = hexdec(substr($bdc, 3, 2));
        $b = hexdec(substr($bdc, 5, 2));
        
        $custom_css = "
            #zask-gate-modal .zask-gate-container { background: {$fmbg} !important; border-radius: {$fmr}px !important; max-width: {$fmw}px !important; color: {$fmtx} !important; }
            #zask-gate-fullpage .zask-fp-card { background: {$fmbg} !important; border-radius: {$fmr}px !important; max-width: {$fmw}px !important; color: {$fmtx} !important; }
            #zask-gate-modal .zask-gate-header h2, #zask-gate-fullpage .zask-fp-header h1, #zask-gate-modal .zask-form-group label, #zask-gate-fullpage .zask-form-group label, #zask-gate-modal .zask-gate-intro, #zask-gate-fullpage .zask-fp-intro, #zask-gate-modal .zask-gate-footer, #zask-gate-fullpage .zask-fp-footer-text, #zask-gate-modal .zask-checkbox-label span, #zask-gate-fullpage .zask-checkbox-label span { color: {$fmtx} !important; }
            #zask-gate-modal .zask-btn-primary, #zask-gate-fullpage .zask-btn-primary { background: linear-gradient(135deg, {$btn1} 0%, {$btn2} 100%) !important; color: {$btntx} !important; }
            #zask-gate-modal .zask-toggle-slider, #zask-gate-fullpage .zask-toggle-slider { background: linear-gradient(135deg, {$btn1} 0%, {$btn2} 100%) !important; }
            #zask-gate-modal .zask-gate-backdrop { background: rgba({$r},{$g},{$b},{$bdo_f}) !important; }
        ";
        wp_add_inline_style('zask-gate', $custom_css);
        
        wp_enqueue_script('zask-gate', ZASK_AGEGATE_URL . 'assets/js/gate.js', array('jquery'), ZASK_AGEGATE_VERSION . '.' . time(), true);
        
        wp_localize_script('zask-gate', 'zaskGate', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('zask_gate_nonce'),
            'home_url' => home_url('/'),
            'stage' => get_option('zask_gate_stage', 'stage1'),
            'display_mode' => get_option('zask_gate_display_mode', 'modal'),
            'minimum_age' => get_option('zask_minimum_age', '21'),
            'custom_fields' => $this->get_enabled_custom_fields(),
            'custom_checkboxes' => $this->get_enabled_custom_checkboxes(),
            'strings' => array(
                'verifying' => __('Verifying...', 'zask-age-gate'),
                'error' => __('Verification failed. Please try again.', 'zask-age-gate'),
                'age_requirement' => sprintf(__('You must be %s or older to access this site.', 'zask-age-gate'), get_option('zask_minimum_age', '21')),
            )
        ));
    }
    
    /**
     * Check if gate should be shown (used by body_class and render_gate_modal)
     */
    private function should_show_gate() {
        // System requests: never show
        if (is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST) ||
            (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) || (defined('DOING_CRON') && DOING_CRON)) {
            return false;
        }
        
        // Admins & Editors bypass
        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            $bypass_roles = array('administrator', 'editor');
            if (array_intersect($bypass_roles, (array) $user->roles)) {
                return false;
            }
        }
        
        // Password reset pages: never show gate
        if ($this->is_password_reset_page()) {
            return false;
        }
        
        // License and enabled checks
        if (get_option('zask_license_status') !== 'active') {
            return false;
        }
        if (get_option('zask_gate_enabled') != '1') {
            return false;
        }
        
        $stage = get_option('zask_gate_stage', 'stage1');
        if ($stage === 'stage0') {
            return false;
        }
        
        // User already verified? No gate.
        if ($this->is_user_verified()) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if user is verified
     */
    private function is_user_verified() {
        $stage = get_option('zask_gate_stage', 'stage1');
        
        if ($stage === 'stage1') {
            // Check session cookie
            return isset($_COOKIE['zask_age_verified']) && $_COOKIE['zask_age_verified'] === 'yes';
        }
        
        if ($stage === 'stage2' || $stage === 'stage3') {
            // Check if user is logged in and has compliance record
            if (!is_user_logged_in()) {
                return false;
            }
            
            global $wpdb;
            $table_name = $wpdb->prefix . 'zask_compliance_records';
            $user_id = get_current_user_id();
            
            // Only require age_verified — terms_agreed is optional depending on settings
            $record = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE user_id = %d AND age_verified = 1",
                $user_id
            ));
            
            if (!$record) {
                return false;
            }
            
            if ($stage === 'stage3') {
                // Stage 3 also requires professional verification
                return $record->professional_verified == 1;
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * AGGRESSIVE GATE ENFORCEMENT
     * Runs at template_redirect priority 1 — before WooCommerce, before any other plugin.
     * 
     * Rules:
     *  - Admin pages, AJAX, REST, cron: never touch
     *  - Password reset / set password pages: always open
     *  - Admins & Editors: always bypass
     *  - Verified users (cookie for Stage 1, compliance record for Stage 2/3): bypass
     *  - Logged-in but NOT verified: redirect to home page (gate shows there)
     *  - NOT logged in: gate overlay renders on every single page
     */
    public function check_gate_access() {
        // Never interfere with backend/system requests
        if (is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST) ||
            (defined('DOING_CRON') && DOING_CRON) || (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST)) {
            return;
        }
        
        // Plugin must be licensed and enabled
        if (get_option('zask_license_status') !== 'active' || get_option('zask_gate_enabled') != '1') {
            return;
        }
        
        $stage = get_option('zask_gate_stage', 'stage1');
        if ($stage === 'stage0') {
            return;
        }
        
        // Always allow password reset & set password pages
        if ($this->is_password_reset_page()) {
            return;
        }
        
        // Admins and Editors always bypass
        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            $bypass_roles = array('administrator', 'editor');
            if (array_intersect($bypass_roles, (array) $user->roles)) {
                return;
            }
        }
        
        // Already verified? Let them through
        if ($this->is_user_verified()) {
            return;
        }
        
        // ---- USER IS NOT VERIFIED — GATE MUST SHOW ----
        
        // For Stage 2/3 only: logged-in but not verified users get redirected
        // to home page where the gate overlay will show.
        // Stage 1 doesn't need this — it's cookie-based and the overlay handles it.
        if (($stage === 'stage2' || $stage === 'stage3') && is_user_logged_in()) {
            if (!$this->is_home_or_front()) {
                wp_safe_redirect(home_url('/'));
                exit;
            }
        }
        
        // NOT logged in — gate renders as overlay on every page via wp_footer.
        // Nothing else to do here; render_gate_modal() handles it.
    }
    
    /**
     * Intercept ALL wp_redirect() calls on the frontend.
     * If the gate should be showing, block redirects to non-password-reset pages.
     * This prevents WooCommerce, other plugins, and theme redirects from
     * sending unverified users away from the gate.
     */
    public function intercept_redirects($location, $status = 302) {
        // Don't intercept admin, AJAX, or REST requests
        if (is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
            return $location;
        }
        
        // Don't intercept if plugin is not active
        if (get_option('zask_license_status') !== 'active' || get_option('zask_gate_enabled') != '1') {
            return $location;
        }
        
        $stage = get_option('zask_gate_stage', 'stage1');
        
        // Stage 0 or Stage 1: no redirect interception needed
        // Stage 1 is cookie-based — the overlay handles everything
        if ($stage === 'stage0' || $stage === 'stage1') {
            return $location;
        }
        
        // Admins/Editors: don't interfere
        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            $bypass_roles = array('administrator', 'editor');
            if (array_intersect($bypass_roles, (array) $user->roles)) {
                return $location;
            }
        }
        
        // Already verified? Don't interfere
        if ($this->is_user_verified()) {
            return $location;
        }
        
        // Allow redirects TO password reset pages
        $password_paths = array('lost-password', 'reset-password', 'set-new-password', 'action=rp', 'action=resetpass');
        foreach ($password_paths as $path) {
            if (strpos($location, $path) !== false) {
                return $location;
            }
        }
        
        // Allow redirects TO home page (that's where the gate shows)
        $home = rtrim(home_url('/'), '/');
        $target = rtrim($location, '/');
        if ($target === $home || $target === '') {
            return $location;
        }
        
        // Block all other redirects — force to home where gate will show
        return home_url('/');
    }
    
    /**
     * Force WooCommerce and WordPress login/registration redirects to home page.
     * Hooks: woocommerce_login_redirect, woocommerce_registration_redirect, login_redirect
     */
    public function force_home_redirect($redirect) {
        // Don't interfere if plugin not active
        if (get_option('zask_license_status') !== 'active' || get_option('zask_gate_enabled') != '1') {
            return $redirect;
        }
        
        $stage = get_option('zask_gate_stage', 'stage1');
        // Only intercept for Stage 2/3 (login-required stages)
        // Stage 1 is cookie-based — WooCommerce redirects don't matter
        if ($stage === 'stage0' || $stage === 'stage1') {
            return $redirect;
        }
        
        // Admins/Editors: don't interfere
        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            $bypass_roles = array('administrator', 'editor');
            if (array_intersect($bypass_roles, (array) $user->roles)) {
                return $redirect;
            }
        }
        
        return home_url('/');
    }
    
    /**
     * Check if current page is a password reset / set password page.
     * These pages are ALWAYS open regardless of gate status.
     */
    private function is_password_reset_page() {
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        
        // WordPress native: wp-login.php?action=rp or resetpass
        if (isset($_GET['action']) && in_array($_GET['action'], array('rp', 'resetpass'), true)) {
            return true;
        }
        
        // WordPress native: has key + login params (set password link)
        if (isset($_GET['key']) && isset($_GET['login'])) {
            return true;
        }
        
        // WooCommerce / common password pages
        $password_pages = array(
            '/my-account/lost-password',
            '/my-account/reset-password',
            '/my-account/set-new-password',
            '/wp-login.php',
        );
        foreach ($password_pages as $page) {
            if (strpos($request_uri, $page) !== false) {
                return true;
            }
        }
        
        // WordPress native password reset action (show_login_form query parameter)
        if (isset($_GET['show']) && $_GET['show'] === 'reset-password') {
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if current page is the home or front page
     */
    private function is_home_or_front() {
        if (is_front_page() || is_home()) {
            return true;
        }
        $uri = rtrim($_SERVER['REQUEST_URI'] ?? '', '/');
        return $uri === '' || $uri === '/' . ltrim(parse_url(home_url(), PHP_URL_PATH) ?: '', '/');
    }
    
    /**
     * Add body class for gate
     */
    public function add_gate_body_class($classes) {
        if ($this->should_show_gate()) {
            $classes[] = 'zask-gate-active';
            $classes[] = 'zask-gate-' . get_option('zask_gate_display_mode', 'modal');
            $classes[] = 'zask-' . get_option('zask_gate_stage', 'stage1');
        }
        return $classes;
    }
    
    /**
     * Render gate modal
     */
    public function render_gate_modal() {
        if (!$this->should_show_gate()) {
            return;
        }
        
        $display_mode = get_option('zask_gate_display_mode', 'modal');
        $stage = get_option('zask_gate_stage', 'stage1');
        
        $template = ZASK_AGEGATE_DIR . 'templates/gate-' . $display_mode . '.php';
        
        if (file_exists($template)) {
            include $template;
        }
    }
    
    /**
     * AJAX: Verify age (Stage 1)
     */
    public function ajax_verify_age() {
        check_ajax_referer('zask_gate_nonce', 'nonce');
        
        $age_confirmed = isset($_POST['age_confirmed']) && $_POST['age_confirmed'] === 'true';
        $terms_agreed = isset($_POST['terms_agreed']) && $_POST['terms_agreed'] === 'true';
        $require_terms = get_option('zask_require_terms') == '1';
        
        if (!$age_confirmed) {
            wp_send_json_error(array('message' => __('You must confirm your age.', 'zask-age-gate')));
        }
        
        if ($require_terms && !$terms_agreed) {
            wp_send_json_error(array('message' => __('You must agree to the terms and conditions.', 'zask-age-gate')));
        }
        
        // Collect custom checkbox values
        $custom_data = $this->collect_custom_post_data();
        
        // Set verification cookie — use '/' path to ensure it works site-wide
        // (COOKIEPATH can cause issues when cookie is set via admin-ajax.php)
        $duration = $this->get_session_duration();
        $expiry = $duration > 0 ? time() + $duration : 0;
        
        $cookie_options = array(
            'expires'  => $expiry,
            'path'     => '/',
            'domain'   => COOKIE_DOMAIN ?: '',
            'secure'   => is_ssl(),
            'httponly'  => true,
            'samesite'  => 'Lax',
        );
        setcookie('zask_age_verified', 'yes', $cookie_options);
        
        // Also set it in $_COOKIE so is_user_verified() works on the same request
        $_COOKIE['zask_age_verified'] = 'yes';
        
        // Log the verification
        $log = array(
            'type' => 'stage1',
            'age_verified' => 1,
            'terms_agreed' => $terms_agreed ? 1 : 0,
        );
        if (!empty($custom_data)) {
            $log['custom_data'] = wp_json_encode($custom_data);
        }
        $this->log_verification($log);
        
        wp_send_json_success(array(
            'message' => __('Age verified successfully!', 'zask-age-gate'),
            'redirect' => home_url()
        ));
    }
    
    /**
     * Get session duration in seconds
     */
    private function get_session_duration() {
        $logout_on_close = get_option('zask_logout_on_browser_close', '0');
        
        if ($logout_on_close === '1') {
            return 0; // Session cookie (expires on browser close)
        }
        
        // Changed to minutes in v1.0.4
        $minutes = get_option('zask_session_duration', '120');
        return intval($minutes) * MINUTE_IN_SECONDS;
    }
    
    /**
     * Handle logout - Clear age verification cookie only
     */
    public function handle_logout() {
        // Clear age verification cookie only
        // Does NOT affect WordPress admin login
        setcookie('zask_age_verified', '', time() - 3600, '/', COOKIE_DOMAIN ?: '', is_ssl(), true);
    }
    
    /**
     * AJAX: Login
     */
    public function ajax_login() {
        check_ajax_referer('zask_gate_nonce', 'nonce');
        
        $login_input = sanitize_text_field($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($login_input) || empty($password)) {
            wp_send_json_error(array('message' => __('Please enter both username/email and password', 'zask-age-gate')));
        }
        
        // Determine if input is an email or username
        if (is_email($login_input)) {
            $user = wp_authenticate($login_input, $password);
        } else {
            // Try as username (user_login)
            $user = wp_authenticate($login_input, $password);
        }
        
        if (is_wp_error($user)) {
            wp_send_json_error(array('message' => $user->get_error_message()));
        }
        
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, false, is_ssl());
        
        wp_send_json_success(array(
            'message' => __('Login successful!', 'zask-age-gate'),
            'redirect' => home_url()
        ));
    }
    
    /**
     * AJAX: Register
     */
    public function ajax_register() {
        check_ajax_referer('zask_gate_nonce', 'nonce');
        
        $email = sanitize_email($_POST['email']);
        $full_name = sanitize_text_field($_POST['full_name']);
        $age_confirmed = isset($_POST['age_confirmed']) && $_POST['age_confirmed'] === 'true';
        $terms_agreed = isset($_POST['terms_agreed']) && $_POST['terms_agreed'] === 'true';
        $business_type = sanitize_text_field($_POST['business_type'] ?? '');
        $pw_mode = get_option('zask_password_mode', 'user_set');
        
        if (!$age_confirmed) {
            wp_send_json_error(array('message' => __('You must confirm your age.', 'zask-age-gate')));
        }
        
        if (get_option('zask_require_terms') == '1' && !$terms_agreed) {
            wp_send_json_error(array('message' => __('You must agree to the terms and conditions.', 'zask-age-gate')));
        }
        
        // Handle password based on mode
        if ($pw_mode === 'user_set') {
            $password = $_POST['password'] ?? '';
            if (strlen($password) < 8) {
                wp_send_json_error(array('message' => __('Password must be at least 8 characters.', 'zask-age-gate')));
            }
        } else {
            // Generate a random password for both 'set_password_link' and 'temp_password' modes
            $password = wp_generate_password(16, true, false);
        }
        
        // Generate username from email: abc@gmail.com → abc.gmail
        $username = $this->generate_username_from_email($email);
        
        // Create user with the generated username (not the email)
        $user_id = wp_create_user($username, $password, $email);
        
        if (is_wp_error($user_id)) {
            wp_send_json_error(array('message' => $user_id->get_error_message()));
        }
        
        // Update user meta
        wp_update_user(array(
            'ID' => $user_id,
            'display_name' => $full_name,
            'first_name' => explode(' ', $full_name)[0],
        ));
        
        // Create compliance record
        $log = array(
            'user_id' => $user_id,
            'full_name' => $full_name,
            'email' => $email,
            'age_verified' => 1,
            'terms_agreed' => $terms_agreed ? 1 : 0,
            'business_type' => $business_type,
            'professional_verified' => !empty($business_type) ? 1 : 0,
        );
        
        // Collect custom fields and checkbox values
        $custom_data = $this->collect_custom_post_data();
        if (!empty($custom_data)) {
            $log['custom_data'] = wp_json_encode($custom_data);
        }
        
        $this->log_verification($log);
        
        // Handle email and response based on password mode
        if ($pw_mode === 'set_password_link') {
            // Send WordPress set-password email (uses built-in reset key mechanism)
            $this->send_set_password_email($user_id, $email, $full_name, $username);
            
            // Send verification email if required
            if (get_option('zask_require_email_verification') == '1') {
                $this->send_verification_email($email);
                wp_send_json_success(array(
                    'message' => __('Account created! Check your email for a verification code and a link to set your password.', 'zask-age-gate'),
                    'requires_verification' => true
                ));
            }
            
            // Log user in immediately so they can shop
            wp_set_current_user($user_id);
            wp_set_auth_cookie($user_id, false, is_ssl());
            
            wp_send_json_success(array('message' => __('Registration successful!', 'zask-age-gate'), 'redirect' => home_url()));
            
        } elseif ($pw_mode === 'temp_password') {
            // Send email with the temporary password
            $this->send_temp_password_email($user_id, $email, $full_name, $password, $username);
            
            // Send verification email if required
            if (get_option('zask_require_email_verification') == '1') {
                $this->send_verification_email($email);
                wp_send_json_success(array(
                    'message' => __('Account created! Check your email for a verification code and your temporary password.', 'zask-age-gate'),
                    'requires_verification' => true
                ));
            }
            
            // Log user in immediately so they can shop
            wp_set_current_user($user_id);
            wp_set_auth_cookie($user_id, false, is_ssl());
            
            wp_send_json_success(array('message' => __('Registration successful!', 'zask-age-gate'), 'redirect' => home_url()));
            
        } else {
            // user_set mode — original behaviour
            $this->send_registration_email($user_id, $email, $full_name, $password, $username);
            
            // Send verification email if required
            if (get_option('zask_require_email_verification') == '1') {
                $this->send_verification_email($email);
                wp_send_json_success(array(
                    'message' => __('Registration successful! Please check your email for verification code.', 'zask-age-gate'),
                    'requires_verification' => true
                ));
            }
            
            // Log user in
            wp_set_current_user($user_id);
            wp_set_auth_cookie($user_id, false, is_ssl());
            
            wp_send_json_success(array('message' => __('Registration successful!', 'zask-age-gate'), 'redirect' => home_url()));
        }
    }
    
    /**
     * AJAX: Password Reset
     */
    public function ajax_reset_password() {
        error_log('ZASK: Password reset request started');
        
        check_ajax_referer('zask_gate_nonce', 'nonce');
        
        $email = sanitize_email($_POST['email'] ?? '');
        
        if (empty($email)) {
            error_log('ZASK: Empty email provided');
            wp_send_json_error(array('message' => __('Please enter your email address', 'zask-age-gate')));
            exit;
        }
        
        if (!is_email($email)) {
            error_log('ZASK: Invalid email format: ' . $email);
            wp_send_json_error(array('message' => __('Please enter a valid email address', 'zask-age-gate')));
            exit;
        }
        
        // Check if user exists
        $user = get_user_by('email', $email);
        
        if (!$user) {
            error_log('ZASK: User not found for email: ' . $email);
            // Don't reveal if user exists or not for security
            wp_send_json_success(array(
                'message' => __('If an account exists with this email, you will receive a password reset link shortly.', 'zask-age-gate')
            ));
            exit;
        }
        
        error_log('ZASK: User found, generating reset key for: ' . $email);
        
        // Use WordPress native password reset
        $reset_key = get_password_reset_key($user);
        
        if (is_wp_error($reset_key)) {
            error_log('ZASK Password Reset Error: ' . $reset_key->get_error_message());
            wp_send_json_error(array('message' => __('Unable to send reset link. Please try again later.', 'zask-age-gate')));
            exit;
        }
        
        error_log('ZASK: Reset key generated successfully');
        
        // Send email - try multiple methods
        $email_sent = false;
        
        // Method 1: Try WooCommerce
        if (class_exists('WC_Emails')) {
            try {
                $wc_emails = WC()->mailer()->get_emails();
                if (isset($wc_emails['WC_Email_Customer_Reset_Password'])) {
                    $wc_emails['WC_Email_Customer_Reset_Password']->trigger($user->user_login, $reset_key);
                    $email_sent = true;
                    error_log('ZASK: Password reset sent via WooCommerce to ' . $email);
                }
            } catch (Exception $e) {
                error_log('ZASK WooCommerce Email Error: ' . $e->getMessage());
            }
        }
        
        // Method 2: WordPress native if WC failed
        if (!$email_sent) {
            error_log('ZASK: Attempting WordPress native email');
            $result = $this->send_password_reset_email($user, $reset_key);
            if ($result) {
                $email_sent = true;
                error_log('ZASK: Password reset sent via WordPress to ' . $email);
            } else {
                error_log('ZASK: WordPress email failed for ' . $email);
            }
        }
        
        error_log('ZASK: Sending success response to frontend');
        
        // Always return success (for security)
        wp_send_json_success(array(
            'message' => __('Password reset link sent! Please check your email.', 'zask-age-gate')
        ));
        exit;
    }
    
    /**
     * Send password reset email
     */
    private function send_password_reset_email($user, $reset_key) {
        $reset_url = network_site_url("wp-login.php?action=rp&key=$reset_key&login=" . rawurlencode($user->user_login), 'login');
        
        $subject = sprintf(__('[%s] Password Reset', 'zask-age-gate'), get_bloginfo('name'));
        
        $message = __('Someone has requested a password reset for your account.', 'zask-age-gate') . "\r\n\r\n";
        $message .= __('If this was a mistake, just ignore this email and nothing will happen.', 'zask-age-gate') . "\r\n\r\n";
        $message .= __('To reset your password, visit the following address:', 'zask-age-gate') . "\r\n\r\n";
        $message .= $reset_url . "\r\n";
        
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        
        return wp_mail($user->user_email, $subject, $message, $headers);
    }
    
    /**
     * Send registration welcome email
     */
    private function send_registration_email($user_id, $email, $full_name, $password, $username = '') {
        $site_name = get_bloginfo('name');
        $site_url = home_url();
        $login_url = wp_login_url();
        
        // Fall back to WP user_login if username not passed
        if (empty($username)) {
            $wp_user = get_userdata($user_id);
            $username = $wp_user ? $wp_user->user_login : $email;
        }
        
        // Email subject
        $subject = sprintf(__('[%s] Your Account Has Been Created', 'zask-age-gate'), $site_name);
        
        // Email message
        $message = sprintf(__('Hi %s,', 'zask-age-gate'), $full_name) . "\r\n\r\n";
        $message .= sprintf(__('Welcome to %s! Your account has been successfully created.', 'zask-age-gate'), $site_name) . "\r\n\r\n";
        $message .= __('Your login details:', 'zask-age-gate') . "\r\n";
        $message .= sprintf(__('Username: %s', 'zask-age-gate'), $username) . "\r\n";
        $message .= sprintf(__('Email: %s', 'zask-age-gate'), $email) . "\r\n\r\n";
        $message .= __('You can log in at:', 'zask-age-gate') . "\r\n";
        $message .= $login_url . "\r\n\r\n";
        $message .= __('Thank you for registering!', 'zask-age-gate') . "\r\n\r\n";
        $message .= sprintf(__('- The %s Team', 'zask-age-gate'), $site_name) . "\r\n";
        
        // Email headers
        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . $site_name . ' <' . get_option('admin_email') . '>'
        );
        
        // Send email and log result
        $result = wp_mail($email, $subject, $message, $headers);
        
        if ($result) {
            error_log('ZASK: Registration email sent to ' . $email);
        } else {
            error_log('ZASK: Registration email FAILED for ' . $email);
        }
        
        return $result;
    }
    
    /**
     * Send set-password link email (password mode: set_password_link)
     * Uses WordPress built-in password reset key mechanism
     */
    private function send_set_password_email($user_id, $email, $full_name, $username = '') {
        $site_name = get_bloginfo('name');
        $login_url = wp_login_url();
        
        if (empty($username)) {
            $wp_user = get_userdata($user_id);
            $username = $wp_user ? $wp_user->user_login : $email;
        }
        
        // Generate a password reset key
        $user = get_userdata($user_id);
        $key = get_password_reset_key($user);
        
        if (is_wp_error($key)) {
            error_log('ZASK: Failed to generate password reset key for ' . $email);
            return false;
        }
        
        $set_password_url = network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user->user_login), 'login');
        
        $subject = sprintf(__('[%s] Set Your Password', 'zask-age-gate'), $site_name);
        
        $message = sprintf(__('Hi %s,', 'zask-age-gate'), $full_name) . "\r\n\r\n";
        $message .= sprintf(__('Welcome to %s! Your account has been created successfully.', 'zask-age-gate'), $site_name) . "\r\n\r\n";
        $message .= __('Your login details:', 'zask-age-gate') . "\r\n";
        $message .= sprintf(__('Username: %s', 'zask-age-gate'), $username) . "\r\n";
        $message .= sprintf(__('Email: %s', 'zask-age-gate'), $email) . "\r\n\r\n";
        $message .= __('To set your password, please click the link below:', 'zask-age-gate') . "\r\n\r\n";
        $message .= $set_password_url . "\r\n\r\n";
        $message .= __('This link will expire in 24 hours.', 'zask-age-gate') . "\r\n\r\n";
        $message .= sprintf(__('- The %s Team', 'zask-age-gate'), $site_name) . "\r\n";
        
        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . $site_name . ' <' . get_option('admin_email') . '>'
        );
        
        $result = wp_mail($email, $subject, $message, $headers);
        
        if ($result) {
            error_log('ZASK: Set-password email sent to ' . $email);
        } else {
            error_log('ZASK: Set-password email FAILED for ' . $email);
        }
        
        return $result;
    }
    
    /**
     * Send temporary password email (password mode: temp_password)
     */
    private function send_temp_password_email($user_id, $email, $full_name, $temp_password, $username = '') {
        $site_name = get_bloginfo('name');
        $login_url = wp_login_url();
        
        if (empty($username)) {
            $wp_user = get_userdata($user_id);
            $username = $wp_user ? $wp_user->user_login : $email;
        }
        
        $subject = sprintf(__('[%s] Your Account & Temporary Password', 'zask-age-gate'), $site_name);
        
        $message = sprintf(__('Hi %s,', 'zask-age-gate'), $full_name) . "\r\n\r\n";
        $message .= sprintf(__('Welcome to %s! Your account has been created successfully.', 'zask-age-gate'), $site_name) . "\r\n\r\n";
        $message .= __('Your login details:', 'zask-age-gate') . "\r\n";
        $message .= sprintf(__('Username: %s', 'zask-age-gate'), $username) . "\r\n";
        $message .= sprintf(__('Email: %s', 'zask-age-gate'), $email) . "\r\n";
        $message .= sprintf(__('Temporary Password: %s', 'zask-age-gate'), $temp_password) . "\r\n\r\n";
        $message .= __('IMPORTANT: Please change your password after logging in for security.', 'zask-age-gate') . "\r\n\r\n";
        $message .= __('You can log in at:', 'zask-age-gate') . "\r\n";
        $message .= $login_url . "\r\n\r\n";
        $message .= sprintf(__('- The %s Team', 'zask-age-gate'), $site_name) . "\r\n";
        
        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . $site_name . ' <' . get_option('admin_email') . '>'
        );
        
        $result = wp_mail($email, $subject, $message, $headers);
        
        if ($result) {
            error_log('ZASK: Temp password email sent to ' . $email);
        } else {
            error_log('ZASK: Temp password email FAILED for ' . $email);
        }
        
        return $result;
    }
    
    /**
     * Get enabled custom fields (for frontend rendering)
     */
    public function get_enabled_custom_fields() {
        $fields = get_option('zask_custom_fields', array());
        if (!is_array($fields)) return array();
        return array_values(array_filter($fields, function($f) {
            return !isset($f['enabled']) || $f['enabled'];
        }));
    }
    
    /**
     * Get enabled custom checkboxes (for frontend rendering)
     */
    public function get_enabled_custom_checkboxes() {
        $cbs = get_option('zask_custom_checkboxes', array());
        if (!is_array($cbs)) return array();
        return array_values(array_filter($cbs, function($cb) {
            return !isset($cb['enabled']) || $cb['enabled'];
        }));
    }
    
    /**
     * Render custom fields HTML for gate templates (Stage 2/3 registration form)
     */
    public static function render_custom_fields_html() {
        $instance = self::get_instance();
        $fields = $instance->get_enabled_custom_fields();
        if (empty($fields)) return;
        
        foreach ($fields as $i => $field) {
            $slug = 'zask_cf_' . sanitize_title($field['label']);
            $required = !empty($field['required']) ? 'required' : '';
            $req_star = !empty($field['required']) ? ' <span class="zask-required">*</span>' : '';
            $type = $field['type'] ?? 'text';
            ?>
            <div class="zask-form-group">
                <label for="<?php echo esc_attr($slug); ?>"><?php echo esc_html($field['label']); ?><?php echo $req_star; ?></label>
                <?php if ($type === 'dropdown'): ?>
                    <?php
                    $options_raw = $field['options'] ?? '';
                    $options = array_filter(array_map('trim', explode("\n", $options_raw)));
                    ?>
                    <select id="<?php echo esc_attr($slug); ?>"
                            name="<?php echo esc_attr($slug); ?>"
                            class="zask-custom-field"
                            data-label="<?php echo esc_attr($field['label']); ?>"
                            <?php echo $required; ?>>
                        <option value=""><?php _e('Select...', 'zask-age-gate'); ?></option>
                        <?php foreach ($options as $opt): ?>
                            <option value="<?php echo esc_attr($opt); ?>"><?php echo esc_html($opt); ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <input type="<?php echo ($type === 'number') ? 'number' : 'text'; ?>"
                           id="<?php echo esc_attr($slug); ?>"
                           name="<?php echo esc_attr($slug); ?>"
                           class="zask-custom-field"
                           data-label="<?php echo esc_attr($field['label']); ?>"
                           placeholder="<?php echo esc_attr($field['placeholder'] ?? ''); ?>"
                           <?php echo $required; ?>>
                <?php endif; ?>
            </div>
            <?php
        }
    }
    
    /**
     * Render custom checkboxes HTML for gate templates (all stages)
     */
    public static function render_custom_checkboxes_html() {
        $instance = self::get_instance();
        $cbs = $instance->get_enabled_custom_checkboxes();
        if (empty($cbs)) return;
        
        foreach ($cbs as $i => $cb) {
            $slug = 'zask_cb_' . sanitize_title($cb['label']);
            $required = !empty($cb['required']) ? 'required' : '';
            ?>
            <div class="zask-form-group">
                <label class="zask-checkbox-label">
                    <input type="checkbox"
                           name="<?php echo esc_attr($slug); ?>"
                           class="zask-custom-checkbox"
                           data-label="<?php echo esc_attr($cb['label']); ?>"
                           <?php echo $required; ?>>
                    <span><?php echo esc_html($cb['label']); ?></span>
                </label>
            </div>
            <?php
        }
    }
    
    /**
     * Generate a unique username from an email address.
     * abc@gmail.com → abc.gmail
     * If taken, appends a number: abc.gmail2, abc.gmail3, etc.
     */
    private function generate_username_from_email($email) {
        $parts = explode('@', $email);
        $local = $parts[0]; // abc
        $domain_parts = explode('.', $parts[1] ?? '');
        $domain = $domain_parts[0] ?? ''; // gmail
        
        $base = sanitize_user($local . '.' . $domain, true);
        $base = strtolower($base);
        
        // Ensure uniqueness
        $username = $base;
        $suffix = 2;
        while (username_exists($username)) {
            $username = $base . $suffix;
            $suffix++;
        }
        
        return $username;
    }
    
    /**
     * Collect custom field and checkbox values from POST data
     * Returns an associative array: { "fields": { label: value, ... }, "checkboxes": { label: true/false, ... } }
     */
    private function collect_custom_post_data() {
        $result = array();
        
        // Custom fields (names start with zask_cf_)
        $fields = $this->get_enabled_custom_fields();
        if (!empty($fields)) {
            foreach ($fields as $field) {
                $slug = 'zask_cf_' . sanitize_title($field['label']);
                if (isset($_POST[$slug])) {
                    $result['fields'][$field['label']] = sanitize_text_field($_POST[$slug]);
                }
            }
        }
        
        // Custom checkboxes (names start with zask_cb_)
        $cbs = $this->get_enabled_custom_checkboxes();
        if (!empty($cbs)) {
            foreach ($cbs as $cb) {
                $slug = 'zask_cb_' . sanitize_title($cb['label']);
                $checked = isset($_POST[$slug]) && $_POST[$slug] === 'true';
                $result['checkboxes'][$cb['label']] = $checked;
            }
        }
        
        return $result;
    }
    
    /**
     * Log verification
     */
    private function log_verification($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'zask_compliance_records';
        
        $defaults = array(
            'session_id' => session_id() ?: uniqid('zask_', true),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => current_time('mysql'),
        );
        
        $data = array_merge($defaults, $data);
        
        $wpdb->insert($table_name, $data);
    }
    
    /**
     * Send verification email
     */
    private function send_verification_email($email) {
        $code = rand(100000, 999999);
        set_transient('zask_verify_' . md5($email), $code, 15 * MINUTE_IN_SECONDS);
        
        $subject = __('Verify your email address', 'zask-age-gate');
        $message = sprintf(__('Your verification code is: %s', 'zask-age-gate'), $code);
        
        wp_mail($email, $subject, $message);
    }
    
    /**
     * AJAX: Save settings
     */
    public function ajax_save_settings() {
        check_ajax_referer('zask_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_zask_agegate')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'zask-age-gate')));
        }
        
        $settings = $_POST['settings'] ?? array();
        
        foreach ($settings as $key => $value) {
            // Form builder fields are stored as JSON arrays
            if ($key === 'zask_custom_fields' || $key === 'zask_custom_checkboxes') {
                $decoded = json_decode(wp_unslash($value), true);
                if (is_array($decoded)) {
                    // Sanitize every item
                    $clean = array();
                    foreach ($decoded as $item) {
                        $sanitized = array();
                        foreach ($item as $k => $v) {
                            $safe_key = sanitize_key($k);
                            // Preserve newlines for dropdown options text
                            if ($safe_key === 'options') {
                                $sanitized[$safe_key] = sanitize_textarea_field($v);
                            } else {
                                $sanitized[$safe_key] = sanitize_text_field($v);
                            }
                        }
                        $clean[] = $sanitized;
                    }
                    update_option($key, $clean);
                }
                continue;
            }
            
            // Color values: sanitize as hex
            if (strpos($key, 'zask_style_btn_color') === 0 || strpos($key, 'zask_style_btn_text') === 0 || strpos($key, 'zask_style_form_bg') === 0 || strpos($key, 'zask_style_form_text') === 0 || strpos($key, 'zask_style_backdrop') === 0) {
                update_option($key, sanitize_hex_color($value));
                continue;
            }
            
            // Textarea fields: preserve newlines
            if ($key === 'zask_terms_text') {
                update_option($key, sanitize_textarea_field($value));
                continue;
            }
            
            update_option($key, sanitize_text_field($value));
        }
        
        wp_send_json_success(array('message' => __('Settings saved successfully!', 'zask-age-gate')));
    }
    
    /**
     * AJAX: Activate License
     */
    public function ajax_activate_license() {
        check_ajax_referer('zask_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_zask_agegate')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'zask-age-gate')));
        }
        
        $license_key = sanitize_text_field($_POST['license_key'] ?? '');
        
        if (empty($license_key)) {
            wp_send_json_error(array('message' => __('License key is required', 'zask-age-gate')));
        }
        
        // Get current domain
        $domain = parse_url(home_url(), PHP_URL_HOST);
        $site_url = home_url();
        
        // Call license server API
        $api_url = 'https://zask.it/wp-json/zask-license/v1/activate';
        
        $response = wp_remote_post($api_url, array(
            'timeout' => 15,
            'body' => array(
                'license_key' => $license_key,
                'domain' => $domain,
                'site_url' => $site_url,
                'plugin_version' => ZASK_AGEGATE_VERSION
            )
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error(array(
                'message' => __('Connection error: ', 'zask-age-gate') . $response->get_error_message()
            ));
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!$data || !isset($data['success'])) {
            wp_send_json_error(array(
                'message' => __('Invalid response from license server', 'zask-age-gate')
            ));
        }
        
        if ($data['success']) {
            // Save license info
            update_option('zask_license_key', $license_key);
            update_option('zask_license_status', 'active');
            update_option('zask_license_tier', $data['data']['tier'] ?? 'professional');
            update_option('zask_license_expires', $data['data']['expires_at'] ?? 'Never');
            
            wp_send_json_success(array(
                'message' => __('License activated successfully!', 'zask-age-gate')
            ));
        } else {
            wp_send_json_error(array(
                'message' => $data['message'] ?? __('License activation failed', 'zask-age-gate')
            ));
        }
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        include ZASK_AGEGATE_DIR . 'templates/admin-settings.php';
    }
    
    /**
     * Render records page
     */
    public function render_records_page() {
        include ZASK_AGEGATE_DIR . 'templates/admin-records.php';
    }
    
    /**
     * Render FDA monitor page
     */
    public function render_fda_page() {
        include ZASK_AGEGATE_DIR . 'templates/admin-fda.php';
    }
    
    /**
     * AJAX: Export CSV
     */
    public function ajax_export_csv() {
        check_ajax_referer('zask_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_zask_agegate')) {
            wp_die(__('Insufficient permissions', 'zask-age-gate'));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'zask_compliance_records';
        $records = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");
        
        // Scan ALL records to discover every custom field & checkbox key ever used
        $all_field_keys = array();
        $all_cb_keys = array();
        $parsed_cache = array(); // id => parsed array
        
        foreach ($records as $record) {
            $cd = array();
            if (!empty($record->custom_data)) {
                $cd = json_decode($record->custom_data, true);
                if (!is_array($cd)) $cd = array();
            }
            $parsed_cache[$record->id] = $cd;
            if (!empty($cd['fields']) && is_array($cd['fields'])) {
                foreach (array_keys($cd['fields']) as $k) $all_field_keys[$k] = $k;
            }
            if (!empty($cd['checkboxes']) && is_array($cd['checkboxes'])) {
                foreach (array_keys($cd['checkboxes']) as $k) $all_cb_keys[$k] = $k;
            }
        }
        
        // Also include keys from current config
        $cfg_fields = get_option('zask_custom_fields', array());
        if (is_array($cfg_fields)) {
            foreach ($cfg_fields as $f) {
                if (!empty($f['label'])) $all_field_keys[$f['label']] = $f['label'];
            }
        }
        $cfg_cbs = get_option('zask_custom_checkboxes', array());
        if (is_array($cfg_cbs)) {
            foreach ($cfg_cbs as $cb) {
                if (!empty($cb['label'])) $all_cb_keys[$cb['label']] = $cb['label'];
            }
        }
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="zask-compliance-' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Build header row
        $headers = array('ID', 'User ID', 'Username', 'Email', 'Full Name', 'Age Verified', 'Terms Agreed', 'Business Type');
        foreach ($all_field_keys as $fk) {
            $headers[] = $fk;
        }
        foreach ($all_cb_keys as $ck) {
            $headers[] = $ck;
        }
        $headers[] = 'IP Address';
        $headers[] = 'Created At';
        fputcsv($output, $headers);
        
        // Data rows
        foreach ($records as $record) {
            $cd = $parsed_cache[$record->id] ?? array();
            $cd_fields = $cd['fields'] ?? array();
            $cd_cbs = $cd['checkboxes'] ?? array();
            
            // Look up WP username
            $wp_username = '';
            if (!empty($record->user_id)) {
                $wp_user = get_userdata($record->user_id);
                if ($wp_user) {
                    $wp_username = $wp_user->user_login;
                }
            }
            
            $row = array(
                $record->id,
                $record->user_id,
                $wp_username,
                $record->email,
                $record->full_name,
                $record->age_verified ? 'Yes' : 'No',
                $record->terms_agreed ? 'Yes' : 'No',
                $record->business_type,
            );
            
            foreach ($all_field_keys as $fk) {
                $row[] = isset($cd_fields[$fk]) ? $cd_fields[$fk] : '';
            }
            foreach ($all_cb_keys as $ck) {
                if (isset($cd_cbs[$ck])) {
                    $row[] = $cd_cbs[$ck] ? 'Yes' : 'No';
                } else {
                    $row[] = '';
                }
            }
            
            $row[] = $record->ip_address ?? '';
            $row[] = $record->created_at;
            
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * AJAX: Delete record
     */
    public function ajax_delete_record() {
        check_ajax_referer('zask_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_zask_agegate')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'zask-age-gate')));
        }
        
        $record_id = intval($_POST['record_id']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'zask_compliance_records';
        $wpdb->delete($table_name, array('id' => $record_id));
        
        wp_send_json_success(array('message' => __('Record deleted successfully!', 'zask-age-gate')));
    }
    
    /**
     * AJAX: Refresh users table
     */
    public function ajax_refresh_users() {
        check_ajax_referer('zask_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_zask_agegate')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'zask-age-gate')));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'zask_compliance_records';
        $records = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC LIMIT 100");
        
        ob_start();
        include ZASK_AGEGATE_DIR . 'templates/partials/records-table.php';
        $html = ob_get_clean();
        
        wp_send_json_success(array('html' => $html));
    }
    
    /**
     * Show warning if license is not active
     */
    public function license_warning_notice() {
        $license_status = get_option('zask_license_status');
        
        if ($license_status !== 'active') {
            ?>
            <div class="notice notice-error">
                <p><strong><?php _e('ZASK Age-Gate: License Not Active!', 'zask-age-gate'); ?></strong></p>
                <p><?php _e('The age gate will NOT function without an active license. Please activate your license in Age-Gate → Settings → License tab.', 'zask-age-gate'); ?></p>
            </div>
            <?php
        }
    }
    
    /**
     * AJAX: Delete old records
     */
    public function ajax_delete_old_records() {
        check_ajax_referer('zask_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_zask_agegate')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'zask-age-gate')));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'zask_compliance_records';
        $one_year_ago = date('Y-m-d H:i:s', strtotime('-1 year'));
        
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_name WHERE created_at < %s",
            $one_year_ago
        ));
        
        wp_send_json_success(array(
            'message' => sprintf(__('Deleted %d old records', 'zask-age-gate'), $deleted)
        ));
    }
    
    /**
     * AJAX: Refresh FDA
     */
    public function ajax_refresh_fda() {
        check_ajax_referer('zask_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_zask_agegate')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'zask-age-gate')));
        }
        
        // Trigger FDA check
        do_action('zask_fda_check');
        
        update_option('zask_fda_last_check', current_time('mysql'));
        
        wp_send_json_success(array(
            'message' => __('FDA check completed successfully', 'zask-age-gate')
        ));
    }
    
    /**
     * AJAX: Add FDA product
     */
    public function ajax_add_fda_product() {
        check_ajax_referer('zask_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_zask_agegate')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'zask-age-gate')));
        }
        
        $product_name = sanitize_text_field($_POST['product_name'] ?? '');
        $sku = sanitize_text_field($_POST['sku'] ?? '');
        
        if (empty($product_name)) {
            wp_send_json_error(array('message' => __('Product name is required', 'zask-age-gate')));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'zask_fda_products';
        
        $wpdb->insert($table_name, array(
            'product_name' => $product_name,
            'sku' => $sku,
            'created_at' => current_time('mysql')
        ));
        
        wp_send_json_success(array(
            'message' => __('Product added to monitoring', 'zask-age-gate')
        ));
    }
    
    /**
     * AJAX: Remove FDA product
     */
    public function ajax_remove_fda_product() {
        check_ajax_referer('zask_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_zask_agegate')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'zask-age-gate')));
        }
        
        $product_id = intval($_POST['product_id'] ?? 0);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'zask_fda_products';
        
        $wpdb->delete($table_name, 
            array('id' => $product_id)
        );
        
        wp_send_json_success(array(
            'message' => __('Product removed from monitoring', 'zask-age-gate')
        ));
    }
    
    /**
     * AJAX: Resolve FDA alert
     */
    public function ajax_resolve_fda_alert() {
        check_ajax_referer('zask_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_zask_agegate')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'zask-age-gate')));
        }
        
        $alert_id = intval($_POST['alert_id'] ?? 0);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'zask_fda_alerts';
        
        $wpdb->update($table_name,
            array('is_resolved' => 1),
            array('id' => $alert_id)
        );
        
        wp_send_json_success(array(
            'message' => __('Alert marked as resolved', 'zask-age-gate')
        ));
    }
    
    /**
     * AJAX: Scan single product for FDA warnings
     */
    public function ajax_scan_single_product() {
        check_ajax_referer('zask_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_zask_agegate')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'zask-age-gate')));
        }
        
        $product_id = intval($_POST['product_id'] ?? 0);
        
        if (!$product_id) {
            wp_send_json_error(array('message' => __('Invalid product ID', 'zask-age-gate')));
        }
        
        global $wpdb;
        $products_table = $wpdb->prefix . 'zask_fda_products';
        $alerts_table = $wpdb->prefix . 'zask_fda_alerts';
        
        // Get product
        $product = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $products_table WHERE id = %d",
            $product_id
        ));
        
        if (!$product) {
            wp_send_json_error(array('message' => __('Product not found', 'zask-age-gate')));
        }
        
        // Simulate FDA API scan
        // In production, this would call actual FDA API
        // For demo purposes, we'll randomly generate some results
        
        $alerts_found = array();
        
        // Randomly check if product has warnings (10% chance for testing)
        if (rand(1, 10) === 1) {
            $sample_warnings = array(
                'Product contains unapproved health claims',
                'Labeling violates FDA regulations',
                'Product marketed as dietary supplement without approval',
                'Contains banned ingredients',
                'Misbranding under section 502 of the FD&C Act'
            );
            
            $warning = $sample_warnings[array_rand($sample_warnings)];
            $alerts_found[] = $warning;
            
            // Insert alert into database
            $wpdb->insert($alerts_table, array(
                'product_id' => $product_id,
                'product_name' => $product->product_name,
                'warning_details' => $warning,
                'fda_url' => 'https://www.fda.gov/inspections-compliance-enforcement-and-criminal-investigations/warning-letters',
                'detected_at' => current_time('mysql'),
                'status' => 'active'
            ));
        }
        
        // Update last scanned timestamp
        $wpdb->update($products_table,
            array('last_scanned_at' => current_time('mysql')),
            array('id' => $product_id)
        );
        
        // Update global last scan option
        update_option('zask_last_fda_scan', current_time('mysql'));
        
        wp_send_json_success(array(
            'alerts' => $alerts_found,
            'product_name' => $product->product_name,
            'scanned_at' => current_time('mysql')
        ));
    }
}
