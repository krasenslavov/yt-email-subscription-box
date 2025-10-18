# YT Email Subscription Box

A lightweight WordPress plugin that provides a simple email subscription form with local storage, AJAX submission, and CSV export functionality.

## Features

- **Simple Subscription Form** - Clean, responsive opt-in form
- **Local Storage** - Stores subscribers in custom database table
- **AJAX Submission** - No page reload required
- **Duplicate Prevention** - Automatically prevents duplicate emails
- **CSV Export** - Export all subscribers to CSV file
- **Confirmation Emails** - Optional welcome emails for new subscribers
- **Admin Dashboard** - View and manage subscribers
- **IP Tracking** - Records subscriber IP addresses
- **Pagination** - Admin list with 20 subscribers per page
- **Shortcode Ready** - Easy integration with `[yt_subscribe_box]`
- **WPCS Compliant** - Follows WordPress Coding Standards
- **Secure** - Nonce verification, sanitization, and escaping

## Installation

1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to **Subscribers → Settings** to configure options
4. Use the shortcode `[yt_subscribe_box]` in any post or page

## File Structure

```
yt-email-subscription-box/
├── class-yt-email-subscription-box.php  # Main plugin file (505 lines)
├── assets/
│   ├── css/
│   │   └── style.css                     # Frontend styles
│   └── js/
│       └── script.js                     # AJAX functionality
└── README.md                             # Documentation
```

## Usage

### Basic Shortcode

```php
[yt_subscribe_box]
```

### Shortcode with Attributes

```php
[yt_subscribe_box title="Join Our Newsletter" button_text="Subscribe Now"]
```

### Available Attributes

| Attribute | Description | Default |
|-----------|-------------|---------|
| `title` | Form heading | "Subscribe to Our Newsletter" |
| `button_text` | Submit button text | "Subscribe" |
| `class` | Additional CSS class | "" |

### Example Usage in Posts

```
<!-- Basic usage -->
[yt_subscribe_box]

<!-- Custom title -->
[yt_subscribe_box title="Get Weekly Updates!"]

<!-- Custom button -->
[yt_subscribe_box button_text="Join Now"]

<!-- Custom styling -->
[yt_subscribe_box class="my-custom-class"]

<!-- All attributes -->
[yt_subscribe_box title="Newsletter Signup" button_text="Subscribe" class="sidebar-form"]
```

### Template File Usage

```php
<?php echo do_shortcode( '[yt_subscribe_box title="Subscribe"]' ); ?>
```

## Admin Features

### Subscribers List

Access via **Subscribers** menu in WordPress admin.

Features:
- View all subscribers with email, IP, and date
- Pagination (20 per page)
- Total subscriber count
- One-click CSV export

### Settings Page

Access via **Subscribers → Settings**.

Options:
- **Send Confirmation Email** - Enable/disable welcome emails
- **Confirmation Subject** - Email subject line
- **Confirmation Message** - Email body (supports `{email}` placeholder)

### CSV Export

1. Go to **Subscribers** page
2. Click **Export to CSV** button
3. File downloads as `subscribers-YYYY-MM-DD.csv`

CSV format:
```
ID,Email,IP Address,Subscribed At
1,user@example.com,192.168.1.1,2025-01-01 12:00:00
```

## Database Schema

The plugin creates a custom table: `wp_yt_email_subscribers`

### Table Structure

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint(20) | Primary key, auto-increment |
| `email` | varchar(100) | Subscriber email (unique) |
| `ip_address` | varchar(45) | Subscriber IP address |
| `subscribed_at` | datetime | Subscription date and time |

### SQL Query

```sql
CREATE TABLE wp_yt_email_subscribers (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    email varchar(100) NOT NULL,
    ip_address varchar(45) NOT NULL,
    subscribed_at datetime NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## Confirmation Email

### Configuration

1. Go to **Subscribers → Settings**
2. Check **Send Confirmation Email**
3. Set **Confirmation Subject**: `Welcome to our newsletter!`
4. Set **Confirmation Message**: `Thank you for subscribing! Your email: {email}`

### Email Placeholder

Use `{email}` in your message to include the subscriber's email address.

Example:
```
Hello!

Thank you for subscribing with {email}.

We're excited to have you on board!

Best regards,
The Team
```

## Security Features

✅ **Implemented Security Measures:**

- **Nonce Verification** - AJAX requests verified with nonces
- **Email Validation** - Server-side email validation with `is_email()`
- **Sanitization** - All inputs sanitized (`sanitize_email()`, `sanitize_text_field()`)
- **Escaping** - All outputs escaped (`esc_html()`, `esc_attr()`, `esc_url()`)
- **Capability Checks** - Admin functions require `manage_options`
- **Prepared Statements** - SQL queries use `$wpdb->prepare()`
- **Duplicate Prevention** - Unique key constraint on email column
- **IP Sanitization** - IP addresses properly sanitized
- **Direct Access Prevention** - File access protection with `WPINC` check

## AJAX Flow

1. User enters email in form
2. JavaScript validates email format
3. Form submission prevented, AJAX request sent
4. PHP validates email, checks for duplicates
5. Email inserted into database
6. Optional confirmation email sent
7. Success/error message displayed
8. Form cleared on success

## Hooks & Filters

### Available Hooks

While the plugin doesn't expose custom hooks in this version, you can use WordPress core hooks:

```php
// Modify confirmation email
add_filter( 'wp_mail', 'custom_yt_esb_email', 10, 1 );
function custom_yt_esb_email( $args ) {
    // Modify email arguments
    return $args;
}

