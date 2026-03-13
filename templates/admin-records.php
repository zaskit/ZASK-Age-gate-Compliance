<?php
/**
 * Compliance Records Admin Page
 */

if (!defined('ABSPATH')) exit;

global $wpdb;
$table_name = $wpdb->prefix . 'zask_compliance_records';

// Pagination
$per_page = 50;
$page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($page - 1) * $per_page;

// Get total records
$total_records = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
$total_pages = ceil($total_records / $per_page);

// Get records
$records = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $table_name ORDER BY created_at DESC LIMIT %d OFFSET %d",
    $per_page,
    $offset
));
?>

<div class="wrap zask-admin-wrap">
    <div class="zask-header">
        <h1>
            <span class="dashicons dashicons-shield"></span>
            <?php _e('Compliance Records', 'zask-age-gate'); ?>
        </h1>
        <p><?php _e('Age verification records for audit and compliance purposes', 'zask-age-gate'); ?></p>
    </div>

    <div class="zask-records-stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">
        <div class="zask-stat-card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div style="font-size: 32px; font-weight: bold; color: #10b981;"><?php echo number_format($total_records); ?></div>
            <div style="color: #6b7280;"><?php _e('Total Records', 'zask-age-gate'); ?></div>
        </div>
        
        <?php
        $verified_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE age_verified = 1");
        $agreed_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE terms_agreed = 1");
        ?>
        
        <div class="zask-stat-card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div style="font-size: 32px; font-weight: bold; color: #3b82f6;"><?php echo number_format($verified_count); ?></div>
            <div style="color: #6b7280;"><?php _e('Age Verified', 'zask-age-gate'); ?></div>
        </div>
        
        <div class="zask-stat-card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div style="font-size: 32px; font-weight: bold; color: #8b5cf6;"><?php echo number_format($agreed_count); ?></div>
            <div style="color: #6b7280;"><?php _e('Terms Agreed', 'zask-age-gate'); ?></div>
        </div>
    </div>

    <div class="zask-records-actions" style="margin: 20px 0; display: flex; gap: 10px;">
        <a href="<?php echo admin_url('admin-ajax.php?action=zask_export_csv&nonce=' . wp_create_nonce('zask_admin_nonce')); ?>" class="button button-primary">
            <span class="dashicons dashicons-download"></span>
            <?php _e('Export CSV', 'zask-age-gate'); ?>
        </a>
        <button type="button" class="button" id="zask-delete-old-records">
            <span class="dashicons dashicons-trash"></span>
            <?php _e('Delete Records Older Than 1 Year', 'zask-age-gate'); ?>
        </button>
    </div>

    <?php
    // Build a flat list of all unique custom-data keys across the page of records
    // so we can create dynamic columns
    $all_field_keys = array();
    $all_cb_keys = array();
    $records_custom = array(); // record id => parsed custom_data
    
    foreach ($records as $record) {
        $cd = array();
        if (!empty($record->custom_data)) {
            $cd = json_decode($record->custom_data, true);
            if (!is_array($cd)) $cd = array();
        }
        $records_custom[$record->id] = $cd;
        
        if (!empty($cd['fields']) && is_array($cd['fields'])) {
            foreach (array_keys($cd['fields']) as $k) {
                $all_field_keys[$k] = $k;
            }
        }
        if (!empty($cd['checkboxes']) && is_array($cd['checkboxes'])) {
            foreach (array_keys($cd['checkboxes']) as $k) {
                $all_cb_keys[$k] = $k;
            }
        }
    }
    
    // Also get current config so we have a complete set of column names
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
    ?>

    <?php if ($records): ?>
        <div class="zask-records-table" style="background: white; border-radius: 8px; overflow-x: auto; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 50px;"><?php _e('ID', 'zask-age-gate'); ?></th>
                        <th><?php _e('Full Name', 'zask-age-gate'); ?></th>
                        <th><?php _e('Username', 'zask-age-gate'); ?></th>
                        <th><?php _e('Email', 'zask-age-gate'); ?></th>
                        <th><?php _e('Age Verified', 'zask-age-gate'); ?></th>
                        <th><?php _e('Terms Agreed', 'zask-age-gate'); ?></th>
                        <th><?php _e('Business Type', 'zask-age-gate'); ?></th>
                        <?php foreach ($all_field_keys as $fk): ?>
                            <th class="zask-custom-col"><?php echo esc_html($fk); ?></th>
                        <?php endforeach; ?>
                        <?php foreach ($all_cb_keys as $ck): ?>
                            <th class="zask-custom-col"><?php echo esc_html($ck); ?></th>
                        <?php endforeach; ?>
                        <th><?php _e('Created At', 'zask-age-gate'); ?></th>
                        <th style="width: 80px;"><?php _e('Actions', 'zask-age-gate'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($records as $record):
                        $cd = $records_custom[$record->id] ?? array();
                        $cd_fields = $cd['fields'] ?? array();
                        $cd_cbs = $cd['checkboxes'] ?? array();
                        // Look up WP username from user_id
                        $wp_username = '—';
                        if (!empty($record->user_id)) {
                            $wp_user = get_userdata($record->user_id);
                            if ($wp_user) {
                                $wp_username = $wp_user->user_login;
                            }
                        }
                    ?>
                        <tr>
                            <td><?php echo esc_html($record->id); ?></td>
                            <td><?php echo esc_html($record->full_name ?: 'N/A'); ?></td>
                            <td><?php echo esc_html($wp_username); ?></td>
                            <td><?php echo esc_html($record->email ?: 'N/A'); ?></td>
                            <td>
                                <?php if ($record->age_verified): ?>
                                    <span style="color: #10b981;">✓ <?php _e('Yes', 'zask-age-gate'); ?></span>
                                <?php else: ?>
                                    <span style="color: #ef4444;">✗ <?php _e('No', 'zask-age-gate'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($record->terms_agreed): ?>
                                    <span style="color: #10b981;">✓ <?php _e('Yes', 'zask-age-gate'); ?></span>
                                <?php else: ?>
                                    <span style="color: #ef4444;">✗ <?php _e('No', 'zask-age-gate'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($record->business_type ?: 'N/A'); ?></td>
                            <?php foreach ($all_field_keys as $fk): ?>
                                <td><?php echo esc_html(isset($cd_fields[$fk]) ? $cd_fields[$fk] : '—'); ?></td>
                            <?php endforeach; ?>
                            <?php foreach ($all_cb_keys as $ck): ?>
                                <td>
                                    <?php if (isset($cd_cbs[$ck])): ?>
                                        <?php if ($cd_cbs[$ck]): ?>
                                            <span style="color: #10b981;">✓ <?php _e('Yes', 'zask-age-gate'); ?></span>
                                        <?php else: ?>
                                            <span style="color: #ef4444;">✗ <?php _e('No', 'zask-age-gate'); ?></span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                            <td><?php echo esc_html(date('Y-m-d H:i', strtotime($record->created_at))); ?></td>
                            <td>
                                <button type="button" class="button button-small zask-delete-record" data-id="<?php echo esc_attr($record->id); ?>">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
            <div class="tablenav" style="margin-top: 20px;">
                <div class="tablenav-pages">
                    <?php
                    echo paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => '&laquo; ' . __('Previous', 'zask-age-gate'),
                        'next_text' => __('Next', 'zask-age-gate') . ' &raquo;',
                        'total' => $total_pages,
                        'current' => $page,
                    ));
                    ?>
                </div>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="zask-empty-state" style="background: white; padding: 60px; text-align: center; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <span class="dashicons dashicons-shield" style="font-size: 80px; opacity: 0.3;"></span>
            <h3><?php _e('No Records Yet', 'zask-age-gate'); ?></h3>
            <p><?php _e('Age verification records will appear here once users start verifying.', 'zask-age-gate'); ?></p>
        </div>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    // Delete single record
    $('.zask-delete-record').on('click', function() {
        if (!confirm('<?php _e('Are you sure you want to delete this record?', 'zask-age-gate'); ?>')) {
            return;
        }
        
        const recordId = $(this).data('id');
        const $row = $(this).closest('tr');
        
        $.post(ajaxurl, {
            action: 'zask_delete_record',
            nonce: '<?php echo wp_create_nonce('zask_admin_nonce'); ?>',
            record_id: recordId
        }, function(response) {
            if (response.success) {
                $row.fadeOut(function() {
                    $(this).remove();
                });
            } else {
                alert('<?php _e('Error deleting record', 'zask-age-gate'); ?>');
            }
        });
    });
    
    // Delete old records
    $('#zask-delete-old-records').on('click', function() {
        if (!confirm('<?php _e('Delete all records older than 1 year? This cannot be undone.', 'zask-age-gate'); ?>')) {
            return;
        }
        
        const $btn = $(this);
        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update-alt"></span> Deleting...');
        
        $.post(ajaxurl, {
            action: 'zask_delete_old_records',
            nonce: '<?php echo wp_create_nonce('zask_admin_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                alert(response.data.message);
                location.reload();
            } else {
                alert('<?php _e('Error deleting records', 'zask-age-gate'); ?>');
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-trash"></span> Delete Records Older Than 1 Year');
            }
        });
    });
});
</script>
