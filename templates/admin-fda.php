<?php
/**
 * FDA Monitor Admin Page
 * Real-time FDA monitoring with detailed results
 *
 * @package ZASK_Age_Gate
 */

if (!defined('ABSPATH')) exit;

global $wpdb;

// Get monitored products
$products_table = $wpdb->prefix . 'zask_fda_products';
$monitored_products = $wpdb->get_results("SELECT * FROM $products_table ORDER BY created_at DESC");

// Get FDA alerts
$alerts_table = $wpdb->prefix . 'zask_fda_alerts';
$recent_alerts = $wpdb->get_results("SELECT * FROM $alerts_table WHERE status = 'active' ORDER BY detected_at DESC LIMIT 10");
$total_alerts = $wpdb->get_var("SELECT COUNT(*) FROM $alerts_table WHERE status = 'active'");
?>

<div class="wrap zask-admin-wrap">
    <h1><?php _e('FDA Monitor', 'zask-age-gate'); ?></h1>
    <p class="description"><?php _e('Real-time monitoring of FDA warning letters and enforcement actions for your products', 'zask-age-gate'); ?></p>
    
    <!-- Stats Cards -->
    <div class="zask-stats-grid" style="margin: 30px 0;">
        <div class="zask-stat-card">
            <div class="zask-stat-icon" style="background: #3b82f6;">📦</div>
            <div class="zask-stat-content">
                <div class="zask-stat-number"><?php echo count($monitored_products); ?></div>
                <div class="zask-stat-label"><?php _e('Products Monitored', 'zask-age-gate'); ?></div>
            </div>
        </div>
        
        <div class="zask-stat-card">
            <div class="zask-stat-icon" style="background: <?php echo $total_alerts > 0 ? '#ef4444' : '#10b981'; ?>;">
                <?php echo $total_alerts > 0 ? '⚠️' : '✅'; ?>
            </div>
            <div class="zask-stat-content">
                <div class="zask-stat-number"><?php echo $total_alerts; ?></div>
                <div class="zask-stat-label"><?php _e('Active Alerts', 'zask-age-gate'); ?></div>
            </div>
        </div>
        
        <div class="zask-stat-card">
            <div class="zask-stat-icon" style="background: #8b5cf6;">🔄</div>
            <div class="zask-stat-content">
                <div class="zask-stat-number" id="zask-last-scan-time">
                    <?php 
                    $last_scan = get_option('zask_last_fda_scan');
                    echo $last_scan ? human_time_diff(strtotime($last_scan), current_time('timestamp')) . ' ago' : 'Never';
                    ?>
                </div>
                <div class="zask-stat-label"><?php _e('Last Scan', 'zask-age-gate'); ?></div>
            </div>
        </div>
    </div>
    
    <div class="zask-admin-grid">
        <!-- Left Column: Add Products & Scan -->
        <div class="zask-admin-main">
            
            <!-- Add Product Card -->
            <div class="zask-card">
                <div class="zask-card-header">
                    <h3><?php _e('Add Products to Monitor', 'zask-age-gate'); ?></h3>
                    <p><?php _e('Select products from your WooCommerce store to monitor for FDA warnings', 'zask-age-gate'); ?></p>
                </div>
                <div class="zask-card-body">
                    <?php if (class_exists('WooCommerce')): ?>
                        <form id="zask-add-wc-products-form">
                            <div class="zask-form-group">
                                <label><?php _e('Select Products from Store', 'zask-age-gate'); ?> <span style="color: red;">*</span></label>
                                <select id="wc-product-selector" class="regular-text" multiple style="height: 200px; width: 100%;">
                                    <?php
                                    $args = array(
                                        'post_type' => 'product',
                                        'posts_per_page' => -1,
                                        'post_status' => 'publish',
                                        'orderby' => 'title',
                                        'order' => 'ASC'
                                    );
                                    $products = get_posts($args);
                                    
                                    // Get already monitored SKUs
                                    $monitored_skus = array_filter(array_column($monitored_products, 'sku'));
                                    
                                    foreach ($products as $product):
                                        $product_obj = wc_get_product($product->ID);
                                        $sku = $product_obj->get_sku();
                                        $is_monitored = in_array($sku, $monitored_skus);
                                    ?>
                                        <option value="<?php echo esc_attr($product->ID); ?>" 
                                                data-sku="<?php echo esc_attr($sku); ?>"
                                                <?php echo $is_monitored ? 'disabled' : ''; ?>>
                                            <?php echo esc_html($product->post_title); ?>
                                            <?php echo $sku ? ' (SKU: ' . esc_html($sku) . ')' : ''; ?>
                                            <?php echo $is_monitored ? ' ✓ MONITORED' : ''; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php _e('Hold Ctrl (Cmd on Mac) to select multiple products at once', 'zask-age-gate'); ?></p>
                            </div>
                            
                            <button type="submit" class="button button-primary button-large">
                                <span class="dashicons dashicons-plus-alt"></span>
                                <?php _e('Add Selected Products', 'zask-age-gate'); ?>
                            </button>
                        </form>
                        
                        <hr style="margin: 30px 0; opacity: 0.3;">
                        
                        <details style="margin-top: 20px;">
                            <summary style="cursor: pointer; font-weight: 600; color: #666;">
                                <?php _e('+ Add Custom Product (not in WooCommerce)', 'zask-age-gate'); ?>
                            </summary>
                            <div style="margin-top: 20px;">
                    <?php else: ?>
                        <div class="notice notice-warning inline">
                            <p><?php _e('WooCommerce not detected. You can add custom products manually.', 'zask-age-gate'); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <form id="zask-add-product-form">
                        <div class="zask-form-row">
                            <div class="zask-form-group" style="flex: 2;">
                                <label><?php _e('Product Name', 'zask-age-gate'); ?> <span style="color: red;">*</span></label>
                                <input type="text" id="product-name" class="regular-text" placeholder="e.g., Custom Supplement" required>
                                <p class="description"><?php _e('For products not in your WooCommerce catalog', 'zask-age-gate'); ?></p>
                            </div>
                            
                            <div class="zask-form-group" style="flex: 1;">
                                <label><?php _e('SKU (Optional)', 'zask-age-gate'); ?></label>
                                <input type="text" id="product-sku" class="regular-text" placeholder="e.g., CUSTOM-001">
                            </div>
                        </div>
                        
                        <button type="submit" class="button button-secondary">
                            <span class="dashicons dashicons-plus-alt"></span>
                            <?php _e('Add Custom Product', 'zask-age-gate'); ?>
                        </button>
                    </form>
                    
                    <?php if (class_exists('WooCommerce')): ?>
                            </div>
                        </details>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Scan Control Card -->
            <div class="zask-card">
                <div class="zask-card-header">
                    <h3><?php _e('FDA Scanning', 'zask-age-gate'); ?></h3>
                    <p><?php _e('Scan FDA database for warning letters mentioning your products', 'zask-age-gate'); ?></p>
                </div>
                <div class="zask-card-body">
                    <div id="zask-scan-status" class="zask-scan-idle">
                        <div class="zask-scan-icon">🔍</div>
                        <p class="zask-scan-message"><?php _e('Ready to scan', 'zask-age-gate'); ?></p>
                        <p class="zask-scan-details"><?php printf(__('%d products will be scanned', 'zask-age-gate'), count($monitored_products)); ?></p>
                    </div>
                    
                    <!-- Scanning Progress -->
                    <div id="zask-scan-progress" style="display: none;">
                        <div class="zask-progress-bar">
                            <div class="zask-progress-fill" id="zask-progress-fill"></div>
                        </div>
                        <p class="zask-progress-text">
                            <span id="zask-current-product"></span>
                            <span id="zask-progress-counter"></span>
                        </p>
                    </div>
                    
                    <!-- Scan Results Log -->
                    <div id="zask-scan-log" style="display: none;">
                        <div class="zask-scan-log-header">
                            <h4><?php _e('Scan Log', 'zask-age-gate'); ?></h4>
                            <button type="button" class="button button-small" id="zask-clear-log">
                                <?php _e('Clear', 'zask-age-gate'); ?>
                            </button>
                        </div>
                        <div class="zask-scan-log-content" id="zask-log-content"></div>
                    </div>
                    
                    <div class="zask-button-group">
                        <button type="button" class="button button-primary button-large" id="zask-start-scan">
                            <span class="dashicons dashicons-update"></span>
                            <?php _e('Start FDA Scan', 'zask-age-gate'); ?>
                        </button>
                        
                        <button type="button" class="button button-secondary" id="zask-stop-scan" style="display: none;">
                            <span class="dashicons dashicons-no"></span>
                            <?php _e('Stop Scan', 'zask-age-gate'); ?>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Monitored Products List -->
            <div class="zask-card">
                <div class="zask-card-header">
                    <h3><?php _e('Monitored Products', 'zask-age-gate'); ?></h3>
                    <p><?php printf(__('%d products being monitored', 'zask-age-gate'), count($monitored_products)); ?></p>
                </div>
                <div class="zask-card-body">
                    <?php if (empty($monitored_products)): ?>
                        <div class="zask-empty-state">
                            <span class="dashicons dashicons-info" style="font-size: 48px; opacity: 0.3;"></span>
                            <p><?php _e('No products added yet', 'zask-age-gate'); ?></p>
                            <p class="description"><?php _e('Add products above to start monitoring', 'zask-age-gate'); ?></p>
                        </div>
                    <?php else: ?>
                        <table class="wp-list-table widefat fixed striped" id="zask-products-table">
                            <thead>
                                <tr>
                                    <th><?php _e('Product Name', 'zask-age-gate'); ?></th>
                                    <th><?php _e('SKU', 'zask-age-gate'); ?></th>
                                    <th><?php _e('Status', 'zask-age-gate'); ?></th>
                                    <th><?php _e('Last Scanned', 'zask-age-gate'); ?></th>
                                    <th><?php _e('Actions', 'zask-age-gate'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($monitored_products as $product): ?>
                                    <tr data-product-id="<?php echo esc_attr($product->id); ?>">
                                        <td><strong><?php echo esc_html($product->product_name); ?></strong></td>
                                        <td><?php echo esc_html($product->sku ?: '—'); ?></td>
                                        <td>
                                            <span class="zask-badge zask-badge-success">
                                                ✓ <?php _e('Monitoring', 'zask-age-gate'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            echo $product->last_scanned_at 
                                                ? human_time_diff(strtotime($product->last_scanned_at), current_time('timestamp')) . ' ago'
                                                : __('Never', 'zask-age-gate');
                                            ?>
                                        </td>
                                        <td>
                                            <button type="button" class="button button-small zask-remove-product" 
                                                    data-id="<?php echo esc_attr($product->id); ?>" 
                                                    data-name="<?php echo esc_attr($product->product_name); ?>">
                                                <span class="dashicons dashicons-trash"></span>
                                                <?php _e('Remove', 'zask-age-gate'); ?>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Right Column: Alerts -->
        <div class="zask-admin-sidebar">
            <div class="zask-card">
                <div class="zask-card-header">
                    <h3><?php _e('Recent Alerts', 'zask-age-gate'); ?></h3>
                </div>
                <div class="zask-card-body">
                    <?php if (empty($recent_alerts)): ?>
                        <div class="zask-empty-state">
                            <span style="font-size: 48px;">✅</span>
                            <p><?php _e('No alerts', 'zask-age-gate'); ?></p>
                            <p class="description"><?php _e('All products clear!', 'zask-age-gate'); ?></p>
                        </div>
                    <?php else: ?>
                        <div class="zask-alerts-list">
                            <?php foreach ($recent_alerts as $alert): ?>
                                <div class="zask-alert-item">
                                    <div class="zask-alert-header">
                                        <span class="zask-badge zask-badge-danger">⚠️ <?php _e('Warning', 'zask-age-gate'); ?></span>
                                        <span class="zask-alert-date"><?php echo human_time_diff(strtotime($alert->detected_at), current_time('timestamp')); ?> ago</span>
                                    </div>
                                    <h4><?php echo esc_html($alert->product_name); ?></h4>
                                    <p><?php echo esc_html(substr($alert->warning_details, 0, 150)); ?>...</p>
                                    <div class="zask-alert-actions">
                                        <a href="<?php echo esc_url($alert->fda_url); ?>" target="_blank" class="button button-small">
                                            <?php _e('View FDA Letter', 'zask-age-gate'); ?>
                                        </a>
                                        <button type="button" class="button button-small zask-resolve-alert" data-id="<?php echo esc_attr($alert->id); ?>">
                                            <?php _e('Resolve', 'zask-age-gate'); ?>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Info Card -->
            <div class="zask-card">
                <div class="zask-card-header">
                    <h3><?php _e('How It Works', 'zask-age-gate'); ?></h3>
                </div>
                <div class="zask-card-body">
                    <div class="zask-info-list">
                        <div class="zask-info-item">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <p><?php _e('Automatic scanning of FDA warning letters', 'zask-age-gate'); ?></p>
                        </div>
                        <div class="zask-info-item">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <p><?php _e('Instant email alerts when products are mentioned', 'zask-age-gate'); ?></p>
                        </div>
                        <div class="zask-info-item">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <p><?php _e('Daily automated scans at midnight', 'zask-age-gate'); ?></p>
                        </div>
                        <div class="zask-info-item">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <p><?php _e('Complete audit trail of all warnings', 'zask-age-gate'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<script>
jQuery(document).ready(function($) {
    let scanInProgress = false;
    let currentScanIndex = 0;
    let totalProducts = 0;
    
    // Add Product
    // WooCommerce Bulk Product Addition
    $('#zask-add-wc-products-form').on('submit', function(e) {
        e.preventDefault();
        
        const selectedOptions = $('#wc-product-selector option:selected');
        
        if (selectedOptions.length === 0) {
            alert('Please select at least one product');
            return;
        }
        
        const $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Adding ' + selectedOptions.length + ' products...');
        
        let productsAdded = 0;
        let productsFailed = 0;
        
        // Add products sequentially
        function addNextProduct(index) {
            if (index >= selectedOptions.length) {
                // All done
                if (productsFailed > 0) {
                    alert(productsAdded + ' products added successfully. ' + productsFailed + ' failed.');
                }
                location.reload();
                return;
            }
            
            const $option = $(selectedOptions[index]);
            const productId = $option.val();
            const productName = $option.text().split('(SKU:')[0].split(' ✓')[0].trim();
            const productSku = $option.data('sku') || '';
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'zask_add_fda_product',
                    product_name: productName,
                    sku: productSku,
                    nonce: '<?php echo wp_create_nonce('zask_admin_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        productsAdded++;
                    } else {
                        productsFailed++;
                    }
                    addNextProduct(index + 1);
                },
                error: function() {
                    productsFailed++;
                    addNextProduct(index + 1);
                }
            });
        }
        
        addNextProduct(0);
    });
    
    // Custom Product Addition
    $('#zask-add-product-form').on('submit', function(e) {
        e.preventDefault();
        
        const $btn = $(this).find('button[type="submit"]');
        const productName = $('#product-name').val().trim();
        const productSku = $('#product-sku').val().trim();
        
        if (!productName) {
            alert('Please enter a product name');
            return;
        }
        
        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Adding...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'zask_add_fda_product',
                product_name: productName,
                sku: productSku,
                nonce: '<?php echo wp_create_nonce('zask_admin_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || 'Failed to add product');
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-plus-alt"></span> Add Product');
                }
            },
            error: function() {
                alert('Connection error. Please try again.');
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-plus-alt"></span> Add Product');
            }
        });
    });
    
    // Remove Product
    $(document).on('click', '.zask-remove-product', function() {
        const productId = $(this).data('id');
        const productName = $(this).data('name');
        const $row = $(this).closest('tr');
        
        if (!confirm(`Remove "${productName}" from monitoring?`)) {
            return;
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'zask_remove_fda_product',
                product_id: productId,
                nonce: '<?php echo wp_create_nonce('zask_admin_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $row.fadeOut(300, function() {
                        $(this).remove();
                        if ($('#zask-products-table tbody tr').length === 0) {
                            location.reload();
                        }
                    });
                } else {
                    alert(response.data.message || 'Failed to remove product');
                }
            }
        });
    });
    
    // Start Scan
    $('#zask-start-scan').on('click', function() {
        if (scanInProgress) return;
        
        const products = <?php echo json_encode($monitored_products); ?>;
        
        if (products.length === 0) {
            alert('Please add products to monitor first');
            return;
        }
        
        startScan(products);
    });
    
    // Stop Scan
    $('#zask-stop-scan').on('click', function() {
        scanInProgress = false;
        resetScanUI();
        addLog('Scan stopped by user', 'warning');
    });
    
    // Clear Log
    $('#zask-clear-log').on('click', function() {
        $('#zask-log-content').empty();
    });
    
    function startScan(products) {
        scanInProgress = true;
        currentScanIndex = 0;
        totalProducts = products.length;
        
        // Update UI
        $('#zask-start-scan').hide();
        $('#zask-stop-scan').show();
        $('#zask-scan-status').hide();
        $('#zask-scan-progress').show();
        $('#zask-scan-log').show();
        $('#zask-log-content').empty();
        
        addLog(`Starting FDA scan for ${totalProducts} products...`, 'info');
        
        // Scan each product
        scanNextProduct(products);
    }
    
    function scanNextProduct(products) {
        if (!scanInProgress || currentScanIndex >= totalProducts) {
            completeScan();
            return;
        }
        
        const product = products[currentScanIndex];
        const progress = ((currentScanIndex + 1) / totalProducts) * 100;
        
        // Update progress
        $('#zask-progress-fill').css('width', progress + '%');
        $('#zask-current-product').text(`Scanning: ${product.product_name}`);
        $('#zask-progress-counter').text(`${currentScanIndex + 1} / ${totalProducts}`);
        
        addLog(`[${currentScanIndex + 1}/${totalProducts}] Scanning: ${product.product_name}`, 'info');
        
        // Make AJAX request to scan this product
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'zask_scan_single_product',
                product_id: product.id,
                nonce: '<?php echo wp_create_nonce('zask_admin_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    if (response.data.alerts && response.data.alerts.length > 0) {
                        addLog(`⚠️ WARNING: ${response.data.alerts.length} alert(s) found for ${product.product_name}`, 'error');
                        response.data.alerts.forEach(function(alert) {
                            addLog(`   → ${alert}`, 'warning');
                        });
                    } else {
                        addLog(`✓ ${product.product_name}: No warnings found`, 'success');
                    }
                } else {
                    addLog(`✗ Error scanning ${product.product_name}: ${response.data.message}`, 'error');
                }
                
                currentScanIndex++;
                
                // Continue to next product after delay
                setTimeout(function() {
                    scanNextProduct(products);
                }, 500);
            },
            error: function() {
                addLog(`✗ Connection error scanning ${product.product_name}`, 'error');
                currentScanIndex++;
                setTimeout(function() {
                    scanNextProduct(products);
                }, 500);
            }
        });
    }
    
    function completeScan() {
        scanInProgress = false;
        $('#zask-progress-fill').css('width', '100%');
        addLog('=================================', 'info');
        addLog('Scan complete!', 'success');
        addLog(`Scanned ${totalProducts} products`, 'info');
        
        // Update last scan time
        $('#zask-last-scan-time').text('Just now');
        
        // Reset UI after delay
        setTimeout(function() {
            resetScanUI();
            // Reload to show updated alerts
            location.reload();
        }, 2000);
    }
    
    function resetScanUI() {
        $('#zask-start-scan').show();
        $('#zask-stop-scan').hide();
        $('#zask-scan-progress').hide();
        $('#zask-scan-status').show();
    }
    
    function addLog(message, type) {
        const timestamp = new Date().toLocaleTimeString();
        const $entry = $('<div>').addClass('zask-log-entry').addClass(type);
        $entry.text(`[${timestamp}] ${message}`);
        $('#zask-log-content').append($entry);
        
        // Auto-scroll to bottom
        const logContent = document.getElementById('zask-log-content');
        logContent.scrollTop = logContent.scrollHeight;
    }
    
    // Resolve Alert
    $(document).on('click', '.zask-resolve-alert', function() {
        const alertId = $(this).data('id');
        const $item = $(this).closest('.zask-alert-item');
        
        if (!confirm('Mark this alert as resolved?')) {
            return;
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'zask_resolve_fda_alert',
                alert_id: alertId,
                nonce: '<?php echo wp_create_nonce('zask_admin_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $item.fadeOut(300, function() {
                        $(this).remove();
                        if ($('.zask-alert-item').length === 0) {
                            location.reload();
                        }
                    });
                }
            }
        });
    });
});
</script>
