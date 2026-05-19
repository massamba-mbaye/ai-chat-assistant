<?php
/**
 * Admin Settings page controller.
 *
 * @package WordPressAIChatbot
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WAICB_Admin_Settings
 *
 * Registers the Settings admin page and handles form submission.
 */
class WAICB_Admin_Settings {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_post_waicb_save_settings', array( $this, 'handle_save' ) );
		add_action( 'admin_post_waicb_clear_conversations', array( $this, 'handle_clear' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Add top-level menu and sub-pages.
	 *
	 * @return void
	 */
	public function add_menu() {
		add_menu_page(
			__( 'WordPress AI Chatbot', 'ai-chat-assistant' ),
			__( 'AI Chatbot', 'ai-chat-assistant' ),
			'manage_options',
			'waicb-settings',
			array( $this, 'render_page' ),
			'dashicons-format-chat',
			80
		);
	}

	/**
	 * Enqueue admin CSS/JS on plugin pages.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( $hook ) {
		if ( strpos( $hook, 'waicb' ) === false ) {
			return;
		}

		wp_enqueue_media(); // Required for the bubble icon media uploader.

		wp_enqueue_style(
			'waicb-admin',
			WAICB_URL . 'admin/assets/admin.css',
			array(),
			WAICB_VERSION
		);

		wp_enqueue_script(
			'waicb-admin',
			WAICB_URL . 'admin/assets/admin.js',
			array( 'jquery' ),
			WAICB_VERSION,
			true
		);

		wp_localize_script(
			'waicb-admin',
			'waicbAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'waicb_admin_nonce' ),
				'i18n'    => array(
					'testing'    => __( 'Test en cours…', 'ai-chat-assistant' ),
					'confirmClear' => __( 'Êtes-vous sûr de vouloir supprimer toutes les conversations ?', 'ai-chat-assistant' ),
				),
			)
		);
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		require WAICB_DIR . 'admin/views/settings-page.php';
	}

	/**
	 * Handle settings form submission.
	 *
	 * @return void
	 */
	public function handle_save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Accès refusé.', 'ai-chat-assistant' ) );
		}

		check_admin_referer( 'waicb_settings_save' );

		// API Key — only update if a new value was submitted.
		$submitted_key = isset( $_POST['waicb_api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['waicb_api_key'] ) ) : '';
		if ( '' !== $submitted_key && strpos( $submitted_key, '****' ) === false ) {
			update_option( 'waicb_api_key', WAICB_Crypto::encrypt( $submitted_key ) );
		}

		// Mode.
		$mode = isset( $_POST['waicb_mode'] ) && 'assistant' === $_POST['waicb_mode'] ? 'assistant' : 'chat';
		update_option( 'waicb_mode', $mode );

		// Model.
		$allowed_models = array( 'gpt-4o', 'gpt-4o-mini', 'gpt-4-turbo', 'gpt-4', 'gpt-3.5-turbo' );
		$model          = isset( $_POST['waicb_model'] ) ? sanitize_text_field( wp_unslash( $_POST['waicb_model'] ) ) : 'gpt-4o-mini';
		if ( ! in_array( $model, $allowed_models, true ) ) {
			$model = 'gpt-4o-mini';
		}
		update_option( 'waicb_model', $model );

		// System prompt — encrypted.
		$system_prompt = isset( $_POST['waicb_system_prompt'] ) ? sanitize_textarea_field( wp_unslash( $_POST['waicb_system_prompt'] ) ) : '';
		update_option( 'waicb_system_prompt', WAICB_Crypto::encrypt( $system_prompt ) );

		// Temperature.
		$temperature = isset( $_POST['waicb_temperature'] ) ? (float) $_POST['waicb_temperature'] : 0.7;
		$temperature = max( 0.0, min( 2.0, $temperature ) );
		update_option( 'waicb_temperature', $temperature );

		// Max tokens.
		$max_tokens = isset( $_POST['waicb_max_tokens'] ) ? (int) $_POST['waicb_max_tokens'] : 1024;
		$max_tokens = max( 1, min( 4096, $max_tokens ) );
		update_option( 'waicb_max_tokens', $max_tokens );

		// History limit.
		$history_limit = isset( $_POST['waicb_history_limit'] ) ? (int) $_POST['waicb_history_limit'] : 20;
		$history_limit = max( 1, min( 100, $history_limit ) );
		update_option( 'waicb_history_limit', $history_limit );

		// Assistant ID.
		$assistant_id = isset( $_POST['waicb_assistant_id'] ) ? sanitize_text_field( wp_unslash( $_POST['waicb_assistant_id'] ) ) : '';
		update_option( 'waicb_assistant_id', $assistant_id );

		// Widget options.
		$position = isset( $_POST['waicb_widget_position'] ) && 'bottom-left' === $_POST['waicb_widget_position'] ? 'bottom-left' : 'bottom-right';
		update_option( 'waicb_widget_position', $position );

		update_option( 'waicb_widget_title', sanitize_text_field( wp_unslash( isset( $_POST['waicb_widget_title'] ) ? $_POST['waicb_widget_title'] : 'Assistant IA' ) ) );
		update_option( 'waicb_welcome_message', sanitize_textarea_field( wp_unslash( isset( $_POST['waicb_welcome_message'] ) ? $_POST['waicb_welcome_message'] : '' ) ) );

		$color = isset( $_POST['waicb_widget_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['waicb_widget_color'] ) ) : '#C49A2E';
		update_option( 'waicb_widget_color', $color ? $color : '#C49A2E' );

		$cookie_days = isset( $_POST['waicb_cookie_days'] ) ? (int) $_POST['waicb_cookie_days'] : 90;
		$cookie_days = max( 1, min( 365, $cookie_days ) );
		update_option( 'waicb_cookie_days', $cookie_days );

		$quick_replies = isset( $_POST['waicb_quick_replies'] ) ? sanitize_textarea_field( wp_unslash( $_POST['waicb_quick_replies'] ) ) : '';
		update_option( 'waicb_quick_replies', $quick_replies );

		$bubble_icon = isset( $_POST['waicb_bubble_icon'] ) ? esc_url_raw( wp_unslash( $_POST['waicb_bubble_icon'] ) ) : '';
		update_option( 'waicb_bubble_icon', $bubble_icon );

		// Display rules.
		$display_mode = isset( $_POST['waicb_display_mode'] ) && in_array( wp_unslash( $_POST['waicb_display_mode'] ), array( 'all', 'specific', 'exclude' ), true )
			? sanitize_text_field( wp_unslash( $_POST['waicb_display_mode'] ) )
			: 'all';
		update_option( 'waicb_display_mode', $display_mode );

		$display_pages = isset( $_POST['waicb_display_pages'] ) && is_array( $_POST['waicb_display_pages'] )
			? array_map( 'absint', $_POST['waicb_display_pages'] )
			: array();
		update_option( 'waicb_display_pages', $display_pages );

		$enabled = isset( $_POST['waicb_enabled'] ) ? 1 : 0;
		update_option( 'waicb_enabled', $enabled );

		wp_safe_redirect( add_query_arg( array( 'page' => 'waicb-settings', 'saved' => '1' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Handle "clear all conversations" form submission.
	 *
	 * @return void
	 */
	public function handle_clear() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Accès refusé.', 'ai-chat-assistant' ) );
		}

		check_admin_referer( 'waicb_clear_conversations' );

		WAICB_Database::delete_all_sessions();

		wp_safe_redirect( add_query_arg( array( 'page' => 'waicb-settings', 'cleared' => '1' ), admin_url( 'admin.php' ) ) );
		exit;
	}
}
