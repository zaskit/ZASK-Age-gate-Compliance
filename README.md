# ZASK Age-Gate & Compliance System

Advanced age verification and compliance system for WordPress/WooCommerce websites selling research chemicals and high-risk products.

## Version 1.0.0

## Features

### Core Features
- ✅ **4-Stage Gate System** (Stage 0-3)
- ✅ **Two Display Modes** (Modal Popup & Full Page Block)
- ✅ **Session Management** (Customizable duration & browser close logout)
- ✅ **Modern Toggle Interface** (Login/Register switch - NO tabs!)
- ✅ **Geographic Compliance** (State-specific age requirements)
- ✅ **FDA Monitoring** (Automated alerts for regulatory changes)
- ✅ **Email Verification** (Optional 6-digit code system)
- ✅ **Professional Verification** (Stage 3 - Business credentials)
- ✅ **Compliance Database** (Complete audit trail)
- ✅ **CSV Export** (Full compliance records)

### What Makes ZASK Different
- **NO "Remember Me"** checkbox - session duration controlled by admin
- **Toggle Switch** instead of tabs for better UX
- **Geographic Intelligence** - automatic state-specific age requirements
- **FDA Alert System** - real-time regulatory monitoring
- **Professional Tier** - business license verification
- **Modern Dashboard** - card-based admin interface (not accordions!)

## Installation

1. **Upload Plugin**
   - Upload `zask-age-gate` folder to `/wp-content/plugins/`
   - OR install via WordPress admin: Plugins → Add New → Upload Plugin

2. **Activate Plugin**
   - Go to Plugins → Installed Plugins
   - Click "Activate" on ZASK Age-Gate

3. **Enter License Key**
   - Go to Age-Gate → Settings → License tab
   - Enter your license key from zask.it
   - Click "Activate License"

4. **Configure Settings**
   - Go to Age-Gate → Settings
   - Choose your gate stage (0-3)
   - Select display mode (Modal or Full Page)
   - Set session duration
   - Save settings

## Configuration Guide

### Stage Selection

**Stage 0: No Gate**
- Unrestricted access
- No compliance tracking
- Use for testing or disabled state

**Stage 1: Lightweight** (Recommended for most sites)
- Simple age + terms attestation
- Session-based (no account needed)
- Fast and low-friction

**Stage 2: Full Verification**
- User account required
- Email verification (optional)
- Database compliance tracking
- Best for high-compliance needs

**Stage 3: Professional**
- All Stage 2 features
- Business type selection
- Professional credentials
- For B2B/research institutions

### Display Modes

**Modal Popup** (Default)
- Overlay on content
- Users can see background
- Less intrusive
- Better for returning visitors

**Full Page Block**
- Complete page coverage
- Nothing visible until verified
- More secure
- Better for first-time visitors

### Session Management

**Session Duration**
- Set from 1 to 720 hours (30 days)
- Default: 2 hours
- Recommendation: 2-48 hours for compliance

**Logout on Browser Close**
- When enabled: Session expires when browser closes
- When disabled: Session lasts for set duration
- Recommendation: Enable for high-security sites

## Geographic Compliance

ZASK automatically detects user location and applies state-specific rules:

- **New York**: 18+ for muscle/weight products
- **New Jersey**: 18+ with parental consent requirement
- **California**: 18+ for muscle/weight products
- **Massachusetts**: 18+ for muscle products
- **Illinois**: 18+ for muscle/weight products
- **Virginia, Texas, New Hampshire**: 18+ for muscle products

*Requires Professional or Enterprise license*

## FDA Monitoring

Automatic monitoring of FDA warnings and alerts:
- Daily RSS feed checks
- Peptide status updates
- Warning letter notifications
- Email alerts to admin
- Dashboard notification center

*Requires Professional or Enterprise license*

## Minimum Requirements

- WordPress 6.0+
- PHP 7.4+
- MySQL 5.7+
- WooCommerce 6.0+ (recommended but not required)

## Recommended Server Settings

```
PHP Memory Limit: 256MB
Max Execution Time: 300 seconds
Max Upload Size: 64MB
```

## File Structure

```
zask-age-gate/
├── zask-age-gate.php          # Main plugin file
├── readme.txt                  # WordPress.org readme
├── LICENSE.txt                 # GPL v3 License
├── includes/                   # Core PHP classes
│   ├── class-zask-compliance-engine.php
│   ├── class-zask-license.php
│   ├── class-zask-geo-compliance.php
│   └── class-zask-fda-monitor.php
├── assets/                     # Frontend & admin assets
│   ├── css/
│   │   ├── gate.css           # Frontend gate styles
│   │   └── admin.css          # Admin dashboard styles
│   ├── js/
│   │   ├── gate.js            # Frontend interactions
│   │   └── admin.js           # Admin interactions
│   └── images/                # Plugin images
├── templates/                  # PHP templates
│   ├── gate-modal.php         # Modal template
│   ├── gate-fullpage.php      # Fullpage template
│   ├── admin-settings.php     # Settings page
│   ├── admin-records.php      # Compliance records
│   └── admin-fda.php          # FDA monitor page
└── languages/                  # Translation files
```

