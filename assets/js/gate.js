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
            $(document).on('submit', '#zask-forgot-form', this.handleForgotPassword);
            $(document).on('click', '.zask-forgot-password-link', this.showForgotForm);
            $(document).on('click', '.zask-back-to-login', this.showLoginForm);
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
                    ZASK_Gate.markVerified();
                    ZASK_Gate.navigateAway();
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
                    ZASK_Gate.markVerified();
                    ZASK_Gate.navigateAway();
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
                        ZASK_Gate.markVerified();
                        ZASK_Gate.navigateAway();
                    }
                } else {
                    ZASK_Gate.showError(response.data ? response.data.message : 'Registration failed. Please try again.');
                }
            }).fail(function() {
                ZASK_Gate.hideLoading($form);
                ZASK_Gate.showError('Connection error. Please try again.');
            });
        },

        showForgotForm: function(e) {
            e.preventDefault();
            $('.zask-auth-form, .zask-auth-toggle').hide();
            $('#zask-forgot-form').show();
        },

        showLoginForm: function(e) {
            e.preventDefault();
            $('#zask-forgot-form').hide();
            $('.zask-auth-toggle').show();
            $('#zask-login-form').addClass('active').show();
        },

        handleForgotPassword: function(e) {
            e.preventDefault();
            var $form = $(this);
            var email = $form.find('[name="email"]').val();

            if (!email) {
                ZASK_Gate.showError('Please enter your email address.');
                return;
            }

            ZASK_Gate.showLoading($form);

            $.post(zaskGate.ajaxurl, {
                action: 'zask_reset_password',
                nonce: zaskGate.nonce,
                email: email
            }, function(response) {
                ZASK_Gate.hideLoading($form);
                if (response.success) {
                    ZASK_Gate.showSuccess(response.data.message);
                } else {
                    ZASK_Gate.showError(response.data.message);
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

        /**
         * Mark the user as verified using multiple storage methods.
         * Safari private mode and Chrome incognito can block or silently drop
         * cookies set via AJAX Set-Cookie headers or document.cookie with
         * SameSite attribute. Using 3 methods ensures at least one sticks.
         */
        markVerified: function() {
            var duration = parseInt(zaskGate.session_duration || '120', 10);

            // Method 1: Cookie WITHOUT SameSite (Safari compatibility)
            // Safari drops cookies with SameSite set via document.cookie
            var expires = '';
            if (duration > 0) {
                var d = new Date();
                d.setTime(d.getTime() + duration * 60 * 1000);
                expires = '; expires=' + d.toUTCString();
            }
            var secure = location.protocol === 'https:' ? '; Secure' : '';
            document.cookie = 'zask_age_verified=yes; path=/' + expires + secure;

            // Method 2: localStorage (survives cookie issues, checked by PHP via AJAX)
            try {
                localStorage.setItem('zask_age_verified', 'yes');
                if (duration > 0) {
                    localStorage.setItem('zask_age_verified_expires', String(Date.now() + duration * 60 * 1000));
                }
            } catch(e) {}

            // Method 3: sessionStorage (fallback for Safari private where localStorage may fail)
            try {
                sessionStorage.setItem('zask_age_verified', 'yes');
            } catch(e) {}
        },

        /**
         * Navigate away from the gate.
         * Use location.href assignment instead of reload() to avoid cached responses.
         * Append a cache-busting parameter so the server sees a fresh request.
         */
        navigateAway: function() {
            var url = window.location.href;
            // Remove any existing zask_v param
            url = url.replace(/[?&]zask_v=[^&]*/g, '');
            // Add cache buster
            var sep = url.indexOf('?') !== -1 ? '&' : '?';
            window.location.href = url + sep + 'zask_v=' + Date.now();
        },

        checkGateStatus: function() {
            if ($('body').hasClass('zask-gate-active')) {
                $('body').css('overflow', 'hidden');

                // Check if user is verified via localStorage/sessionStorage
                // but cookie was lost (Safari/incognito). If so, re-set cookie.
                var lsVerified = false;
                try {
                    var exp = localStorage.getItem('zask_age_verified_expires');
                    if (localStorage.getItem('zask_age_verified') === 'yes') {
                        if (!exp || Date.now() < parseInt(exp, 10)) {
                            lsVerified = true;
                        } else {
                            // Expired — clean up
                            localStorage.removeItem('zask_age_verified');
                            localStorage.removeItem('zask_age_verified_expires');
                        }
                    }
                } catch(e) {}

                if (!lsVerified) {
                    try {
                        if (sessionStorage.getItem('zask_age_verified') === 'yes') {
                            lsVerified = true;
                        }
                    } catch(e) {}
                }

                if (lsVerified) {
                    // Re-set the cookie and hide the gate
                    ZASK_Gate.markVerified();
                    ZASK_Gate.hideGate();
                }
            }
        }
    };

    $(document).ready(function() {
        ZASK_Gate.init();
    });

})(jQuery);
