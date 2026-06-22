<?php
/**
 * REST API — POST /wp-json/waicb/v1/chat endpoint.
 *
 * @package WordPressAIChatbot
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WAICB_Rest_Api
 *
 * Registers and handles the single REST endpoint used by the chatbot widget.
 */
class WAICB_Rest_Api {

	/** REST namespace. */
	const NAMESPACE = 'waicb/v1';

	/**
	 * Register the REST route.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			self::NAMESPACE,
			'/chat',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'handle_chat' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Handle POST /wp-json/waicb/v1/chat.
	 *
	 * @param WP_REST_Request $request Incoming request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle_chat( WP_REST_Request $request ) {
		// Plugin disabled globally.
		if ( ! get_option( 'waicb_enabled', true ) ) {
			return new WP_Error(
				'disabled',
				__( 'Le chatbot est désactivé.', 'ai-chat-assistant' ),
				array( 'status' => 503 )
			);
		}

		try {
			$body    = $request->get_json_params();
			$nonce   = isset( $body['nonce'] ) ? sanitize_text_field( $body['nonce'] ) : '';
			$message = isset( $body['message'] ) ? $body['message'] : '';
			$sk_body = isset( $body['session_key'] ) ? $body['session_key'] : '';

			// 1. Verify nonce.
			WAICB_Security::verify_nonce( $nonce );

			// 2. Rate limit by IP.
			$ip_hash = WAICB_Security::hash_ip();
			WAICB_Security::check_rate_limit( $ip_hash );

			// 3. Verify session_key ownership: cookie must match body.
			$cookie_key  = WAICB_Session_Manager::get_cookie_key();
			$body_sk     = WAICB_Security::sanitize_session_key( $sk_body );

			if ( '' === $body_sk || $cookie_key !== $body_sk ) {
				return new WP_Error(
					'forbidden',
					__( 'Accès refusé.', 'ai-chat-assistant' ),
					array( 'status' => 403 )
				);
			}

			// 4. Sanitise message.
			$clean_message = WAICB_Security::sanitize_message( $message );

			// 5. Get/create DB session.
			$session_id = WAICB_Session_Manager::get_or_create( $body_sk );

			// 6. Retrieve history.
			$limit   = (int) get_option( 'waicb_history_limit', 20 );
			$history = WAICB_Database::get_messages( $session_id, $limit );

			// Allow external filtering of the payload.
			$history = apply_filters( 'waicb_messages_payload', $history, $session_id );

			// 7. Dispatch to AI engine.
			$result = WAICB_Api_Router::dispatch( $clean_message, $session_id, $history );

			$reply = isset( $result['reply'] ) ? $result['reply'] : '';
			$usage = isset( $result['usage'] ) ? $result['usage'] : array();

			// Filter reply before saving / sending.
			$reply = apply_filters( 'waicb_before_send_response', $reply, $session_id );

			// 8. Save messages.
			WAICB_Database::save_message( $session_id, 'user', $clean_message );
			WAICB_Database::save_message( $session_id, 'assistant', $reply );

			// 9. Insert log. The engine reports the actual model used.
			$model = isset( $result['model'] ) && '' !== $result['model']
				? $result['model']
				: get_option( 'waicb_model', 'gpt-4o-mini' );

			WAICB_Database::insert_log(
				$session_id,
				$model,
				isset( $usage['prompt_tokens'] ) ? (int) $usage['prompt_tokens'] : 0,
				isset( $usage['completion_tokens'] ) ? (int) $usage['completion_tokens'] : 0,
				isset( $usage['total_tokens'] ) ? (int) $usage['total_tokens'] : 0,
				0.0
			);

			// 10. Fire action hook.
			do_action( 'waicb_after_exchange_saved', $session_id, $clean_message, $reply );

			return rest_ensure_response(
				array(
					'success' => true,
					'data'    => array(
						'reply'       => $reply,
						'session_key' => $body_sk,
					),
				)
			);

		} catch ( InvalidArgumentException $e ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'data'    => array( 'message' => $e->getMessage() ),
				)
			);
		} catch ( RuntimeException $e ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'data'    => array( 'message' => $e->getMessage() ),
				)
			);
		}
	}

	/**
	 * AJAX handler — test the OpenAI or Claude API connection from the admin.
	 *
	 * @return void
	 */
	public function handle_test_api() {
		check_ajax_referer( 'waicb_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Accès refusé.', 'ai-chat-assistant' ) ) );
			return;
		}

		$provider = isset( $_POST['provider'] ) && 'claude' === $_POST['provider'] ? 'claude' : 'openai';
		$raw_key  = isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : '';

		if ( 'claude' === $provider ) {
			$model = get_option( 'waicb_claude_model', 'claude-sonnet-4-6' );
			$this->test_connection(
				array(
					'key_option'  => 'waicb_claude_api_key',
					'no_key_msg'  => __( 'Clé API Claude non configurée.', 'ai-chat-assistant' ),
					'success_msg' => __( 'Connexion Claude réussie ✓', 'ai-chat-assistant' ),
					'url'         => 'https://api.anthropic.com/v1/messages',
					'headers'     => array( 'anthropic-version' => '2023-06-01' ),
					'auth_header' => 'x-api-key',
					'auth_prefix' => '',
					'body'        => array(
						'model'      => $model,
						'max_tokens' => 5,
						'messages'   => array( array( 'role' => 'user', 'content' => 'Hi' ) ),
					),
				),
				$raw_key
			);
			return;
		}

		$this->test_connection(
			array(
				'key_option'  => 'waicb_api_key',
				'no_key_msg'  => __( 'Clé API OpenAI non configurée.', 'ai-chat-assistant' ),
				'success_msg' => __( 'Connexion OpenAI réussie ✓', 'ai-chat-assistant' ),
				'url'         => 'https://api.openai.com/v1/chat/completions',
				'headers'     => array(),
				'auth_header' => 'Authorization',
				'auth_prefix' => 'Bearer ',
				'body'        => array(
					'model'      => 'gpt-4o-mini',
					'messages'   => array( array( 'role' => 'user', 'content' => 'Hi' ) ),
					'max_tokens' => 5,
				),
			),
			$raw_key
		);
	}

	/**
	 * Generic connection tester for any provider.
	 *
	 * @param array  $config  {key_option, no_key_msg, success_msg, url, headers, auth_header, auth_prefix, body}.
	 * @param string $raw_key Key submitted from the form (may be empty → use stored).
	 * @return void
	 */
	private function test_connection( $config, $raw_key ) {
		$api_key = '' !== $raw_key ? $raw_key : WAICB_Crypto::decrypt( get_option( $config['key_option'], '' ) );

		if ( '' === $api_key ) {
			wp_send_json_error( array( 'message' => $config['no_key_msg'] ) );
			return;
		}

		$headers = array_merge(
			$config['headers'],
			array(
				'Content-Type'           => 'application/json',
				$config['auth_header'] => $config['auth_prefix'] . $api_key,
			)
		);

		$response = wp_remote_post(
			$config['url'],
			array(
				'timeout' => 15,
				'headers' => $headers,
				'body'    => wp_json_encode( $config['body'] ),
			)
		);

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( array( 'message' => $response->get_error_message() ) );
			return;
		}

		$code = wp_remote_retrieve_response_code( $response );

		if ( 200 === $code ) {
			wp_send_json_success( array( 'message' => $config['success_msg'] ) );
			return;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		$err  = isset( $data['error']['message'] ) ? $data['error']['message'] : 'HTTP ' . $code;
		wp_send_json_error( array( 'message' => $err ) );
	}
}
