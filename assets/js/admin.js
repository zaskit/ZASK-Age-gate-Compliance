/**
 * ZASK Age-Gate Admin JavaScript
 */

(function($) {
    'use strict';
    
    const ZASK_Admin = {
        
        init: function() {
            this.bindEvents();
            this.initTabs();
            this.initDragDrop();
            this.initAppearancePreview();
        },
        
        bindEvents: function() {
            // Tab navigation
            $(document).on('click', '.zask-nav-tab', this.switchTab);
            
            // Save settings
            $(document).on('click', '#zask-save-settings', this.saveSettings);
            
            // License activation
            $(document).on('click', '#zask-activate-license', this.activateLicense);
            
            // Logo upload
            $(document).on('click', '#zask-upload-logo', this.uploadLogo);
            $(document).on('click', '#zask-remove-logo', this.removeLogo);
            
            // Stage selection
            $(document).on('change', '[name="zask_gate_stage"]', this.handleStageChange);
            
            // Display mode selection
            $(document).on('change', '[name="zask_gate_display_mode"]', this.handleModeChange);
            
            // Form Builder: Custom Fields
            $(document).on('click', '#zask-add-custom-field', this.addCustomField);
            $(document).on('click', '.zask-fb-remove-row', this.removeRow);
            
            // Form Builder: Custom Checkboxes
            $(document).on('click', '#zask-add-custom-checkbox', this.addCustomCheckbox);
            
            // Form Builder: Dropdown type toggle – show/hide options row
            $(document).on('change', '.zask-fb-type-select', this.handleTypeChange);
            
            // Appearance: live preview on color/input change
            $(document).on('input change', '[name^="zask_style_"]', this.updateAppearancePreview);
            
            // Radio cards (Password Mode): highlight selected
            $(document).on('change', '.zask-radio-card input[type="radio"]', function() {
                $(this).closest('.zask-radio-cards').find('.zask-radio-card').removeClass('selected');
                $(this).closest('.zask-radio-card').addClass('selected');
            });
        },
        
        initTabs: function() {
            // Show first tab by default
            $('.zask-nav-tab').first().addClass('active');
            $('.zask-tab-content').first().addClass('active');
        },
        
        /**
         * Initialise drag-and-drop reordering on form-builder tables
         */
        initDragDrop: function() {
            if ($.fn.sortable) {
                $('#zask-custom-fields-body, #zask-custom-checkboxes-body').sortable({
                    handle: '.zask-fb-drag-handle',
                    axis: 'y',
                    items: '.zask-fb-row',
                    helper: function(e, tr) {
                        var $originals = tr.children();
                        var $helper = tr.clone();
                        $helper.children().each(function(index) {
                            $(this).width($originals.eq(index).width());
                        });
                        $helper.css({background: '#f0f4ff', boxShadow: '0 2px 8px rgba(0,0,0,.12)'});
                        return $helper;
                    },
                    placeholder: 'zask-fb-sortable-placeholder',
                    tolerance: 'pointer',
                    update: function() {
                        ZASK_Admin.reindexRows($(this));
                    }
                });
            }
        },
        
        /**
         * Re-number data-index attributes after drag reorder
         */
        reindexRows: function($tbody) {
            $tbody.find('.zask-fb-row').each(function(i) {
                $(this).attr('data-index', i);
                $(this).next('.zask-fb-options-row').attr('data-parent', i);
            });
        },
        
        switchTab: function(e) {
            e.preventDefault();
            const $btn = $(this);
            const tab = $btn.data('tab');
            
            $('.zask-nav-tab').removeClass('active');
            $btn.addClass('active');
            
            $('.zask-tab-content').removeClass('active');
            $('.zask-tab-content[data-tab="' + tab + '"]').addClass('active');
        },
        
        /* -----------------------------------------------------------
         *  Handle field type change (show/hide dropdown options row)
         * --------------------------------------------------------- */
        handleTypeChange: function() {
            var $row = $(this).closest('.zask-fb-row');
            var idx = $row.attr('data-index');
            var type = $(this).val();
            var $optRow = $row.next('.zask-fb-options-row');
            
            if (type === 'dropdown') {
                if ($optRow.length === 0) {
                    var optionsHtml = '<tr class="zask-fb-options-row" data-parent="' + idx + '">'
                        + '<td></td>'
                        + '<td colspan="6">'
                        + '<div class="zask-fb-options-wrap">'
                        + '<label class="zask-fb-options-label">Dropdown Options (one per line):</label>'
                        + '<textarea class="zask-fb-options-textarea" data-key="options" rows="3" placeholder="Option 1\nOption 2\nOption 3"></textarea>'
                        + '</div>'
                        + '</td>'
                        + '</tr>';
                    $row.after(optionsHtml);
                } else {
                    $optRow.show();
                }
            } else {
                if ($optRow.length) {
                    $optRow.hide();
                }
            }
        },
        
        /* -----------------------------------------------------------
         *  Collect form-builder data so it is included in save
         * --------------------------------------------------------- */
        collectFormBuilderData: function(settings) {
            // Custom Fields
            var fields = [];
            $('#zask-custom-fields-body .zask-fb-row').each(function() {
                var $row = $(this);
                var label = $row.find('[data-key="label"]').val().trim();
                if (!label) return;
                
                var type = $row.find('[data-key="type"]').val();
                var fieldData = {
                    label:       label,
                    placeholder: $row.find('[data-key="placeholder"]').val().trim(),
                    type:        type,
                    required:    $row.find('[data-key="required"]').is(':checked') ? 1 : 0,
                    enabled:     $row.find('[data-key="enabled"]').is(':checked') ? 1 : 0
                };
                
                // If dropdown, collect options from the sibling options row
                if (type === 'dropdown') {
                    var $optRow = $row.next('.zask-fb-options-row');
                    if ($optRow.length) {
                        fieldData.options = $optRow.find('[data-key="options"]').val() || '';
                    }
                }
                
                fields.push(fieldData);
            });
            settings['zask_custom_fields'] = JSON.stringify(fields);

            // Custom Checkboxes
            var checkboxes = [];
            $('#zask-custom-checkboxes-body .zask-fb-row').each(function() {
                var $row = $(this);
                var label = $row.find('[data-key="label"]').val().trim();
                if (!label) return;
                checkboxes.push({
                    label:    label,
                    required: $row.find('[data-key="required"]').is(':checked') ? 1 : 0,
                    enabled:  $row.find('[data-key="enabled"]').is(':checked') ? 1 : 0
                });
            });
            settings['zask_custom_checkboxes'] = JSON.stringify(checkboxes);

            return settings;
        },
        
        saveSettings: function(e) {
            e.preventDefault();
            
            const $btn = $(this);
            const $status = $('.zask-save-status');
            
            // Gather all settings
            const settings = {};
            $('[name^="zask_"]').each(function() {
                const $field = $(this);
                const name = $field.attr('name');
                
                // Skip form builder inline inputs (they have data-key)
                if ($field.closest('.zask-fb-row').length) return;
                // Skip options-row textareas (collected separately)
                if ($field.closest('.zask-fb-options-row').length) return;
                
                if ($field.is(':checkbox')) {
                    settings[name] = $field.is(':checked') ? '1' : '0';
                } else if ($field.is(':radio')) {
                    if ($field.is(':checked')) {
                        settings[name] = $field.val();
                    }
                } else {
                    settings[name] = $field.val();
                }
            });
            
            // Add form builder data
            ZASK_Admin.collectFormBuilderData(settings);
            
            // Show loading
            $btn.prop('disabled', true).html('<span class="dashicons dashicons-update-alt"></span> ' + zaskAdmin.strings.saving);
            $status.html('<span class="spinner is-active" style="float:none;"></span>');
            
            // Send AJAX request
            $.post(zaskAdmin.ajaxurl, {
                action: 'zask_save_settings',
                nonce: zaskAdmin.nonce,
                settings: settings
            }, function(response) {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-yes"></span> ' + zaskAdmin.strings.saved);
                
                if (response.success) {
                    $status.html('<span style="color: #10b981;">✓ ' + response.data.message + '</span>');
                } else {
                    $status.html('<span style="color: #dc2626;">✗ ' + response.data.message + '</span>');
                }
                
                setTimeout(function() {
                    $btn.html('<span class="dashicons dashicons-yes"></span> Save All Settings');
                    $status.html('');
                }, 3000);
            });
        },
        
        activateLicense: function(e) {
            e.preventDefault();
            
            const $btn = $(this);
            const $status = $('.zask-license-status');
            const licenseKey = $('[name="zask_license_key"]').val().trim();
            
            if (!licenseKey) {
                $status.html('<span style="color: #dc2626;">✗ Please enter a license key</span>');
                return;
            }
            
            $btn.prop('disabled', true).html('<span class="dashicons dashicons-update-alt"></span> Activating...');
            $status.html('<span class="spinner is-active" style="float:none;"></span> Contacting license server...');
            
            $.post(zaskAdmin.ajaxurl, {
                action: 'zask_activate_license',
                nonce: zaskAdmin.nonce,
                license_key: licenseKey
            }, function(response) {
                $btn.prop('disabled', false).html('Activate License');
                
                if (response.success) {
                    $status.html('<span style="color: #10b981;">✓ ' + response.data.message + '</span>');
                    setTimeout(function() { location.reload(); }, 1000);
                } else {
                    $status.html('<span style="color: #dc2626;">✗ ' + response.data.message + '</span>');
                }
            }).fail(function() {
                $btn.prop('disabled', false).html('Activate License');
                $status.html('<span style="color: #dc2626;">✗ Connection error. Please try again.</span>');
            });
        },
        
        handleStageChange: function() {
            const $selected = $('[name="zask_gate_stage"]:checked');
            $('.zask-stage-option').removeClass('active');
            $selected.closest('.zask-stage-option').addClass('active');
        },
        
        handleModeChange: function() {
            const $selected = $('[name="zask_gate_display_mode"]:checked');
            $('.zask-mode-option').removeClass('active');
            $selected.closest('.zask-mode-option').addClass('active');
        },
        
        uploadLogo: function(e) {
            e.preventDefault();
            
            if (ZASK_Admin.mediaFrame) {
                ZASK_Admin.mediaFrame.open();
                return;
            }
            
            ZASK_Admin.mediaFrame = wp.media({
                title: 'Select Logo',
                button: { text: 'Use this logo' },
                multiple: false,
                library: { type: 'image' }
            });
            
            ZASK_Admin.mediaFrame.on('select', function() {
                const attachment = ZASK_Admin.mediaFrame.state().get('selection').first().toJSON();
                $('#zask_gate_logo_id').val(attachment.id);
                $('.zask-logo-preview img').attr('src', attachment.url);
                $('.zask-logo-preview').show();
                $('#zask-remove-logo').show();
            });
            
            ZASK_Admin.mediaFrame.open();
        },
        
        removeLogo: function(e) {
            e.preventDefault();
            $('#zask_gate_logo_id').val('');
            $('.zask-logo-preview').hide();
            $(this).hide();
        },
        
        /* -----------------------------------------------------------
         *  Form Builder – Add / Remove rows
         * --------------------------------------------------------- */
        addCustomField: function(e) {
            e.preventDefault();
            var idx = $('#zask-custom-fields-body .zask-fb-row').length;
            var row = '<tr class="zask-fb-row" data-index="' + idx + '">'
                + '<td class="zask-fb-col-drag"><span class="dashicons dashicons-menu zask-fb-drag-handle"></span></td>'
                + '<td><input type="text" class="zask-fb-input" data-key="label" value="" placeholder="Field label"></td>'
                + '<td><input type="text" class="zask-fb-input" data-key="placeholder" value="" placeholder="Placeholder text"></td>'
                + '<td><select class="zask-fb-select zask-fb-type-select" data-key="type"><option value="text">Text</option><option value="number">Number</option><option value="dropdown">Dropdown</option></select></td>'
                + '<td class="zask-fb-col-center"><label class="zask-fb-toggle"><input type="checkbox" data-key="required"><span class="zask-fb-toggle-slider"></span></label></td>'
                + '<td class="zask-fb-col-center"><label class="zask-fb-toggle"><input type="checkbox" data-key="enabled" checked><span class="zask-fb-toggle-slider"></span></label></td>'
                + '<td class="zask-fb-col-actions"><button type="button" class="zask-fb-remove-row" title="Remove"><span class="dashicons dashicons-trash"></span></button></td>'
                + '</tr>';
            $('#zask-custom-fields-body').append(row);
            $('#zask-fields-empty').hide();
            if ($.fn.sortable && $('#zask-custom-fields-body').data('ui-sortable')) {
                $('#zask-custom-fields-body').sortable('refresh');
            }
        },
        
        addCustomCheckbox: function(e) {
            e.preventDefault();
            var idx = $('#zask-custom-checkboxes-body .zask-fb-row').length;
            var row = '<tr class="zask-fb-row" data-index="' + idx + '">'
                + '<td class="zask-fb-col-drag"><span class="dashicons dashicons-menu zask-fb-drag-handle"></span></td>'
                + '<td><input type="text" class="zask-fb-input zask-fb-input-wide" data-key="label" value="" placeholder="Checkbox label text"></td>'
                + '<td class="zask-fb-col-center"><label class="zask-fb-toggle"><input type="checkbox" data-key="required"><span class="zask-fb-toggle-slider"></span></label></td>'
                + '<td class="zask-fb-col-center"><label class="zask-fb-toggle"><input type="checkbox" data-key="enabled" checked><span class="zask-fb-toggle-slider"></span></label></td>'
                + '<td class="zask-fb-col-actions"><button type="button" class="zask-fb-remove-row" title="Remove"><span class="dashicons dashicons-trash"></span></button></td>'
                + '</tr>';
            $('#zask-custom-checkboxes-body').append(row);
            $('#zask-checkboxes-empty').hide();
            if ($.fn.sortable && $('#zask-custom-checkboxes-body').data('ui-sortable')) {
                $('#zask-custom-checkboxes-body').sortable('refresh');
            }
        },
        
        removeRow: function(e) {
            e.preventDefault();
            var $row = $(this).closest('.zask-fb-row');
            var $tbody = $row.closest('tbody');
            var $optRow = $row.next('.zask-fb-options-row');
            
            $row.fadeOut(200, function() {
                if ($optRow.length) $optRow.remove();
                $row.remove();
                ZASK_Admin.reindexRows($tbody);
                if ($tbody.attr('id') === 'zask-custom-fields-body' && $tbody.find('.zask-fb-row').length === 0) {
                    $('#zask-fields-empty').show();
                }
                if ($tbody.attr('id') === 'zask-custom-checkboxes-body' && $tbody.find('.zask-fb-row').length === 0) {
                    $('#zask-checkboxes-empty').show();
                }
            });
        },
        
        /* -----------------------------------------------------------
         *  Appearance – Live preview
         * --------------------------------------------------------- */
        initAppearancePreview: function() {
            this.updateAppearancePreview();
        },
        
        updateAppearancePreview: function() {
            var $preview = $('#zask-appearance-preview');
            if (!$preview.length) return;
            
            var btn1  = $('[name="zask_style_btn_color"]').val()     || '#667eea';
            var btn2  = $('[name="zask_style_btn_color_end"]').val() || '#764ba2';
            var btnTx = $('[name="zask_style_btn_text"]').val()      || '#ffffff';
            var fmBg  = $('[name="zask_style_form_bg"]').val()       || '#ffffff';
            var fmTx  = $('[name="zask_style_form_text"]').val()     || '#111827';
            var fmRad = $('[name="zask_style_form_radius"]').val()   || '20';
            
            $preview.css({
                background: fmBg,
                borderRadius: fmRad + 'px'
            });
            $preview.find('.zask-preview-text').css('color', fmTx);
            $preview.find('.zask-preview-btn').css({
                background: 'linear-gradient(135deg, ' + btn1 + ' 0%, ' + btn2 + ' 100%)',
                color: btnTx,
                borderRadius: '10px'
            });
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        if ($('.zask-admin-wrap').length) {
            ZASK_Admin.init();
        }
    });
    
})(jQuery);
