<?php
/**
 * Plugin Name:       AI Chat Assistant
 * Plugin URI:        https://www.im-mass.com/plugins/ai-chat-assistant
 * Description:       Adds an AI chatbot to any site, powered by the Jokko AI Cloud service (prepaid credits — no AI key to manage).
 * Version:           1.6.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Massamba MBAYE
 * Author URI:        https://www.linkedin.com/in/massamba-mbaye/
 * License:           GPL v2 or later
 * Text Domain:       ai-chat-assistant
 * Domain Path:       /languages
 * Update URI:        https://github.com/massamba-mbaye/ai-chat-assistant
 *
 * @package WordPressAIChatbot
 */

defined( 'ABSPATH' ) || exit;

// ── Constantes ────────────────────────────────────────────────────────────────
define( 'WAICB_VERSION', '1.6.0' );
define( 'WAICB_FILE', __FILE__ );
define( 'WAICB_DIR', plugin_dir_path( __FILE__ ) );
define( 'WAICB_URL', plugin_dir_url( __FILE__ ) );
define( 'WAICB_DB_VERSION', '1.0.0' );

// Service Cloud (Jokko AI) — endpoint du proxy IA et tableau de bord client.
define( 'WAICB_CLOUD_URL', 'https://jokko-ai.im-mass.com/api/chat.php' );
define( 'WAICB_CLOUD_STATUS_URL', 'https://jokko-ai.im-mass.com/api/status.php' );
define( 'WAICB_CLOUD_DASHBOARD', 'https://jokko-ai.im-mass.com/dashboard.php' );

// ── Autoloader ────────────────────────────────────────────────────────────────
spl_autoload_register( function ( $class_name ) {
	// Only handle our own classes.
	if ( strpos( $class_name, 'WAICB_' ) !== 0 ) {
		return;
	}

	// Map class name → file path.
	$class_map = array(
		'WAICB_Plugin_Core'            => WAICB_DIR . 'includes/class-plugin-core.php',
		'WAICB_Database'               => WAICB_DIR . 'includes/class-database.php',
		'WAICB_Crypto'                 => WAICB_DIR . 'includes/class-crypto.php',
		'WAICB_Security'               => WAICB_DIR . 'includes/class-security.php',
		'WAICB_Session_Manager'        => WAICB_DIR . 'includes/class-session-manager.php',
		'WAICB_Api_Router'             => WAICB_DIR . 'includes/class-api-router.php',
		'WAICB_Cloud_Chat'             => WAICB_DIR . 'includes/class-cloud-chat.php',
		'WAICB_Updater'                => WAICB_DIR . 'includes/class-updater.php',
		'WAICB_Rest_Api'               => WAICB_DIR . 'includes/class-rest-api.php',
		'WAICB_Admin_Settings'         => WAICB_DIR . 'includes/class-admin-settings.php',
		'WAICB_Admin_Conversations'    => WAICB_DIR . 'includes/class-admin-conversations.php',
		'WAICB_Admin_Logs'             => WAICB_DIR . 'includes/class-admin-logs.php',
		'WAICB_Chatbot_Widget'         => WAICB_DIR . 'public/class-chatbot-widget.php',
	);

	if ( isset( $class_map[ $class_name ] ) ) {
		require_once $class_map[ $class_name ];
	}
} );

// ── Activation ────────────────────────────────────────────────────────────────
register_activation_hook( __FILE__, function () {
	if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die( esc_html__( 'AI Chat Assistant requires PHP 7.4 or higher.', 'ai-chat-assistant' ) );
	}

	if ( version_compare( get_bloginfo( 'version' ), '6.0', '<' ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die( esc_html__( 'AI Chat Assistant requires WordPress 6.0 or higher.', 'ai-chat-assistant' ) );
	}

	WAICB_Database::install();
} );

// ── Désactivation ─────────────────────────────────────────────────────────────
register_deactivation_hook( __FILE__, function () {
	// Nothing to do on deactivation.
} );

// ── Chargement ────────────────────────────────────────────────────────────────
add_action( 'plugins_loaded', function () {
	WAICB_Plugin_Core::get_instance()->init();

	// Self-hosted update checker (GitHub Releases). Only needed where WP checks
	// for updates: the admin and cron.
	if ( is_admin() || ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
		( new WAICB_Updater() )->init();
	}
} );