<?php
/**
 * OpenAI Chat Completions engine.
 *
 * @package WordPressAIChatbot
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WAICB_Openai_Chat
 *
 * Sends messages to the OpenAI /v1/chat/completions endpoint,
 * calculates USD cost from token usage, and returns the reply.
 */
class WAICB_Openai_Chat {

	/** OpenAI API base URL. */
	const API_BASE = 'https://api.openai.com/v1';

	/** @var string OpenAI API key. */
	private $api_key;

	/**
	 * Constructor.
	 *
	 * @param string $api_key OpenAI API key.
	 */
	public function __construct( $api_key ) {
		$this->api_key = $api_key;
	}

	/**
	 * Send a message and return the assistant's reply.
	 *
	 * @param string $message User message (already sanitised).
	 * @param array  $history Array of {role, content} history rows from DB.
	 * @return array {reply: string, usage: array, cost: float}
	 * @throws RuntimeException On HTTP or API errors.
	 */
	public function chat( $message, $history ) {
		$model         = get_option( 'waicb_model', 'gpt-4o-mini' );
		$temperature   = (float) get_option( 'waicb_temperature', 0.7 );
		$max_tokens    = (int) get_option( 'waicb_max_tokens', 1024 );
		$system_prompt = WAICB_Crypto::decrypt( get_option( 'waicb_system_prompt', '' ) );

		// Build the messages payload.
		$messages = array();

		if ( '' !== $system_prompt ) {
			$messages[] = array(
				'role'    => 'system',
				'content' => $system_prompt,
			);
		}

		// Append history (exclude system messages already injected).
		foreach ( $history as $row ) {
			if ( 'system' !== $row['role'] ) {
				$messages[] = array(
					'role'    => $row['role'],
					'content' => $row['content'],
				);
			}
		}

		// Append current user message.
		$messages[] = array(
			'role'    => 'user',
			'content' => $message,
		);

		/**
		 * Filter the messages payload before sending to OpenAI.
		 *
		 * @param array $messages   Array of {role, content} entries.
		 * @param int   $session_id 0 — not available at this level; use waicb_messages_payload filter at REST level.
		 */
		$messages = apply_filters( 'waicb_messages_payload', $messages, 0 );

		$body = array(
			'model'       => $model,
			'messages'    => $messages,
			'temperature' => $temperature,
			'max_tokens'  => $max_tokens,
		);

		$response = wp_remote_post(
			self::API_BASE . '/chat/completions',
			array(
				'timeout' => 60,
				'headers' => array(
					'Authorization' => 'Bearer ' . $this->api_key,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( $body ),
			)
		);

		if ( is_wp_error( $response ) ) {
			throw new RuntimeException( esc_html( $response->get_error_message() ) );
		}

		$code = wp_remote_retrieve_response_code( $response );
		$raw  = wp_remote_retrieve_body( $response );
		$data = json_decode( $raw, true );

		if ( 200 !== $code ) {
			$err = isset( $data['error']['message'] ) ? esc_html( $data['error']['message'] ) : 'HTTP ' . (int) $code;
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- already escaped above.
			throw new RuntimeException( $err );
		}

		if ( ! isset( $data['choices'][0]['message']['content'] ) ) {
			throw new RuntimeException( esc_html__( 'Réponse OpenAI inattendue.', 'ai-chat-assistant' ) );
		}

		$reply = $data['choices'][0]['message']['content'];
		$usage = isset( $data['usage'] ) ? $data['usage'] : array();

		return array(
			'reply' => $reply,
			'usage' => $usage,
			'model' => $model,
		);
	}
}