## Shortcodes

Currently, ZASK Age-Gate does not use shortcodes. The gate is automatically displayed based on your configuration.

## Hooks & Filters

### Actions

```php
// Before gate is rendered
do_action('zask_before_gate_render', $stage, $display_mode);

// After user verification
do_action('zask_after_verification', $user_id, $verification_type);

// After settings save
do_action('zask_settings_saved', $settings);
```

### Filters

```php
// Customize minimum age
add_filter('zask_minimum_age', function($age, $state) {
    return $age;
}, 10, 2);

// Customize gate display
add_filter('zask_show_gate', function($show, $context) {
    return $show;
}, 10, 2);
```

## Database Tables

### zask_compliance_records
Stores all user verification data:
- User ID & session ID
- Email & verification status
- Age attestation
- Terms agreement
- Business information
- IP address & geolocation
- Timestamps

### zask_terms_versions
Tracks all terms & conditions versions:
- Terms text snapshots
- Version history
- Creation dates

### zask_fda_alerts
FDA monitoring alerts:
- Alert type & content
- Peptide name
- Source URL
- Read status

## Troubleshooting

### Gate Not Showing
1. Check if gate is enabled (Settings → General)
2. Clear browser cookies
3. Check user is not already verified
4. Clear site cache

### Session Not Persisting
1. Check session duration setting
2. Verify "Logout on Browser Close" setting
3. Check PHP session configuration
4. Test in incognito/private window

### License Issues
1. Verify license key is correct
2. Check domain matches licensed domain
3. Confirm license is active on zask.it
4. Contact support@zask.it

## Support

- **Documentation**: https://zask.it/docs/age-gate
- **Support Email**: support@zask.it
- **License Portal**: https://zask.it/account
- **Updates**: Automatic via WordPress admin

## Changelog

### 1.0.0 - 2026-02-10
- Initial release
- 4-stage gate system
- Modal & fullpage display modes
- Session management
- Geographic compliance
- FDA monitoring
- License system integration

## License

This plugin is licensed under GPL v3 or later.
Commercial license required for use (via zask.it)

## Credits

Developed by ZASK Digital Solutions
© 2026 ZASK.it - All Rights Reserved

## Privacy & GDPR

ZASK Age-Gate collects and stores:
- Email addresses
- IP addresses
- User agent strings
- Geolocation data (country, state, city)
- Age attestation confirmations
- Terms agreement records

All data is stored securely and used solely for compliance purposes.

For GDPR compliance:
- Users can request data deletion
- Data is exportable via CSV
- Clear privacy policy integration recommended
- Cookie consent may be required (depending on jurisdiction)

## Security

- All AJAX requests use WordPress nonces
- SQL queries use prepared statements
- User input is sanitized
- Passwords are hashed
- Session cookies are HTTP-only
- HTTPS recommended for production

## Performance

- Minimal database queries
- CSS/JS minified in production
- Transient caching for geolocation
- Optimized for high-traffic sites
- No external dependencies (except license check)

## Roadmap

- v1.1: Multi-language support
- v1.2: Advanced analytics dashboard
- v1.3: API for third-party integrations
- v1.4: White-label customization
- v1.5: Mobile app integration

## FAQ

**Q: Do I need WooCommerce?**
A: No, but it's recommended for e-commerce sites.

**Q: Can I customize the gate design?**
A: Yes, via custom CSS or filter hooks.

**Q: Does it work with caching plugins?**
A: Yes, but you may need to exclude the gate from cache.

**Q: Can I test without a license?**
A: Limited functionality available without license.

**Q: Is it translation-ready?**
A: Yes, .pot file included for translations.

**Q: Can users bypass the gate?**
A: No, unless they're logged in and verified, or gate is disabled.

## Developer Notes

For developers extending ZASK Age-Gate:

```php
// Check if user is verified
if (ZASK_Compliance_Engine::is_user_verified()) {
    // User has passed gate
}

// Get compliance record
$record = ZASK_Compliance_Engine::get_compliance_record($user_id);

// Force gate display
add_filter('zask_show_gate', '__return_true');
```

---

**Need Help?** Contact support@zask.it or visit https://zask.it/support