// Run code after plugin activation
add_action( 'activated_plugin', 'custom_yt_esb_activated', 10, 2 );
function custom_yt_esb_activated( $plugin, $network_wide ) {
    if ( $plugin === 'yt-email-subscription-box/class-yt-email-subscription-box.php' ) {
        // Custom activation code
    }
}
```

## Customization

### CSS Customization

Override default styles in your theme:

```css
/* Custom wrapper styles */
.yt-esb-wrapper {
    background: #ffffff;
    border: 2px solid #000;
}

/* Custom button styles */
.yt-esb-button {
    background-color: #ff5722;
}

.yt-esb-button:hover {
    background-color: #e64a19;
}

/* Custom input styles */
.yt-esb-input {
    border-color: #000;
}
```

### JavaScript Customization

Extend the form behavior:

```javascript
jQuery(document).ready(function($) {
    // Add custom validation
    $('.yt-esb-form').on('submit', function(e) {
        const email = $(this).find('.yt-esb-input').val();

        // Custom validation logic
        if (email.includes('spam')) {
            e.preventDefault();
            alert('Invalid email domain');
            return false;
        }
    });

    // Track subscription attempts
    $('.yt-esb-form').on('submit', function() {
        console.log('Subscription attempt:', new Date());
    });
});
```

## Uninstall

The plugin provides a clean uninstall process:

1. Deactivate the plugin
2. Delete the plugin files
3. Database table and options are automatically removed

### What Gets Deleted

- Custom database table: `wp_yt_email_subscribers`
- Plugin options: `yt_esb_options`
- All subscriber data
- All settings

## Development

### Constants Defined

```php
YT_ESB_VERSION  // Plugin version: 1.0.0
YT_ESB_BASENAME // Plugin basename
YT_ESB_PATH     // Plugin directory path
YT_ESB_URL      // Plugin directory URL
```

### Main Class Methods

| Method | Description |
|--------|-------------|
| `get_instance()` | Get singleton instance |
| `init_hooks()` | Register WordPress hooks |
| `load_textdomain()` | Load translations |
| `enqueue_scripts()` | Enqueue frontend CSS/JS |
| `admin_enqueue_scripts()` | Enqueue admin CSS |
| `add_admin_menu()` | Add admin menu pages |
| `register_settings()` | Register plugin settings |
| `sanitize_options()` | Sanitize settings input |
| `render_admin_page()` | Display subscribers list |
| `render_settings_page()` | Display settings page |
| `handle_csv_export()` | Export subscribers to CSV |
| `render_shortcode()` | Render subscription form |
| `handle_subscription()` | Process AJAX subscription |
| `send_confirmation_email()` | Send welcome email |
| `get_user_ip()` | Get subscriber IP address |
| `activate()` | Plugin activation |
| `deactivate()` | Plugin deactivation |

## Troubleshooting

### Form Not Submitting

1. Check JavaScript console for errors
2. Ensure jQuery is loaded
3. Verify AJAX URL is correct
4. Check nonce verification

### Emails Not Sending

1. Verify **Send Confirmation Email** is enabled
2. Check WordPress email configuration
3. Install SMTP plugin (e.g., WP Mail SMTP)
4. Check spam folder

### Duplicate Email Error

This is expected behavior. The plugin prevents duplicate subscriptions.

### CSV Export Not Working

1. Verify you have `manage_options` capability
2. Check file permissions
3. Ensure nonce is valid
4. Check for PHP errors in debug log

### Subscribers Not Showing

1. Check database table was created on activation
2. Verify table name: `wp_yt_email_subscribers`
3. Check database permissions
4. Re-activate the plugin

## Performance

- **Database:** Single table with indexed email column
- **AJAX:** Asynchronous form submission
- **Assets:** Minified CSS/JS (optional)
- **Queries:** Optimized with pagination
- **Caching:** Compatible with caching plugins

## Browser Support

- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+
- Opera 76+

## Requirements

- **WordPress:** 5.8 or higher
- **PHP:** 7.4 or higher
- **MySQL:** 5.6 or higher
- **jQuery:** Included with WordPress

## Changelog

### 1.0.0 (2025-01-18)
- Initial release
- Email subscription form with AJAX
- Local database storage
- CSV export functionality
- Confirmation email support
- Admin dashboard
- WPCS compliant code

## License

GPL v2 or later

## Credits

Built following WordPress Plugin Handbook and WPCS guidelines.

## Support

For issues and feature requests, please visit:
- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)

## Author

Krasen Slavov
- Website: https://krasenslavov.com
- GitHub: https://github.com/krasenslavov
