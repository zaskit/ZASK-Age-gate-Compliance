<?php
/**
 * Gate Modal Template
 * Popup overlay for age verification
 *
 * @package ZASK_Age_Gate
 */

if (!defined('ABSPATH')) exit;

$stage = get_option('zask_gate_stage', 'stage1');
$minimum_age = ZASK_Geo_Compliance::get_minimum_age();
$state_message = ZASK_Geo_Compliance::get_state_message();
?>

<div id="zask-gate-modal" class="zask-gate-modal zask-gate-<?php echo esc_attr($stage); ?>">
    <div class="zask-gate-backdrop"></div>
    
    <div class="zask-gate-container">
        <div class="zask-gate-header">
            <?php 
            $logo_id = get_option('zask_gate_logo_id');
            $logo_url = $logo_id ? wp_get_attachment_url($logo_id) : '';
            if ($logo_url): ?>
                <img src="<?php echo esc_url($logo_url); ?>" alt="<?php bloginfo('name'); ?>" class="zask-gate-logo">
            <?php else: ?>
                <div class="zask-gate-icon">🛡️</div>
            <?php endif; ?>
            <h2><?php echo esc_html(get_option('zask_welcome_message', __('Age Verification Required', 'zask-age-gate'))); ?></h2>
            <?php $welcome_sub = get_option('zask_welcome_subtitle', ''); if ($welcome_sub): ?>
                <p class="zask-gate-subtitle"><?php echo esc_html($welcome_sub); ?></p>
            <?php endif; ?>
        </div>
        
        <div class="zask-gate-body">
            <?php if ($stage === 'stage1'): ?>
                <!-- Stage 1: Simple Age + Terms -->
                <form id="zask-stage1-form" class="zask-gate-form">
                    <?php if ($state_message): ?>
                        <div class="zask-state-notice">
                            <?php echo esc_html($state_message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <p class="zask-gate-intro">
                        <?php
                        $intro = get_option('zask_intro_text', 'You must be {age} or older to access this website.');
                        echo esc_html(str_replace('{age}', $minimum_age, $intro));
                        ?>
                    </p>
                    
                    <div class="zask-form-group">
                        <label class="zask-checkbox-label">
                            <input type="checkbox" name="age_confirmed" required>
                            <span><?php
                                $age_label = get_option('zask_age_checkbox_label', 'I am {age} years of age or older');
                                echo esc_html(str_replace('{age}', $minimum_age, $age_label));
                            ?></span>
                        </label>
                    </div>
                    
                    <?php if (get_option('zask_require_terms') == '1'): ?>
                        <div class="zask-form-group">
                            <label class="zask-checkbox-label">
                                <input type="checkbox" name="terms_agreed" required>
                                <span><?php echo esc_html(get_option('zask_terms_checkbox_label', __('I agree to the terms and conditions', 'zask-age-gate'))); ?></span>
                            </label>
                            
                            <div class="zask-terms-preview">
                                <?php echo wpautop(wp_kses_post(get_option('zask_terms_text', __('These products are for research use only and not for human or veterinary use.', 'zask-age-gate')))); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php ZASK_Compliance_Engine::render_custom_checkboxes_html(); ?>
                    
                    <button type="submit" class="zask-btn zask-btn-primary">
                        <?php echo esc_html(get_option('zask_btn_enter', __('Enter Site', 'zask-age-gate'))); ?>
                    </button>
                    
                    <?php $footer_text = get_option('zask_footer_text', 'By entering, you certify that you meet the age requirements.'); if ($footer_text): ?>
                        <p class="zask-gate-footer">
                            <?php echo esc_html($footer_text); ?>
                        </p>
                    <?php endif; ?>
                </form>
                
            <?php elseif ($stage === 'stage2' || $stage === 'stage3'): ?>
                <!-- Stage 2/3: Login or Register -->
                <?php $pw_mode = get_option('zask_password_mode', 'user_set'); ?>
                <?php $default_auth = get_option('zask_default_auth_tab', 'login'); ?>
                <div class="zask-auth-toggle">
                    <div class="zask-toggle-switch">
                        <button class="zask-toggle-btn <?php echo $default_auth === 'login' ? 'active' : ''; ?>" data-form="login">
                            <?php echo esc_html(get_option('zask_btn_login', __('Login', 'zask-age-gate'))); ?>
                        </button>
                        <button class="zask-toggle-btn <?php echo $default_auth === 'register' ? 'active' : ''; ?>" data-form="register">
                            <?php _e('Sign Up', 'zask-age-gate'); ?>
                        </button>
                        <div class="zask-toggle-slider" <?php if ($default_auth === 'register') echo 'style="transform: translateX(100%)"'; ?>></div>
                    </div>
                </div>
                
                <!-- Login Form -->
                <form id="zask-login-form" class="zask-gate-form zask-auth-form <?php echo $default_auth === 'login' ? 'active' : ''; ?>">
                    <div class="zask-form-group">
                        <label for="login-email"><?php _e('Email or Username', 'zask-age-gate'); ?></label>
                        <input type="text" id="login-email" name="email" required placeholder="<?php esc_attr_e('your@email.com or username', 'zask-age-gate'); ?>">
                    </div>
                    
                    <div class="zask-form-group">
                        <label for="login-password"><?php _e('Password', 'zask-age-gate'); ?></label>
                        <div class="zask-password-wrapper">
                            <input type="password" id="login-password" name="password" required>
                            <button type="button" class="zask-password-toggle" data-target="login-password">
                                <span class="show-icon">👁️</span>
                                <span class="hide-icon" style="display:none;">🙈</span>
                            </button>
                        </div>
                    </div>
                    
                    <button type="submit" class="zask-btn zask-btn-primary">
                        <?php echo esc_html(get_option('zask_btn_login', __('Login', 'zask-age-gate'))); ?>
                    </button>
                    
                    <p class="zask-form-footer">
                        <button type="button" class="zask-btn-link" id="zask-show-reset">
                            <?php _e('Forgot Password?', 'zask-age-gate'); ?>
                        </button>
                    </p>
                </form>
                
                <!-- Password Reset Form -->
                <form id="zask-reset-form" class="zask-gate-form zask-auth-form" style="display:none;">
                    <div class="zask-form-group">
                        <label for="reset-email"><?php _e('Email Address', 'zask-age-gate'); ?></label>
                        <input type="email" id="reset-email" name="email" required placeholder="your@email.com">
                        <small><?php _e('Enter your email and we\'ll send you a password reset link', 'zask-age-gate'); ?></small>
                    </div>
                    
                    <button type="submit" class="zask-btn zask-btn-primary">
                        <?php _e('Send Reset Link', 'zask-age-gate'); ?>
                    </button>
                    
                    <p class="zask-form-footer">
                        <button type="button" class="zask-btn-link" id="zask-back-to-login">
                            <?php _e('← Back to Login', 'zask-age-gate'); ?>
                        </button>
                    </p>
                </form>
                
                <!-- Register Form -->
                <form id="zask-register-form" class="zask-gate-form zask-auth-form <?php echo $default_auth === 'register' ? 'active' : ''; ?>" data-pw-mode="<?php echo esc_attr($pw_mode); ?>">
                    <div class="zask-form-group">
                        <label for="register-name"><?php _e('Full Name', 'zask-age-gate'); ?></label>
                        <input type="text" id="register-name" name="full_name" required>
                    </div>
                    
                    <div class="zask-form-group">
                        <label for="register-email"><?php _e('Email Address', 'zask-age-gate'); ?></label>
                        <input type="email" id="register-email" name="email" required>
                    </div>
                    
                    <?php if ($pw_mode === 'user_set'): ?>
                        <div class="zask-form-group">
                            <label for="register-password"><?php _e('Password', 'zask-age-gate'); ?></label>
                            <div class="zask-password-wrapper">
                                <input type="password" id="register-password" name="password" required>
                                <button type="button" class="zask-password-toggle" data-target="register-password">
                                    <span class="show-icon">👁️</span>
                                    <span class="hide-icon" style="display:none;">🙈</span>
                                </button>
                            </div>
                            <small><?php _e('Minimum 8 characters', 'zask-age-gate'); ?></small>
                        </div>
                    <?php elseif ($pw_mode === 'set_password_link'): ?>
                        <div class="zask-pw-mode-notice">
                            <span class="zask-pw-mode-icon">✉️</span>
                            <?php _e('After signing up, you\'ll receive an email with a link to set your password.', 'zask-age-gate'); ?>
                        </div>
                    <?php elseif ($pw_mode === 'temp_password'): ?>
                        <div class="zask-pw-mode-notice">
                            <span class="zask-pw-mode-icon">🔐</span>
                            <?php _e('After signing up, a temporary password will be emailed to you.', 'zask-age-gate'); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($stage === 'stage3'): ?>
                        <div class="zask-form-group">
                            <label for="business-type"><?php _e('Business Type', 'zask-age-gate'); ?></label>
                            <select id="business-type" name="business_type" required>
                                <option value=""><?php _e('Select...', 'zask-age-gate'); ?></option>
                                <option value="research"><?php _e('Research/Academic', 'zask-age-gate'); ?></option>
                                <option value="healthcare"><?php _e('Healthcare/Medical', 'zask-age-gate'); ?></option>
                                <option value="biotech"><?php _e('Biotechnology', 'zask-age-gate'); ?></option>
                                <option value="pharma"><?php _e('Pharmaceutical', 'zask-age-gate'); ?></option>
                                <option value="other"><?php _e('Other', 'zask-age-gate'); ?></option>
                            </select>
                        </div>
                    <?php endif; ?>
                    
                    <?php ZASK_Compliance_Engine::render_custom_fields_html(); ?>
                    
                    <div class="zask-form-group">
                        <label class="zask-checkbox-label">
                            <input type="checkbox" name="age_confirmed" required>
                            <span><?php
                                $age_label = get_option('zask_age_checkbox_label', 'I am {age} years of age or older');
                                echo esc_html(str_replace('{age}', $minimum_age, $age_label));
                            ?></span>
                        </label>
                    </div>
                    
                    <?php if (get_option('zask_require_terms') == '1'): ?>
                        <div class="zask-form-group">
                            <label class="zask-checkbox-label">
                                <input type="checkbox" name="terms_agreed" required>
                                <span><?php echo esc_html(get_option('zask_terms_checkbox_label', __('I agree to the terms and conditions', 'zask-age-gate'))); ?></span>
                            </label>
                        </div>
                    <?php endif; ?>
                    
                    <?php ZASK_Compliance_Engine::render_custom_checkboxes_html(); ?>
                    
                    <button type="submit" class="zask-btn zask-btn-primary">
                        <?php echo esc_html(get_option('zask_btn_register', __('Create Account', 'zask-age-gate'))); ?>
                    </button>
                </form>
                
                <!-- Email Verification (shown after registration) -->
                <div id="zask-verification-form" class="zask-gate-form" style="display:none;">
                    <div class="zask-verification-notice">
                        <p><?php _e('Please check your email for a verification code.', 'zask-age-gate'); ?></p>
                    </div>
                    
                    <div class="zask-form-group">
                        <label for="verification-code"><?php _e('Verification Code', 'zask-age-gate'); ?></label>
                        <input type="text" id="verification-code" name="code" maxlength="6" required>
                    </div>
                    
                    <button type="button" id="zask-verify-btn" class="zask-btn zask-btn-primary">
                        <?php _e('Verify', 'zask-age-gate'); ?>
                    </button>
                    
                    <p class="zask-form-footer">
                        <button type="button" id="zask-resend-btn" class="zask-btn-link">
                            <?php _e('Resend Code', 'zask-age-gate'); ?>
                        </button>
                    </p>
                </div>
                
            <?php endif; ?>
            
            <div class="zask-gate-messages">
                <div class="zask-message zask-message-error" style="display:none;"></div>
                <div class="zask-message zask-message-success" style="display:none;"></div>
            </div>
        </div>
    </div>
</div>
