<?php
/**
 * Plugin Name: YT Email Subscription Box
 * Plugin URI: https://github.com/krasenslavov/yt-email-subscription-box
 * Description: Simple opt-in subscription form that stores emails locally with CSV export functionality.
 * Version: 1.0.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: Krasen Slavov
 * Author URI: https://krasenslavov.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: yt-email-subscription-box
 * Domain Path: /languages
 *
 * @package YT_Email_Subscription_Box
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Plugin version.
 */
define( 'YT_ESB_VERSION', '1.0.0' );

/**
 * Plugin base name.
 */
define( 'YT_ESB_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Plugin directory path.
 */
define( 'YT_ESB_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Plugin directory URL.
 */
define( 'YT_ESB_URL', plugin_dir_url( __FILE__ ) );

/**
 * Main plugin class.
 *
 * @since 1.0.0
 */
class YT_Email_Subscription_Box {

	/**
	 * Single instance.
	 *
	 * @var YT_Email_Subscription_Box|null
	 */
	private static $instance = null;

	/**
	 * Database table name.
	 *
	 * @var string
	 */
	private $table_name;

	/**
	 * Plugin options.
	 *
	 * @var array
	 */
	private $options = array();

	/**
	 * Get instance.
	 *
	 * @return YT_Email_Subscription_Box
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'yt_email_subscribers';
		$this->options    = get_option( 'yt_esb_options', array() );
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	private function init_hooks() {
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_shortcode( 'yt_subscribe_box', array( $this, 'render_shortcode' ) );
		add_action( 'wp_ajax_yt_esb_subscribe', array( $this, 'handle_subscription' ) );
		add_action( 'wp_ajax_nopriv_yt_esb_subscribe', array( $this, 'handle_subscription' ) );

		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
			add_action( 'admin_init', array( $this, 'register_settings' ) );
			add_action( 'admin_init', array( $this, 'handle_csv_export' ) );
			add_filter( 'plugin_action_links_' . YT_ESB_BASENAME, array( $this, 'add_action_links' ) );
		}
	}

	/**
	 * Load text domain.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'yt-email-subscription-box', false, dirname( YT_ESB_BASENAME ) . '/languages' );
	}

	/**
	 * Enqueue frontend scripts.
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		wp_enqueue_style( 'yt-esb-style', YT_ESB_URL . 'assets/css/style.css', array(), YT_ESB_VERSION );
		wp_enqueue_script( 'yt-esb-script', YT_ESB_URL . 'assets/js/script.js', array( 'jquery' ), YT_ESB_VERSION, true );
		wp_localize_script(
			'yt-esb-script',
			'ytEsbAjax',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'yt_esb_nonce' ),
			)
		);
	}

	/**
	 * Enqueue admin scripts.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function admin_enqueue_scripts( $hook ) {
		if ( 'toplevel_page_yt-email-subscribers' !== $hook ) {
			return;
		}
		wp_enqueue_style( 'yt-esb-admin', YT_ESB_URL . 'assets/css/admin.css', array(), YT_ESB_VERSION );
	}

	/**
	 * Add admin menu.
	 *
	 * @return void
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'Email Subscribers', 'yt-email-subscription-box' ),
			__( 'Subscribers', 'yt-email-subscription-box' ),
			'manage_options',
			'yt-email-subscribers',
			array( $this, 'render_admin_page' ),
			'dashicons-email-alt',
			30
		);

		add_submenu_page(
			'yt-email-subscribers',
			__( 'Settings', 'yt-email-subscription-box' ),
			__( 'Settings', 'yt-email-subscription-box' ),
			'manage_options',
			'yt-email-subscribers-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register settings.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting( 'yt_esb_options_group', 'yt_esb_options', array( $this, 'sanitize_options' ) );
		add_settings_section( 'yt_esb_main_section', __( 'Email Settings', 'yt-email-subscription-box' ), null, 'yt-email-subscribers-settings' );
		add_settings_field( 'send_confirmation', __( 'Send Confirmation Email', 'yt-email-subscription-box' ), array( $this, 'render_confirmation_field' ), 'yt-email-subscribers-settings', 'yt_esb_main_section' );
		add_settings_field( 'confirmation_subject', __( 'Confirmation Subject', 'yt-email-subscription-box' ), array( $this, 'render_subject_field' ), 'yt-email-subscribers-settings', 'yt_esb_main_section' );
		add_settings_field( 'confirmation_message', __( 'Confirmation Message', 'yt-email-subscription-box' ), array( $this, 'render_message_field' ), 'yt-email-subscribers-settings', 'yt_esb_main_section' );
	}

	/**
	 * Sanitize options.
	 *
	 * @param array $input Raw input.
	 * @return array Sanitized options.
	 */
	public function sanitize_options( $input ) {
		$sanitized                         = array();
		$sanitized['send_confirmation']    = ! empty( $input['send_confirmation'] );
		$sanitized['confirmation_subject'] = sanitize_text_field( $input['confirmation_subject'] ?? '' );
		$sanitized['confirmation_message'] = wp_kses_post( $input['confirmation_message'] ?? '' );
		return $sanitized;
	}

	/**
	 * Render confirmation checkbox.
	 */
	public function render_confirmation_field() {
		printf(
			'<input type="checkbox" name="yt_esb_options[send_confirmation]" value="1" %s /> <label>%s</label>',
			checked( $this->options['send_confirmation'] ?? false, true, false ),
			esc_html__( 'Send confirmation email to new subscribers', 'yt-email-subscription-box' )
		);
	}

	/**
	 * Render subject field.
	 */
	public function render_subject_field() {
		printf(
			'<input type="text" name="yt_esb_options[confirmation_subject]" value="%s" class="regular-text" />',
			esc_attr( $this->options['confirmation_subject'] ?? __( 'Welcome to our newsletter!', 'yt-email-subscription-box' ) )
		);
	}

	/**
	 * Render message field.
	 */
	public function render_message_field() {
		printf(
			'<textarea name="yt_esb_options[confirmation_message]" rows="5" class="large-text">%s</textarea><p class="description">%s</p>',
			esc_textarea( $this->options['confirmation_message'] ?? __( 'Thank you for subscribing!', 'yt-email-subscription-box' ) ),
			esc_html__( 'Use {email} placeholder for subscriber email.', 'yt-email-subscription-box' )
		);
	}

	/**
	 * Add action links.
	 *
	 * @param array $links Existing links.
	 * @return array Modified links.
	 */
	public function add_action_links( $links ) {
		$custom_links = array(
			'<a href="' . esc_url( admin_url( 'admin.php?page=yt-email-subscribers' ) ) . '">' . esc_html__( 'Subscribers', 'yt-email-subscription-box' ) . '</a>',
			'<a href="' . esc_url( admin_url( 'admin.php?page=yt-email-subscribers-settings' ) ) . '">' . esc_html__( 'Settings', 'yt-email-subscription-box' ) . '</a>',
		);
		return array_merge( $custom_links, $links );
	}

	/**
	 * Render admin page.
	 */
	public function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions.', 'yt-email-subscription-box' ) );
		}
		$page     = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
		$per_page = 20;
		global $wpdb;
		$total       = $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name}" ); // phpcs:ignore
		$subscribers = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->table_name} ORDER BY subscribed_at DESC LIMIT %d OFFSET %d", $per_page, ( $page - 1 ) * $per_page ) ); // phpcs:ignore
		$total_pages = ceil( $total / $per_page );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Email Subscribers', 'yt-email-subscription-box' ); ?></h1>
			<p><?php printf( esc_html__( 'Total: %d', 'yt-email-subscription-box' ), intval( $total ) ); // phpcs:ignore ?></p>
			<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=yt-email-subscribers&export=csv&_wpnonce=' . wp_create_nonce( 'yt_esb_export' ) ) ); ?>" class="button button-primary"><?php esc_html_e( 'Export CSV', 'yt-email-subscription-box' ); ?></a></p>
			<table class="wp-list-table widefat fixed striped">
				<thead><tr>
					<th><?php esc_html_e( 'ID', 'yt-email-subscription-box' ); ?></th>
					<th><?php esc_html_e( 'Email', 'yt-email-subscription-box' ); ?></th>
					<th><?php esc_html_e( 'IP', 'yt-email-subscription-box' ); ?></th>
					<th><?php esc_html_e( 'Date', 'yt-email-subscription-box' ); ?></th>
				</tr></thead>
				<tbody>
				<?php if ( $subscribers ) : ?>
					<?php foreach ( $subscribers as $sub ) : ?>
						<tr>
							<td><?php echo intval( $sub->id ); ?></td>
							<td><?php echo esc_html( $sub->email ); ?></td>
							<td><?php echo esc_html( $sub->ip_address ); ?></td>
							<td><?php echo esc_html( $sub->subscribed_at ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr><td colspan="4"><?php esc_html_e( 'No subscribers yet.', 'yt-email-subscription-box' ); ?></td></tr>
				<?php endif; ?>
				</tbody>
			</table>
			<?php
			if ( $total_pages > 1 ) {
				echo '<div class="tablenav"><div class="tablenav-pages">';
				echo paginate_links(
					array(
						'base'      => add_query_arg( 'paged', '%#%' ),
						'format'    => '',
						'prev_text' => '&laquo;',
						'next_text' => '&raquo;',
						'total'     => $total_pages,
						'current'   => $page,
					)
				);
				echo '</div></div>';
			}
			?>
		</div>
		<?php
	}

	/**
	 * Render settings page.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions.', 'yt-email-subscription-box' ) );
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Email Subscription Settings', 'yt-email-subscription-box' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'yt_esb_options_group' );
				do_settings_sections( 'yt-email-subscribers-settings' );
				submit_button();
				?>
			</form>
			<hr>
			<h2><?php esc_html_e( 'Shortcode Usage', 'yt-email-subscription-box' ); ?></h2>
			<code>[yt_subscribe_box title="Subscribe" button_text="Subscribe"]</code>
		</div>
		<?php
	}

	/**
	 * Handle CSV export.
	 */
	public function handle_csv_export() {
		if ( ! isset( $_GET['export'], $_GET['_wpnonce'] ) || 'csv' !== $_GET['export'] ) {
			return;
		}
		if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'yt_esb_export' ) || ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'yt-email-subscription-box' ) );
		}
		global $wpdb;
		$subscribers = $wpdb->get_results( "SELECT * FROM {$this->table_name} ORDER BY subscribed_at DESC", ARRAY_A ); // phpcs:ignore
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=subscribers-' . gmdate( 'Y-m-d' ) . '.csv' );
		$output = fopen( 'php://output', 'w' );
		fputcsv( $output, array( 'ID', 'Email', 'IP Address', 'Subscribed At' ) );
		foreach ( $subscribers as $subscriber ) {
			fputcsv( $output, $subscriber );
		}
		fclose( $output );
		exit;
	}

	/**
	 * Render subscription box shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'title'       => __( 'Subscribe to Our Newsletter', 'yt-email-subscription-box' ),
				'button_text' => __( 'Subscribe', 'yt-email-subscription-box' ),
				'class'       => '',
			),
			$atts,
			'yt_subscribe_box'
		);

		ob_start();
		?>
		<div class="yt-esb-wrapper <?php echo esc_attr( $atts['class'] ); ?>">
			<form class="yt-esb-form" data-nonce="<?php echo esc_attr( wp_create_nonce( 'yt_esb_nonce' ) ); ?>">
				<?php if ( ! empty( $atts['title'] ) ) : ?>
					<h3 class="yt-esb-title"><?php echo esc_html( $atts['title'] ); ?></h3>
				<?php endif; ?>
				<div class="yt-esb-field">
					<input type="email" name="email" class="yt-esb-input" placeholder="<?php esc_attr_e( 'Enter your email', 'yt-email-subscription-box' ); ?>" required />
				</div>
				<div class="yt-esb-field">
					<button type="submit" class="yt-esb-button"><?php echo esc_html( $atts['button_text'] ); ?></button>
				</div>
				<div class="yt-esb-message"></div>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Handle subscription via AJAX.
	 */
	public function handle_subscription() {
		check_ajax_referer( 'yt_esb_nonce', 'nonce' );
		$email = sanitize_email( $_POST['email'] ?? '' );
		if ( ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a valid email address.', 'yt-email-subscription-box' ) ) );
		}
		global $wpdb;
		if ( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$this->table_name} WHERE email = %s", $email ) ) ) { // phpcs:ignore
			wp_send_json_error( array( 'message' => __( 'This email is already subscribed.', 'yt-email-subscription-box' ) ) );
		}
		$inserted = $wpdb->insert(
			$this->table_name,
			array(
				'email'         => $email,
				'ip_address'    => $this->get_user_ip(),
				'subscribed_at' => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s' )
		);
		if ( ! $inserted ) {
			wp_send_json_error( array( 'message' => __( 'Failed to subscribe. Please try again.', 'yt-email-subscription-box' ) ) );
		}
		if ( ! empty( $this->options['send_confirmation'] ) ) {
			$this->send_confirmation_email( $email );
		}
		wp_send_json_success( array( 'message' => __( 'Thank you for subscribing!', 'yt-email-subscription-box' ) ) );
	}

	/**
	 * Send confirmation email.
	 *
	 * @param string $email Subscriber email.
	 */
	private function send_confirmation_email( $email ) {
		$subject = $this->options['confirmation_subject'] ?? __( 'Welcome!', 'yt-email-subscription-box' );
		$message = str_replace( '{email}', $email, $this->options['confirmation_message'] ?? __( 'Thank you for subscribing!', 'yt-email-subscription-box' ) );
		wp_mail( $email, $subject, $message );
	}

	/**
	 * Get user IP address.
	 *
	 * @return string
	 */
	private function get_user_ip() {
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			return sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
		}
		if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			return sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
		}
		return ! empty( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0.0.0.0';
	}

	/**
	 * Plugin activation.
	 */
	public static function activate() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'yt_email_subscribers';
		$sql        = "CREATE TABLE IF NOT EXISTS $table_name (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			email varchar(100) NOT NULL,
			ip_address varchar(45) NOT NULL,
			subscribed_at datetime NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY email (email)
		) {$wpdb->get_charset_collate()};";
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
		if ( ! get_option( 'yt_esb_options' ) ) {
			add_option(
				'yt_esb_options',
				array(
					'send_confirmation'    => true,
					'confirmation_subject' => __( 'Welcome!', 'yt-email-subscription-box' ),
					'confirmation_message' => __( 'Thank you for subscribing! Your email: {email}', 'yt-email-subscription-box' ),
				)
			);
		}
	}

	/**
	 * Plugin deactivation.
	 */
	public static function deactivate() {
		// Cleanup if needed.
	}
}

/**
 * Uninstall hook.
 *
 * @return void
 */
function yt_esb_uninstall() {
	global $wpdb;
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}yt_email_subscribers" );
	delete_option( 'yt_esb_options' );
	wp_cache_flush();
}

// Register hooks.
register_activation_hook( __FILE__, array( 'YT_Email_Subscription_Box', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'YT_Email_Subscription_Box', 'deactivate' ) );
register_uninstall_hook( __FILE__, 'yt_esb_uninstall' );

// Initialize plugin.
add_action( 'plugins_loaded', array( 'YT_Email_Subscription_Box', 'get_instance' ) );
