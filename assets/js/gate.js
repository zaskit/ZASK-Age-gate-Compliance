/**
 * ZASK Age-Gate Frontend JavaScript
 * Handles all gate interactions
 */

(function($) {
    'use strict';
    
    const ZASK_Gate = {
        
        init: function() {
            this.bindEvents();
            this.checkGateStatus();
        },
        
        bindEvents: function() {
            $(document).on('click', '.zask-toggle-btn', this.handleToggle);
            $(document).on('click', '.zask-password-toggle', this.togglePassword);
            $(document).on('submit', '#zask-stage1-form', this.handleStage1Submit);
            $(document).on('submit', '#zask-login-form', this.handleLogin);
            $(document).on('submit', '#zask-register-form', this.handleRegister);
            $(document).on('click', '#zask-show-reset', this.showResetForm);
            $(document).on('click', '#zask-back-to-login', this.backToLogin);
            $(document).on('submit', '#zask-reset-form', this.handlePasswordReset);
            $(document).on('click', '#zask-verify-btn', this.handleVerification);
            $(document).on('click', '#zask-resend-btn', this.resendVerification);
        },
        
        handleToggle: function(e) {
            e.preventDefault();
            const $btn = $(this);
            const form = $btn.data('form');
            
            $('.zask-toggle-btn').removeClass('active');
            $btn.addClass('active');
            
            $('.zask-auth-form').removeClass('active');
            if (form === 'login') {
                $('#zask-login-form').addClass('active');
                $('.zask-toggle-slider').css('transform', 'translateX(0)');
            } else {
                $('#zask-register-form').addClass('active');
                $('.zask-toggle-slider').css('transform', 'translateX(100%)');
            }
        },
        
        togglePassword: function(e) {
            e.preventDefault();
            const $btn = $(this);
            const targetId = $btn.data('target');
            const $input = $('#' + targetId);
            
            if ($input.attr('type') === 'password') {
                $input.attr('type', 'text');
                $btn.find('.show-icon').hide();
                $btn.find('.hide-icon').show();
            } else {
                $input.attr('type', 'password');
                $btn.find('.show-icon').show();
                $btn.find('.hide-icon').hide();
            }
        },
        
        /**
         * Stage 1: Simple checkbox verification
         * On success: redirect to current page (cookie is set server-side)
         */
        handleStage1Submit: function(e) {
            e.preventDefault();
            const $form = $(this);
            
            // Validate custom required checkboxes
            var invalid = false;
            $form.find('.zask-custom-checkbox[required]').each(function() {
                if (!$(this).is(':checked')) {
                    invalid = true;
                    $(this).closest('.zask-form-group').addClass('zask-field-error');
                } else {
                    $(this).closest('.zask-form-group').removeClass('zask-field-error');
                }
            });
            if (invalid) {
                ZASK_Gate.showError('Please check all required checkboxes.');
                return;
            }
            
            const data = {
                action: 'zask_verify_age',
                nonce: zaskGate.nonce,
                age_confirmed: $form.find('[name="age_confirmed"]').is(':checked'),
                terms_agreed: $form.find('[name="terms_agreed"]').is(':checked')
            };
            
            $form.find('.zask-custom-checkbox').each(function() {
                data[$(this).attr('name')] = $(this).is(':checked') ? 'true' : 'false';
            });
            
            ZASK_Gate.showLoading($form);
            
            $.post(zaskGate.ajaxurl, data, function(response) {
                if (response.success) {
                    // Redirect — cookie is now set, page will load without gate
                    window.location.reload();
                } else {
                    ZASK_Gate.showError(response.data.message);
                    ZASK_Gate.hideLoading($form);
                }
            }).fail(function() {
                ZASK_Gate.showError('Connection error. Please try again.');
                ZASK_Gate.hideLoading($form);
            });
        },
        
        /**
         * Stage 2/3: Login
         * On success: redirect (user is now logged in server-side)
         */
        handleLogin: function(e) {
            e.preventDefault();
            const $form = $(this);
            
            const data = {
                action: 'zask_login',
                nonce: zaskGate.nonce,
                email: $form.find('[name="email"]').val(),
                password: $form.find('[name="password"]').val()
            };
            
            ZASK_Gate.showLoading($form);
            
            $.post(zaskGate.ajaxurl, data, function(response) {
                if (response.success) {
                    // User is logged in — reload page, gate won't show
                    window.location.reload();
                } else {
                    ZASK_Gate.showError(response.data.message);
                    ZASK_Gate.hideLoading($form);
                }
            }).fail(function() {
                ZASK_Gate.showError('Connection error. Please try again.');
                ZASK_Gate.hideLoading($form);
            });
        },
        
        /**
         * Stage 2/3: Registration (all password modes)
         * On success: redirect (user is logged in server-side)
         */
        handleRegister: function(e) {
            e.preventDefault();
            const $form = $(this);
            const pwMode = $form.data('pw-mode') || 'user_set';
            
            // Validate custom required fields
            var invalid = false;
            $form.find('.zask-custom-field[required]').each(function() {
                if (!$(this).val().trim()) {
                    invalid = true;
                    $(this).closest('.zask-form-group').addClass('zask-field-error');
                } else {
                    $(this).closest('.zask-form-group').removeClass('zask-field-error');
                }
            });
            $form.find('.zask-custom-checkbox[required]').each(function() {
                if (!$(this).is(':checked')) {
                    invalid = true;
                    $(this).closest('.zask-form-group').addClass('zask-field-error');
                } else {
                    $(this).closest('.zask-form-group').removeClass('zask-field-error');
                }
            });
            if (invalid) {
                ZASK_Gate.showError('Please fill in all required fields and check all required checkboxes.');
                return;
            }
            
            const data = {
                action: 'zask_register',
                nonce: zaskGate.nonce,
                full_name: $form.find('[name="full_name"]').val(),
                email: $form.find('[name="email"]').val(),
                business_type: $form.find('[name="business_type"]').val() || '',
                age_confirmed: $form.find('[name="age_confirmed"]').is(':checked'),
                terms_agreed: $form.find('[name="terms_agreed"]').is(':checked')
            };
            
            if (pwMode === 'user_set') {
                data.password = $form.find('[name="password"]').val();
            }
            
            $form.find('.zask-custom-field').each(function() {
                data[$(this).attr('name')] = $(this).val();
            });
            
            $form.find('.zask-custom-checkbox').each(function() {
                data[$(this).attr('name')] = $(this).is(':checked') ? 'true' : 'false';
            });
            
            ZASK_Gate.showLoading($form);
            
            $.post(zaskGate.ajaxurl, data, function(response) {
                ZASK_Gate.hideLoading($form);
                
                if (response.success) {
                    if (response.data && response.data.requires_verification) {
                        $('.zask-auth-form').hide();
                        $('#zask-verification-form').show();
                        ZASK_Gate.showSuccess(response.data.message);
                    } else {
                        // User is logged in — reload page, gate won't show
                        window.location.reload();
                    }
                } else {
                    ZASK_Gate.showError(response.data ? response.data.message : 'Registration failed. Please try again.');
                }
            }).fail(function() {
                ZASK_Gate.hideLoading($form);
                ZASK_Gate.showError('Connection error. Please try again.');
            });
        },
        
        handleVerification: function(e) {
            e.preventDefault();
            window.location.reload();
        },
        
        resendVerification: function(e) {
            e.preventDefault();
            ZASK_Gate.showSuccess('Verification code resent!');
        },
        
        showResetForm: function(e) {
            e.preventDefault();
            $('#zask-login-form').removeClass('active').hide();
            $('#zask-reset-form').show();
            $('.zask-auth-toggle').fadeOut();
        },
        
        backToLogin: function(e) {
            e.preventDefault();
            $('#zask-reset-form').hide();
            $('#zask-login-form').addClass('active').fadeIn();
            $('.zask-auth-toggle').fadeIn();
            $('#reset-email').val('');
        },
        
        handlePasswordReset: function(e) {
            e.preventDefault();
            const $form = $(this);
            const $button = $form.find('.zask-btn');
            const originalButtonText = $button.text();
            const email = $('#reset-email').val();
            
            if (!email) {
                ZASK_Gate.showError('Please enter your email address');
                return;
            }
            
            $button.prop('disabled', true).text('Sending...');
            ZASK_Gate.showLoading($form);
            
            const safetyTimeout = setTimeout(function() {
                ZASK_Gate.hideLoading($form);
                $button.prop('disabled', false).text(originalButtonText);
                ZASK_Gate.showError('Request timed out. Please try again.');
            }, 10000);
            
            $.ajax({
                url: zaskGate.ajaxurl,
                type: 'POST',
                dataType: 'json',
                timeout: 9000,
                data: {
                    action: 'zask_reset_password',
                    email: email,
                    nonce: zaskGate.nonce
                },
                success: function(response) {
                    clearTimeout(safetyTimeout);
                    ZASK_Gate.hideLoading($form);
                    $button.prop('disabled', false).text(originalButtonText);
                    
                    if (response && response.success) {
                        ZASK_Gate.showSuccess(response.data.message);
                        $('#reset-email').val('');
                        setTimeout(function() {
                            ZASK_Gate.backToLogin($.Event('click'));
                        }, 3000);
                    } else {
                        const errorMsg = response && response.data && response.data.message 
                            ? response.data.message 
                            : 'Failed to send reset link. Please try again.';
                        ZASK_Gate.showError(errorMsg);
                    }
                },
                error: function(xhr, status) {
                    clearTimeout(safetyTimeout);
                    ZASK_Gate.hideLoading($form);
                    $button.prop('disabled', false).text(originalButtonText);
                    
                    let errorMessage = 'Connection error. Please try again.';
                    if (status === 'timeout') {
                        errorMessage = 'Request timed out. Please check your connection.';
                    }
                    ZASK_Gate.showError(errorMessage);
                },
                complete: function() {
                    setTimeout(function() {
                        if ($button.prop('disabled')) {
                            $button.prop('disabled', false).text(originalButtonText);
                            ZASK_Gate.hideLoading($form);
                        }
                    }, 500);
                }
            });
        },
        
        showLoading: function($form) {
            $form.addClass('zask-loading');
            $form.find('.zask-btn').prop('disabled', true);
        },
        
        hideLoading: function($form) {
            $form.removeClass('zask-loading');
            $form.find('.zask-btn').prop('disabled', false);
        },
        
        showError: function(message) {
            const $error = $('.zask-message-error');
            $error.text(message).fadeIn();
            setTimeout(function() { $error.fadeOut(); }, 5000);
        },
        
        showSuccess: function(message) {
            const $success = $('.zask-message-success');
            $success.text(message).fadeIn();
            setTimeout(function() { $success.fadeOut(); }, 5000);
        },
        
        hideGate: function() {
            $('.zask-gate-modal, .zask-gate-fullpage').fadeOut(300, function() {
                $(this).remove();
                $('body').removeClass('zask-gate-active');
                $('body').css('overflow', '');
            });
        },
        
        checkGateStatus: function() {
            if ($('body').hasClass('zask-gate-active')) {
                $('body').css('overflow', 'hidden');
            }
        }
    };
    
    $(document).ready(function() {
        ZASK_Gate.init();
    });
    
})(jQuery);
