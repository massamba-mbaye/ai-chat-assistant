<?php
/**
 * API Router — dispatches to Chat or Assistant engine.
 *
 * @package WordPressAIChatbot
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WAICB_Api_Router
 *
 * Routes a chat request to either WAICB_Openai_Chat or
 * WAICB_Openai_Assistant based on the configured mode.
 */
class WAICB_Api_Router {

	/**
	 * Dispatch a chat request to the appropriate provider/engine.
	 *
	 * Routing is driven by the `waicb_provider` option (openai | claude).
	 * For OpenAI, the `waicb_mode` option further selects Chat vs Assistants.
	 *
	 * @param string $message    Sanitised user message.
	 * @param int    $session_id DB session ID (used by Assistants API for thread lookup).
	 * @param array  $history    Array of {role, content} history entries.
	 * @return array {reply: string, usage: array, cost: float, model: string}
	 * @throws RuntimeException If the API call fails.
	 */
	public static function dispatch( $message, $session_id, $history ) {
		$provider = get_option( 'waicb_provider', 'openai' );

		if ( 'cloud' === $provider ) {
			$endpoint    = get_option( 'waicb_cloud_url', '' );
			$account_key = WAICB_Crypto::decrypt( get_option( 'waicb_cloud_key', '' ) );

			if ( '' === $endpoint || '' === $account_key ) {
				throw new RuntimeException( esc_html__( 'Fournisseur Cloud non configuré (URL ou clé de compte manquante).', 'ai-chat-assistant' ) );
			}

			$engine = new WAICB_Cloud_Chat( $endpoint, $account_key );
			return $engine->chat( $message, $history );
		}

		if ( 'claude' === $provider ) {
			$api_key = WAICB_Crypto::decrypt( get_option( 'waicb_claude_api_key', '' ) );

			if ( '' === $api_key ) {
				throw new RuntimeException( esc_html__( 'Clé API Claude non configurée.', 'ai-chat-assistant' ) );
			}

			$engine = new WAICB_Anthropic_Chat( $api_key );
			return $engine->chat( $message, $history );
		}

		// Default provider: OpenAI.
		$mode    = get_option( 'waicb_mode', 'chat' );
		$api_key = WAICB_Crypto::decrypt( get_option( 'waicb_api_key', '' ) );

		if ( '' === $api_key ) {
			throw new RuntimeException( esc_html__( 'Clé API OpenAI non configurée.', 'ai-chat-assistant' ) );
		}

		if ( 'assistant' === $mode ) {
			$engine = new WAICB_Openai_Assistant( $api_key );
			return $engine->chat( $message, $session_id );
		}

		$engine = new WAICB_Openai_Chat( $api_key );
		return $engine->chat( $message, $history );
	}
}
