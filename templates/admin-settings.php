<?php
/**
 * Admin Settings Page
 * Modern dashboard-style settings interface
 *
 * @package ZASK_Age_Gate
 */

if (!defined('ABSPATH')) exit;

$license_status = ZASK_License::get_license_status();
$license_tier = ZASK_License::get_tier();
?>

<div class="wrap zask-admin-wrap">
    <!-- Header -->
    <div class="zask-admin-header">
        <div class="zask-header-left">
            <h1>
                <span class="zask-logo-icon">🛡️</span>
                <?php _e('ZASK Age-Gate & Compliance', 'zask-age-gate'); ?>
            </h1>
            <p class="zask-subtitle"><?php _e('Advanced Age Verification & FDA Compliance System', 'zask-age-gate'); ?></p>
        </div>
        
        <div class="zask-header-right">
            <div class="zask-license-badge zask-license-<?php echo esc_attr($license_status); ?>">
                <span class="zask-license-dot"></span>
                <?php echo ucfirst($license_status); ?> - <?php echo ucfirst($license_tier); ?>
            </div>
            <span class="zask-version">v<?php echo ZASK_AGEGATE_VERSION; ?></span>
        </div>
    </div>
    
    <!-- Navigation Tabs -->
    <nav class="zask-admin-nav">
        <button class="zask-nav-tab active" data-tab="general">
            <span class="dashicons dashicons-admin-settings"></span>
            <?php _e('General', 'zask-age-gate'); ?>
        </button>
        <button class="zask-nav-tab" data-tab="gate">
            <span class="dashicons dashicons-shield"></span>
            <?php _e('Gate Configuration', 'zask-age-gate'); ?>
        </button>
        <button class="zask-nav-tab" data-tab="session">
            <span class="dashicons dashicons-clock"></span>
            <?php _e('Session Management', 'zask-age-gate'); ?>
        </button>
        <button class="zask-nav-tab" data-tab="geo">
            <span class="dashicons dashicons-location"></span>
            <?php _e('Geographic Compliance', 'zask-age-gate'); ?>
        </button>
        <button class="zask-nav-tab" data-tab="terms">
            <span class="dashicons dashicons-media-document"></span>
            <?php _e('Terms & Content', 'zask-age-gate'); ?>
        </button>
        <button class="zask-nav-tab" data-tab="appearance">
            <span class="dashicons dashicons-art"></span>
            <?php _e('Appearance', 'zask-age-gate'); ?>
        </button>
        <button class="zask-nav-tab" data-tab="form-builder">
            <span class="dashicons dashicons-forms"></span>
            <?php _e('Form Builder', 'zask-age-gate'); ?>
        </button>
        <button class="zask-nav-tab" data-tab="license">
            <span class="dashicons dashicons-admin-network"></span>
            <?php _e('License', 'zask-age-gate'); ?>
        </button>
    </nav>
    
    <!-- Content Area -->
    <div class="zask-admin-content">
        
        <!-- General Tab -->
        <div class="zask-tab-content active" data-tab="general">
            <div class="zask-settings-grid">
                
                <!-- Enable/Disable Gate -->
                <div class="zask-card">
                    <div class="zask-card-header">
                        <h3><?php _e('Gate Status', 'zask-age-gate'); ?></h3>
                    </div>
                    <div class="zask-card-body">
                        <div class="zask-toggle-field">
                            <label class="zask-switch">
                                <input type="checkbox" name="zask_gate_enabled" <?php checked(get_option('zask_gate_enabled'), '1'); ?>>
                                <span class="zask-switch-slider"></span>
                            </label>
                            <div class="zask-toggle-label">
                                <strong><?php _e('Enable Age Gate', 'zask-age-gate'); ?></strong>
                                <p><?php _e('Turn on age verification for your website', 'zask-age-gate'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Site Information -->
                <div class="zask-card">
                    <div class="zask-card-header">
                        <h3><?php _e('Site Information', 'zask-age-gate'); ?></h3>
                    </div>
                    <div class="zask-card-body">
                        <div class="zask-info-grid">
                            <div class="zask-info-item">
                                <label><?php _e('Domain', 'zask-age-gate'); ?></label>
                                <strong><?php echo parse_url(get_site_url(), PHP_URL_HOST); ?></strong>
                            </div>
                            <div class="zask-info-item">
                                <label><?php _e('WordPress', 'zask-age-gate'); ?></label>
                                <strong><?php echo get_bloginfo('version'); ?></strong>
                            </div>
                            <div class="zask-info-item">
                                <label><?php _e('PHP', 'zask-age-gate'); ?></label>
                                <strong><?php echo PHP_VERSION; ?></strong>
                            </div>
                            <div class="zask-info-item">
                                <label><?php _e('WooCommerce', 'zask-age-gate'); ?></label>
                                <strong><?php echo class_exists('WooCommerce') ? WC()->version : __('Not installed', 'zask-age-gate'); ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Logo Upload -->
                <div class="zask-card zask-card-wide">
                    <div class="zask-card-header">
                        <h3><?php _e('Gate Logo', 'zask-age-gate'); ?></h3>
                        <p><?php _e('Upload a custom logo for your age gate', 'zask-age-gate'); ?></p>
                    </div>
                    <div class="zask-card-body">
                        <?php
                        $logo_id = get_option('zask_gate_logo_id');
                        $logo_url = $logo_id ? wp_get_attachment_url($logo_id) : '';
                        ?>
                        
                        <div class="zask-logo-upload">
                            <div class="zask-logo-preview" style="<?php echo $logo_url ? '' : 'display:none;'; ?>">
                                <img src="<?php echo esc_url($logo_url); ?>" alt="Logo Preview" style="max-width: 300px; max-height: 150px; border: 2px solid #e5e7eb; border-radius: 8px; padding: 10px; background: white;">
                            </div>
                            
                            <input type="hidden" id="zask_gate_logo_id" name="zask_gate_logo_id" value="<?php echo esc_attr($logo_id); ?>">
                            
                            <div class="zask-logo-buttons">
                                <button type="button" class="button button-primary" id="zask-upload-logo">
                                    <span class="dashicons dashicons-upload"></span>
                                    <?php _e('Upload Logo', 'zask-age-gate'); ?>
                                </button>
                                <button type="button" class="button" id="zask-remove-logo" style="<?php echo $logo_url ? '' : 'display:none;'; ?>">
                                    <span class="dashicons dashicons-no"></span>
                                    <?php _e('Remove Logo', 'zask-age-gate'); ?>
                                </button>
                            </div>
                            
                            <small style="display: block; margin-top: 10px;">
                                <?php _e('Recommended: 300x80px PNG with transparent background', 'zask-age-gate'); ?>
                            </small>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
        
        <!-- Gate Configuration Tab -->
        <div class="zask-tab-content" data-tab="gate">
            <div class="zask-settings-grid">
                
                <!-- Gate Stage -->
                <div class="zask-card zask-card-wide">
                    <div class="zask-card-header">
                        <h3><?php _e('Gate Stage', 'zask-age-gate'); ?></h3>
                        <p><?php _e('Choose the verification level required', 'zask-age-gate'); ?></p>
                    </div>
                    <div class="zask-card-body">
                        <div class="zask-stage-selector">
                            <?php
                            $stages = array(
                                'stage0' => array(
                                    'name' => __('Stage 0: No Gate', 'zask-age-gate'),
                                    'description' => __('Unrestricted access - No verification required', 'zask-age-gate'),
                                    'icon' => '🔓',
                                    'features' => array(__('No restrictions', 'zask-age-gate'), __('No compliance tracking', 'zask-age-gate'))
                                ),
                                'stage1' => array(
                                    'name' => __('Stage 1: Lightweight', 'zask-age-gate'),
                                    'description' => __('Simple age attestation with session cookie', 'zask-age-gate'),
                                    'icon' => '✅',
                                    'features' => array(__('Age confirmation', 'zask-age-gate'), __('Terms agreement', 'zask-age-gate'), __('Session-based', 'zask-age-gate'))
                                ),
                                'stage2' => array(
                                    'name' => __('Stage 2: Full Verification', 'zask-age-gate'),
                                    'description' => __('Login required with email verification', 'zask-age-gate'),
                                    'icon' => '🔐',
                                    'features' => array(__('User account required', 'zask-age-gate'), __('Email verification', 'zask-age-gate'), __('Database tracking', 'zask-age-gate'))
                                ),
                                'stage3' => array(
                                    'name' => __('Stage 3: Professional', 'zask-age-gate'),
                                    'description' => __('Business verification with credentials', 'zask-age-gate'),
                                    'icon' => '🏢',
                                    'features' => array(__('Professional credentials', 'zask-age-gate'), __('Business license', 'zask-age-gate'), __('Enhanced compliance', 'zask-age-gate'))
                                ),
                            );
                            
                            $current_stage = get_option('zask_gate_stage', 'stage1');
                            
                            foreach ($stages as $stage_key => $stage_data):
                            ?>
                            <label class="zask-stage-option <?php echo $stage_key === $current_stage ? 'active' : ''; ?>">
                                <input type="radio" name="zask_gate_stage" value="<?php echo esc_attr($stage_key); ?>" <?php checked($current_stage, $stage_key); ?>>
                                <div class="zask-stage-card">
                                    <div class="zask-stage-icon"><?php echo $stage_data['icon']; ?></div>
                                    <div class="zask-stage-info">
                                        <h4><?php echo $stage_data['name']; ?></h4>
                                        <p><?php echo $stage_data['description']; ?></p>
                                        <ul class="zask-stage-features">
                                            <?php foreach ($stage_data['features'] as $feature): ?>
                                                <li><?php echo $feature; ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Display Mode -->
                <div class="zask-card">
                    <div class="zask-card-header">
                        <h3><?php _e('Display Mode', 'zask-age-gate'); ?></h3>
                        <p><?php _e('How the age gate appears to visitors', 'zask-age-gate'); ?></p>
                    </div>
                    <div class="zask-card-body">
                        <?php $display_mode = get_option('zask_gate_display_mode', 'modal'); ?>
                        <div class="zask-radio-cards" style="grid-template-columns: 1fr 1fr;">
                            <label class="zask-radio-card <?php echo $display_mode === 'modal' ? 'selected' : ''; ?>">
                                <input type="radio" name="zask_gate_display_mode" value="modal" <?php checked($display_mode, 'modal'); ?>>
                                <div class="zask-radio-card-inner">
                                    <span class="zask-radio-icon">💬</span>
                                    <strong><?php _e('Popup Modal', 'zask-age-gate'); ?></strong>
                                    <p><?php _e('Overlay popup on top of a blurred background. Users can see the site behind the gate.', 'zask-age-gate'); ?></p>
                                </div>
                            </label>
                            <label class="zask-radio-card <?php echo $display_mode === 'fullpage' ? 'selected' : ''; ?>">
                                <input type="radio" name="zask_gate_display_mode" value="fullpage" <?php checked($display_mode, 'fullpage'); ?>>
                                <div class="zask-radio-card-inner">
                                    <span class="zask-radio-icon">🔒</span>
                                    <strong><?php _e('Full Page Block', 'zask-age-gate'); ?></strong>
                                    <p><?php _e('Completely blocks the page. Clean, minimal form centered on screen. No site content visible.', 'zask-age-gate'); ?></p>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Age Requirement -->
                <div class="zask-card">
                    <div class="zask-card-header">
                        <h3><?php _e('Age Requirement', 'zask-age-gate'); ?></h3>
                    </div>
                    <div class="zask-card-body">
                        <div class="zask-form-field">
                            <label><?php _e('Minimum Age', 'zask-age-gate'); ?></label>
                            <select name="zask_minimum_age">
                                <?php for ($age = 16; $age <= 25; $age++): ?>
                                    <option value="<?php echo $age; ?>" <?php selected(get_option('zask_minimum_age', 21), $age); ?>><?php echo $age; ?>+</option>
                                <?php endfor; ?>
                            </select>
                            <small><?php _e('Default age requirement (may be overridden by state laws)', 'zask-age-gate'); ?></small>
                        </div>
                        
                        <div class="zask-toggle-field">
                            <label class="zask-switch">
                                <input type="checkbox" name="zask_require_terms" <?php checked(get_option('zask_require_terms'), '1'); ?>>
                                <span class="zask-switch-slider"></span>
                            </label>
                            <div class="zask-toggle-label">
                                <strong><?php _e('Require Terms Agreement', 'zask-age-gate'); ?></strong>
                                <p><?php _e('Users must agree to RUO terms', 'zask-age-gate'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
        
        <!-- Session Management Tab -->
        <div class="zask-tab-content" data-tab="session">
            <div class="zask-settings-grid">
                
                <div class="zask-card zask-card-wide">
                    <div class="zask-card-header">
                        <h3><?php _e('Session Settings', 'zask-age-gate'); ?></h3>
                        <p><?php _e('Control how long users stay logged in', 'zask-age-gate'); ?></p>
                    </div>
                    <div class="zask-card-body">
                        <div class="zask-form-field">
                            <label><?php _e('Session Duration (Minutes)', 'zask-age-gate'); ?></label>
                            <input type="number" name="zask_session_duration" value="<?php echo esc_attr(get_option('zask_session_duration', '120')); ?>" min="5" max="43200">
                            <small><?php _e('How long users stay verified (5-43200 minutes). Default: 120 minutes (2 hours)', 'zask-age-gate'); ?></small>
                        </div>
                        
                        <div class="zask-toggle-field">
                            <label class="zask-switch">
                                <input type="checkbox" name="zask_logout_on_browser_close" <?php checked(get_option('zask_logout_on_browser_close'), '1'); ?>>
                                <span class="zask-switch-slider"></span>
                            </label>
                            <div class="zask-toggle-label">
                                <strong><?php _e('Logout on Browser Close', 'zask-age-gate'); ?></strong>
                                <p><?php _e('Session expires when browser is closed (overrides session duration)', 'zask-age-gate'); ?></p>
                            </div>
                        </div>
                        
                        <div class="zask-info-box">
                            <span class="dashicons dashicons-info"></span>
                            <div>
                                <strong><?php _e('Security Recommendation:', 'zask-age-gate'); ?></strong>
                                <p><?php _e('For high-compliance sites, enable "Logout on Browser Close" to ensure users re-verify each browsing session.', 'zask-age-gate'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
        
        <!-- Geographic Compliance Tab -->
        <div class="zask-tab-content" data-tab="geo">
            <div class="zask-settings-grid">
                
                <div class="zask-card">
                    <div class="zask-card-header">
                        <h3><?php _e('Geographic Compliance', 'zask-age-gate'); ?></h3>
                    </div>
                    <div class="zask-card-body">
                        <div class="zask-toggle-field">
                            <label class="zask-switch">
                                <input type="checkbox" name="zask_geo_compliance_enabled" <?php checked(get_option('zask_geo_compliance_enabled'), '1'); ?>>
                                <span class="zask-switch-slider"></span>
                            </label>
                            <div class="zask-field-label">
                                <strong><?php _e('Enable Geographic Compliance', 'zask-age-gate'); ?></strong>
                                <p><?php _e('Automatically adjust age requirements based on user location', 'zask-age-gate'); ?></p>
                            </div>
                        </div>
                        
                        <div class="zask-info-box">
                            <span class="dashicons dashicons-info"></span>
                            <div>
                                <strong><?php _e('How Geographic Compliance Works:', 'zask-age-gate'); ?></strong>
                                <p><?php _e('Detects VISITOR location (not store location) via IP address and adjusts age requirements automatically. States with 21+ laws: NY, NJ, CA, MA, IL, VA, TX, NH. Other states use your configured minimum age. This helps you stay compliant across all jurisdictions.', 'zask-age-gate'); ?></p>
                                <p><strong><?php _e('Example:', 'zask-age-gate'); ?></strong> <?php _e('Visitor from New York sees 21+, visitor from Florida sees 18+ (if your min age is 18).', 'zask-age-gate'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
        
        <!-- Terms & Content Tab -->
        <div class="zask-tab-content" data-tab="terms">
            <div class="zask-settings-grid">
                
                <!-- Welcome / Header Wording -->
                <div class="zask-card zask-card-wide">
                    <div class="zask-card-header">
                        <h3><?php _e('Welcome / Header Wording', 'zask-age-gate'); ?></h3>
                        <p><?php _e('Customize the main heading and subtitle shown on the age gate', 'zask-age-gate'); ?></p>
                    </div>
                    <div class="zask-card-body">
                        <div class="zask-form-group">
                            <label><strong><?php _e('Welcome Message (Title)', 'zask-age-gate'); ?></strong></label>
                            <input type="text" name="zask_welcome_message" value="<?php echo esc_attr(get_option('zask_welcome_message', 'Age Verification Required')); ?>" style="width: 100%;">
                            <p class="description"><?php _e('Main heading on the gate. Default: "Age Verification Required"', 'zask-age-gate'); ?></p>
                        </div>
                        
                        <div class="zask-form-group">
                            <label><strong><?php _e('Welcome Subtitle', 'zask-age-gate'); ?></strong></label>
                            <input type="text" name="zask_welcome_subtitle" value="<?php echo esc_attr(get_option('zask_welcome_subtitle', '')); ?>" style="width: 100%;">
                            <p class="description"><?php _e('Smaller text below the heading. Leave empty to hide.', 'zask-age-gate'); ?></p>
                        </div>
                        
                        <div class="zask-form-group">
                            <label><strong><?php _e('Intro Text (Stage 1)', 'zask-age-gate'); ?></strong></label>
                            <input type="text" name="zask_intro_text" value="<?php echo esc_attr(get_option('zask_intro_text', 'You must be {age} or older to access this website.')); ?>" style="width: 100%;">
                            <p class="description"><?php _e('Use <code>{age}</code> as placeholder for the minimum age number.', 'zask-age-gate'); ?></p>
                        </div>

                        <div class="zask-form-group">
                            <label><strong><?php _e('Footer Text (Stage 1)', 'zask-age-gate'); ?></strong></label>
                            <input type="text" name="zask_footer_text" value="<?php echo esc_attr(get_option('zask_footer_text', 'By entering, you certify that you meet the age requirements.')); ?>" style="width: 100%;">
                            <p class="description"><?php _e('Small text at the bottom of the Stage 1 form. Leave empty to hide.', 'zask-age-gate'); ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Checkbox Wording -->
                <div class="zask-card zask-card-wide">
                    <div class="zask-card-header">
                        <h3><?php _e('Checkbox Wording', 'zask-age-gate'); ?></h3>
                        <p><?php _e('Customize the text for every built-in checkbox on the age gate', 'zask-age-gate'); ?></p>
                    </div>
                    <div class="zask-card-body">
                        <div class="zask-form-group">
                            <label><strong><?php _e('Age Confirmation Checkbox', 'zask-age-gate'); ?></strong></label>
                            <input type="text" name="zask_age_checkbox_label" value="<?php echo esc_attr(get_option('zask_age_checkbox_label', 'I am {age} years of age or older')); ?>" style="width: 100%;">
                            <p class="description"><?php _e('Use <code>{age}</code> as placeholder for the minimum age. Default: "I am {age} years of age or older"', 'zask-age-gate'); ?></p>
                        </div>

                        <div class="zask-form-group">
                            <label><strong><?php _e('Terms Checkbox Label', 'zask-age-gate'); ?></strong></label>
                            <input type="text" name="zask_terms_checkbox_label" value="<?php echo esc_attr(get_option('zask_terms_checkbox_label', 'I agree to the terms and conditions')); ?>" style="width: 100%;">
                            <p class="description"><?php _e('Text next to the terms agreement checkbox. Default: "I agree to the terms and conditions"', 'zask-age-gate'); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Terms & Conditions Body -->
                <div class="zask-card zask-card-wide">
                    <div class="zask-card-header">
                        <h3><?php _e('Terms & Conditions', 'zask-age-gate'); ?></h3>
                        <p><?php _e('The full terms text shown below the terms checkbox', 'zask-age-gate'); ?></p>
                    </div>
                    <div class="zask-card-body">
                        <div class="zask-form-group">
                            <label><strong><?php _e('Terms Text', 'zask-age-gate'); ?></strong></label>
                            <textarea name="zask_terms_text" rows="8" style="width: 100%;"><?php echo esc_textarea(get_option('zask_terms_text', 'I agree to the Research Use Only terms and conditions.')); ?></textarea>
                            <p class="description"><?php _e('The full terms text displayed below the checkbox. Supports multiple lines.', 'zask-age-gate'); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Default Auth Tab (Stage 2 & 3) -->
                <div class="zask-card zask-card-wide">
                    <div class="zask-card-header">
                        <h3><?php _e('Default Auth Tab', 'zask-age-gate'); ?></h3>
                        <p><?php _e('Which tab should be active by default on the age gate (Stage 2 & 3)', 'zask-age-gate'); ?></p>
                    </div>
                    <div class="zask-card-body">
                        <?php $default_tab = get_option('zask_default_auth_tab', 'login'); ?>
                        <div class="zask-radio-cards" style="grid-template-columns: 1fr 1fr;">
                            <label class="zask-radio-card <?php echo $default_tab === 'login' ? 'selected' : ''; ?>">
                                <input type="radio" name="zask_default_auth_tab" value="login" <?php checked($default_tab, 'login'); ?>>
                                <div class="zask-radio-card-inner">
                                    <span class="zask-radio-icon">🔓</span>
                                    <strong><?php _e('Login First', 'zask-age-gate'); ?></strong>
                                    <p><?php _e('Show the Login tab by default. Best for sites with mostly returning customers.', 'zask-age-gate'); ?></p>
                                </div>
                            </label>
                            <label class="zask-radio-card <?php echo $default_tab === 'register' ? 'selected' : ''; ?>">
                                <input type="radio" name="zask_default_auth_tab" value="register" <?php checked($default_tab, 'register'); ?>>
                                <div class="zask-radio-card-inner">
                                    <span class="zask-radio-icon">📝</span>
                                    <strong><?php _e('Sign Up First', 'zask-age-gate'); ?></strong>
                                    <p><?php _e('Show the Sign Up tab by default. Best for new stores wanting to grow their customer base.', 'zask-age-gate'); ?></p>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Button Labels -->
                <div class="zask-card zask-card-wide">
                    <div class="zask-card-header">
                        <h3><?php _e('Button Labels', 'zask-age-gate'); ?></h3>
                        <p><?php _e('Customize the text on buttons', 'zask-age-gate'); ?></p>
                    </div>
                    <div class="zask-card-body">
                        <div class="zask-form-group" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                            <div>
                                <label><strong><?php _e('Enter Site Button (Stage 1)', 'zask-age-gate'); ?></strong></label>
                                <input type="text" name="zask_btn_enter" value="<?php echo esc_attr(get_option('zask_btn_enter', 'Enter Site')); ?>" style="width: 100%;">
                            </div>
                            <div>
                                <label><strong><?php _e('Create Account Button', 'zask-age-gate'); ?></strong></label>
                                <input type="text" name="zask_btn_register" value="<?php echo esc_attr(get_option('zask_btn_register', 'Create Account')); ?>" style="width: 100%;">
                            </div>
                            <div>
                                <label><strong><?php _e('Login Button', 'zask-age-gate'); ?></strong></label>
                                <input type="text" name="zask_btn_login" value="<?php echo esc_attr(get_option('zask_btn_login', 'Login')); ?>" style="width: 100%;">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Registration Password Mode -->
                <div class="zask-card zask-card-wide">
                    <div class="zask-card-header">
                        <h3><?php _e('Registration Password Mode', 'zask-age-gate'); ?></h3>
                        <p><?php _e('Control how passwords are handled during user registration (Stage 2 & 3)', 'zask-age-gate'); ?></p>
                    </div>
                    <div class="zask-card-body">
                        <?php $pw_mode = get_option('zask_password_mode', 'user_set'); ?>
                        
                        <div class="zask-radio-cards">
                            <label class="zask-radio-card <?php echo $pw_mode === 'user_set' ? 'selected' : ''; ?>">
                                <input type="radio" name="zask_password_mode" value="user_set" <?php checked($pw_mode, 'user_set'); ?>>
                                <div class="zask-radio-card-inner">
                                    <span class="zask-radio-icon">🔑</span>
                                    <strong><?php _e('User Sets Password', 'zask-age-gate'); ?></strong>
                                    <p><?php _e('Password field is visible on the registration form. User chooses their own password. This is the current default behaviour.', 'zask-age-gate'); ?></p>
                                </div>
                            </label>
                            
                            <label class="zask-radio-card <?php echo $pw_mode === 'set_password_link' ? 'selected' : ''; ?>">
                                <input type="radio" name="zask_password_mode" value="set_password_link" <?php checked($pw_mode, 'set_password_link'); ?>>
                                <div class="zask-radio-card-inner">
                                    <span class="zask-radio-icon">✉️</span>
                                    <strong><?php _e('Email Set-Password Link', 'zask-age-gate'); ?></strong>
                                    <p><?php _e('Password field is hidden. After registration, the user receives an email with a secure link to set their own password.', 'zask-age-gate'); ?></p>
                                </div>
                            </label>
                            
                            <label class="zask-radio-card <?php echo $pw_mode === 'temp_password' ? 'selected' : ''; ?>">
                                <input type="radio" name="zask_password_mode" value="temp_password" <?php checked($pw_mode, 'temp_password'); ?>>
                                <div class="zask-radio-card-inner">
                                    <span class="zask-radio-icon">🔐</span>
                                    <strong><?php _e('Send Temporary Password', 'zask-age-gate'); ?></strong>
                                    <p><?php _e('Password field is hidden. A temporary password is auto-generated and emailed to the user. They can change it after logging in.', 'zask-age-gate'); ?></p>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
        
        <!-- Appearance Tab -->
        <div class="zask-tab-content" data-tab="appearance">
            <div class="zask-settings-grid">
                
                <div class="zask-card">
                    <div class="zask-card-header">
                        <h3><?php _e('Form Dimensions', 'zask-age-gate'); ?></h3>
                    </div>
                    <div class="zask-card-body">
                        <div class="zask-form-field">
                            <label><?php _e('Form Max Width (px)', 'zask-age-gate'); ?></label>
                            <input type="number" name="zask_style_form_width" value="<?php echo esc_attr(get_option('zask_style_form_width', '500')); ?>" min="300" max="900" step="10">
                            <small><?php _e('Default: 500px. Range 300–900.', 'zask-age-gate'); ?></small>
                        </div>
                        <div class="zask-form-field">
                            <label><?php _e('Form Border Radius (px)', 'zask-age-gate'); ?></label>
                            <input type="number" name="zask_style_form_radius" value="<?php echo esc_attr(get_option('zask_style_form_radius', '20')); ?>" min="0" max="40" step="1">
                            <small><?php _e('Default: 20px. How rounded the corners are.', 'zask-age-gate'); ?></small>
                        </div>
                    </div>
                </div>
                
                <div class="zask-card">
                    <div class="zask-card-header">
                        <h3><?php _e('Colors', 'zask-age-gate'); ?></h3>
                    </div>
                    <div class="zask-card-body">
                        <div class="zask-form-field">
                            <label><?php _e('Primary Button Color', 'zask-age-gate'); ?></label>
                            <input type="color" name="zask_style_btn_color" value="<?php echo esc_attr(get_option('zask_style_btn_color', '#667eea')); ?>" class="zask-color-picker">
                            <small><?php _e('Used for buttons and toggle slider.', 'zask-age-gate'); ?></small>
                        </div>
                        <div class="zask-form-field">
                            <label><?php _e('Primary Button Gradient End', 'zask-age-gate'); ?></label>
                            <input type="color" name="zask_style_btn_color_end" value="<?php echo esc_attr(get_option('zask_style_btn_color_end', '#764ba2')); ?>" class="zask-color-picker">
                            <small><?php _e('Second color for the button gradient. Set same as above for a flat color.', 'zask-age-gate'); ?></small>
                        </div>
                        <div class="zask-form-field">
                            <label><?php _e('Button Text Color', 'zask-age-gate'); ?></label>
                            <input type="color" name="zask_style_btn_text" value="<?php echo esc_attr(get_option('zask_style_btn_text', '#ffffff')); ?>" class="zask-color-picker">
                        </div>
                        <div class="zask-form-field">
                            <label><?php _e('Form Background Color', 'zask-age-gate'); ?></label>
                            <input type="color" name="zask_style_form_bg" value="<?php echo esc_attr(get_option('zask_style_form_bg', '#ffffff')); ?>" class="zask-color-picker">
                        </div>
                        <div class="zask-form-field">
                            <label><?php _e('Form Text Color', 'zask-age-gate'); ?></label>
                            <input type="color" name="zask_style_form_text" value="<?php echo esc_attr(get_option('zask_style_form_text', '#111827')); ?>" class="zask-color-picker">
                        </div>
                        <div class="zask-form-field">
                            <label><?php _e('Backdrop Overlay Color', 'zask-age-gate'); ?></label>
                            <input type="color" name="zask_style_backdrop" value="<?php echo esc_attr(get_option('zask_style_backdrop', '#000000')); ?>" class="zask-color-picker">
                        </div>
                        <div class="zask-form-field">
                            <label><?php _e('Backdrop Opacity (%)', 'zask-age-gate'); ?></label>
                            <input type="number" name="zask_style_backdrop_opacity" value="<?php echo esc_attr(get_option('zask_style_backdrop_opacity', '85')); ?>" min="0" max="100" step="5">
                            <small><?php _e('Default: 85%. 0 = fully transparent, 100 = fully opaque.', 'zask-age-gate'); ?></small>
                        </div>
                    </div>
                </div>
                
                <div class="zask-card zask-card-wide">
                    <div class="zask-card-header">
                        <h3><?php _e('Live Preview', 'zask-age-gate'); ?></h3>
                        <p><?php _e('A rough preview of your color choices. Save settings and refresh the front end to see the real result.', 'zask-age-gate'); ?></p>
                    </div>
                    <div class="zask-card-body">
                        <div id="zask-appearance-preview" style="padding:30px; border-radius:12px; border:1px solid #e5e7eb; text-align:center;">
                            <div style="font-weight:700; font-size:18px; margin-bottom:12px;" class="zask-preview-text">Age Verification Required</div>
                            <div style="margin-bottom:16px; font-size:14px;" class="zask-preview-text">You must be 21 or older to access this website.</div>
                            <button type="button" class="zask-preview-btn" style="padding:12px 40px; border:none; border-radius:10px; font-size:15px; font-weight:600; cursor:default;">Enter Site</button>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
        
        <!-- Form Builder Tab -->
        <div class="zask-tab-content" data-tab="form-builder">
            <div class="zask-settings-grid">
                
                <!-- Custom Fields Section -->
                <div class="zask-card zask-card-wide">
                    <div class="zask-card-header">
                        <h3><?php _e('Custom Registration Fields', 'zask-age-gate'); ?></h3>
                        <p><?php _e('Add custom text or number fields to the registration form (Stage 2 & 3). Drag rows to reorder.', 'zask-age-gate'); ?></p>
                    </div>
                    <div class="zask-card-body">
                        
                        <div class="zask-fb-table-wrap">
                            <table class="zask-fb-table" id="zask-custom-fields-table">
                                <thead>
                                    <tr>
                                        <th class="zask-fb-col-drag"></th>
                                        <th><?php _e('Label', 'zask-age-gate'); ?></th>
                                        <th><?php _e('Placeholder', 'zask-age-gate'); ?></th>
                                        <th><?php _e('Type', 'zask-age-gate'); ?></th>
                                        <th><?php _e('Required', 'zask-age-gate'); ?></th>
                                        <th><?php _e('Show on Form', 'zask-age-gate'); ?></th>
                                        <th class="zask-fb-col-actions"></th>
                                    </tr>
                                </thead>
                                <tbody id="zask-custom-fields-body">
                                    <?php
                                    $custom_fields = get_option('zask_custom_fields', array());
                                    if (!empty($custom_fields)):
                                        foreach ($custom_fields as $index => $field):
                                    ?>
                                    <tr class="zask-fb-row" data-index="<?php echo $index; ?>">
                                        <td class="zask-fb-col-drag"><span class="dashicons dashicons-menu zask-fb-drag-handle"></span></td>
                                        <td><input type="text" class="zask-fb-input" data-key="label" value="<?php echo esc_attr($field['label']); ?>" placeholder="<?php esc_attr_e('Field label', 'zask-age-gate'); ?>"></td>
                                        <td><input type="text" class="zask-fb-input" data-key="placeholder" value="<?php echo esc_attr($field['placeholder'] ?? ''); ?>" placeholder="<?php esc_attr_e('Placeholder text', 'zask-age-gate'); ?>"></td>
                                        <td>
                                            <select class="zask-fb-select zask-fb-type-select" data-key="type">
                                                <option value="text" <?php selected($field['type'], 'text'); ?>><?php _e('Text', 'zask-age-gate'); ?></option>
                                                <option value="number" <?php selected($field['type'], 'number'); ?>><?php _e('Number', 'zask-age-gate'); ?></option>
                                                <option value="dropdown" <?php selected($field['type'], 'dropdown'); ?>><?php _e('Dropdown', 'zask-age-gate'); ?></option>
                                            </select>
                                        </td>
                                        <td class="zask-fb-col-center">
                                            <label class="zask-fb-toggle">
                                                <input type="checkbox" data-key="required" <?php checked(!empty($field['required'])); ?>>
                                                <span class="zask-fb-toggle-slider"></span>
                                            </label>
                                        </td>
                                        <td class="zask-fb-col-center">
                                            <label class="zask-fb-toggle">
                                                <input type="checkbox" data-key="enabled" <?php checked(!isset($field['enabled']) || $field['enabled']); ?>>
                                                <span class="zask-fb-toggle-slider"></span>
                                            </label>
                                        </td>
                                        <td class="zask-fb-col-actions">
                                            <button type="button" class="zask-fb-remove-row" title="<?php esc_attr_e('Remove', 'zask-age-gate'); ?>">
                                                <span class="dashicons dashicons-trash"></span>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr class="zask-fb-options-row" data-parent="<?php echo $index; ?>" style="<?php echo ($field['type'] ?? '') === 'dropdown' ? '' : 'display:none;'; ?>">
                                        <td></td>
                                        <td colspan="6">
                                            <div class="zask-fb-options-wrap">
                                                <label class="zask-fb-options-label"><?php _e('Dropdown Options (one per line):', 'zask-age-gate'); ?></label>
                                                <textarea class="zask-fb-options-textarea" data-key="options" rows="3" placeholder="<?php esc_attr_e("Option 1\nOption 2\nOption 3", 'zask-age-gate'); ?>"><?php echo esc_textarea($field['options'] ?? ''); ?></textarea>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php
                                        endforeach;
                                    endif;
                                    ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="zask-fb-empty-state" id="zask-fields-empty" style="<?php echo empty($custom_fields) ? '' : 'display:none;'; ?>">
                            <span class="dashicons dashicons-text-page"></span>
                            <p><?php _e('No custom fields yet. Click the button below to add one.', 'zask-age-gate'); ?></p>
                        </div>
                        
                        <button type="button" class="button" id="zask-add-custom-field" style="margin-top: 15px;">
                            <span class="dashicons dashicons-plus-alt2"></span>
                            <?php _e('Add Custom Field', 'zask-age-gate'); ?>
                        </button>
                        
                        <div class="zask-info-box" style="margin-top: 20px;">
                            <span class="dashicons dashicons-info"></span>
                            <div>
                                <strong><?php _e('How Custom Fields Work', 'zask-age-gate'); ?></strong>
                                <p><?php _e('Custom fields appear on the Sign Up form for Stage 2 and Stage 3 gates. Field values are saved with the user\'s compliance record. Use "Show on Form" to temporarily hide a field without deleting it.', 'zask-age-gate'); ?></p>
                            </div>
                        </div>
                        
                    </div>
                </div>
                
                <!-- Custom Checkboxes Section -->
                <div class="zask-card zask-card-wide">
                    <div class="zask-card-header">
                        <h3><?php _e('Custom Checkboxes', 'zask-age-gate'); ?></h3>
                        <p><?php _e('Add, edit, or remove checkboxes shown on the age gate. These appear on all stages. Drag rows to reorder.', 'zask-age-gate'); ?></p>
                    </div>
                    <div class="zask-card-body">
                        
                        <div class="zask-fb-table-wrap">
                            <table class="zask-fb-table" id="zask-custom-checkboxes-table">
                                <thead>
                                    <tr>
                                        <th class="zask-fb-col-drag"></th>
                                        <th><?php _e('Checkbox Label', 'zask-age-gate'); ?></th>
                                        <th><?php _e('Required', 'zask-age-gate'); ?></th>
                                        <th><?php _e('Show on Form', 'zask-age-gate'); ?></th>
                                        <th class="zask-fb-col-actions"></th>
                                    </tr>
                                </thead>
                                <tbody id="zask-custom-checkboxes-body">
                                    <?php
                                    $custom_checkboxes = get_option('zask_custom_checkboxes', array());
                                    if (!empty($custom_checkboxes)):
                                        foreach ($custom_checkboxes as $index => $cb):
                                    ?>
                                    <tr class="zask-fb-row" data-index="<?php echo $index; ?>">
                                        <td class="zask-fb-col-drag"><span class="dashicons dashicons-menu zask-fb-drag-handle"></span></td>
                                        <td><input type="text" class="zask-fb-input zask-fb-input-wide" data-key="label" value="<?php echo esc_attr($cb['label']); ?>" placeholder="<?php esc_attr_e('Checkbox label text', 'zask-age-gate'); ?>"></td>
                                        <td class="zask-fb-col-center">
                                            <label class="zask-fb-toggle">
                                                <input type="checkbox" data-key="required" <?php checked(!empty($cb['required'])); ?>>
                                                <span class="zask-fb-toggle-slider"></span>
                                            </label>
                                        </td>
                                        <td class="zask-fb-col-center">
                                            <label class="zask-fb-toggle">
                                                <input type="checkbox" data-key="enabled" <?php checked(!isset($cb['enabled']) || $cb['enabled']); ?>>
                                                <span class="zask-fb-toggle-slider"></span>
                                            </label>
                                        </td>
                                        <td class="zask-fb-col-actions">
                                            <button type="button" class="zask-fb-remove-row" title="<?php esc_attr_e('Remove', 'zask-age-gate'); ?>">
                                                <span class="dashicons dashicons-trash"></span>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php
                                        endforeach;
                                    endif;
                                    ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="zask-fb-empty-state" id="zask-checkboxes-empty" style="<?php echo empty($custom_checkboxes) ? '' : 'display:none;'; ?>">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <p><?php _e('No custom checkboxes yet. Click the button below to add one.', 'zask-age-gate'); ?></p>
                        </div>
                        
                        <button type="button" class="button" id="zask-add-custom-checkbox" style="margin-top: 15px;">
                            <span class="dashicons dashicons-plus-alt2"></span>
                            <?php _e('Add Checkbox', 'zask-age-gate'); ?>
                        </button>
                        
                        <div class="zask-info-box" style="margin-top: 20px;">
                            <span class="dashicons dashicons-info"></span>
                            <div>
                                <strong><?php _e('How Custom Checkboxes Work', 'zask-age-gate'); ?></strong>
                                <p><?php _e('Custom checkboxes appear below the built-in age confirmation and terms checkboxes. On Stage 1 they appear in the age gate popup; on Stage 2 & 3 they appear on the Sign Up form. Required checkboxes must be checked before the user can proceed.', 'zask-age-gate'); ?></p>
                            </div>
                        </div>
                        
                    </div>
                </div>
                
            </div>
        </div>
        
        <!-- License Tab -->
        <div class="zask-tab-content" data-tab="license">
            <div class="zask-settings-grid">
                
                <div class="zask-card">
                    <div class="zask-card-header">
                        <h3><?php _e('License Activation', 'zask-age-gate'); ?></h3>
                    </div>
                    <div class="zask-card-body">
                        
                        <?php if ($license_status === 'active'): ?>
                            <div class="zask-success-box">
                                <span class="dashicons dashicons-yes-alt"></span>
                                <div>
                                    <strong><?php _e('License Active!', 'zask-age-gate'); ?></strong>
                                    <p><?php _e('Your license is activated and working properly.', 'zask-age-gate'); ?></p>
                                </div>
                            </div>
                            
                            <div class="zask-form-group">
                                <label><strong><?php _e('License Key', 'zask-age-gate'); ?></strong></label>
                                <input type="text" value="<?php echo esc_attr(get_option('zask_license_key', '')); ?>" disabled style="width: 100%; background: #f0f0f0;">
                            </div>
                            
                            <div class="zask-form-group">
                                <label><strong><?php _e('Tier', 'zask-age-gate'); ?></strong></label>
                                <input type="text" value="<?php echo esc_attr(ucfirst($license_tier)); ?>" disabled style="width: 200px; background: #f0f0f0;">
                            </div>
                            
                            <div class="zask-form-group">
                                <label><strong><?php _e('Expires', 'zask-age-gate'); ?></strong></label>
                                <input type="text" value="<?php echo esc_attr(get_option('zask_license_expires', 'Never')); ?>" disabled style="width: 200px; background: #f0f0f0;">
                            </div>
                            
                        <?php else: ?>
                            <div class="zask-warning-box">
                                <span class="dashicons dashicons-warning"></span>
                                <div>
                                    <strong><?php _e('License Required!', 'zask-age-gate'); ?></strong>
                                    <p><?php _e('The age gate will NOT function without an active license. Please enter your license key below.', 'zask-age-gate'); ?></p>
                                </div>
                            </div>
                            
                            <div class="zask-form-group">
                                <label><strong><?php _e('License Key', 'zask-age-gate'); ?></strong></label>
                                <input type="text" name="zask_license_key" id="zask-license-key-input" placeholder="AGEGATE-XXXX-XXXX-XXXX-XXXX" style="width: 100%; font-family: monospace; font-size: 14px;">
                                <p class="description"><?php _e('Enter the license key you received after purchase.', 'zask-age-gate'); ?></p>
                            </div>
                            
                            <div class="zask-license-status" style="margin-bottom: 15px;"></div>
                            
                            <button type="button" id="zask-activate-license" class="button button-primary">
                                <span class="dashicons dashicons-admin-network"></span>
                                <?php _e('Activate License', 'zask-age-gate'); ?>
                            </button>
                        <?php endif; ?>
                        
                        <div class="zask-info-box" style="margin-top: 20px;">
                            <span class="dashicons dashicons-info"></span>
                            <div>
                                <strong><?php _e('Need a License?', 'zask-age-gate'); ?></strong>
                                <p><?php _e('Purchase a license at zask.it. You will receive your license key via email immediately after purchase.', 'zask-age-gate'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
        
        <!-- Save Button (Fixed at bottom) -->
        <div class="zask-save-bar">
            <button type="button" id="zask-save-settings" class="button button-primary button-hero">
                <span class="dashicons dashicons-yes"></span>
                <?php _e('Save All Settings', 'zask-age-gate'); ?>
            </button>
            
            <div class="zask-save-status"></div>
        </div>
        
    </div>
</div>
