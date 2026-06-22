<?php
/**
 * Anthropic (Claude) Messages API engine.
 *
 * @package WordPressAIChatbot
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WAICB_Anthropic_Chat
 *
 * Sends messages to the Anthropic /v1/messages endpoint,
 * calculates USD cost from token usage, and returns the reply.
 *
 * Uses native wp_remote_* (no Composer SDK), consistent with the
 * plugin's zero-dependency design.
 */
class WAICB_Anthropic_Chat {

	/** Anthropic API base URL. */
	const API_BASE = 'https://api.anthropic.com/v1';

	/** Anthropic API version header value. */
	const API_VERSION = '2023-06-01';

	/** @var string Anthropic API key. */
	private $api_key;

	/**
	 * Constructor.
	 *
	 * @param string $api_key Anthropic API key.
	 */
	public function __construct( $api_key ) {
		$this->api_key = $api_key;
	}

	/**
	 * Send a message and return the assistant's reply.
	 *
	 * @param string $message User message (already sanitised).
	 * @param array  $history Array of {role, content} history rows from DB.
	 * @return array {reply: string, usage: array, cost: float, model: string}
	 * @throws RuntimeException On HTTP or API errors.
	 */
	public function chat( $message, $history ) {
		$model         = get_option( 'waicb_claude_model', 'claude-sonnet-4-6' );
		$max_tokens    = (int) get_option( 'waicb_max_tokens', 1024 );
		$system_prompt = WAICB_Crypto::decrypt( get_option( 'waicb_system_prompt', '' ) );

		// Build the messages payload. Anthropic requires alternating roles
		// starting with "user"; the system prompt is a top-level field, not a message.
		$messages = array();
		foreach ( $history as $row ) {
			if ( 'system' === $row['role'] ) {
				continue;
			}
			$messages[] = array(
				'role'    => 'assistant' === $row['role'] ? 'assistant' : 'user',
				'content' => $row['content'],
			);
		}

		$messages[] = array(
			'role'    => 'user',
			'content' => $message,
		);

		/**
		 * Filter the Claude messages payload before sending to Anthropic.
		 *
		 * @param array $messages   Array of {role, content} entries.
		 * @param int   $session_id 0 — not available at this level; use waicb_messages_payload at REST level.
		 */
		$messages = apply_filters( 'waicb_anthropic_messages_payload', $messages, 0 );

		$body = array(
			'model'      => $model,
			'max_tokens' => $max_tokens > 0 ? $max_tokens : 1024,
			'messages'   => $messages,
		);

		// System prompt is a top-level field on the Messages API.
		if ( '' !== $system_prompt ) {
			$body['system'] = $system_prompt;
		}

		// Note: temperature is intentionally omitted — it is rejected (HTTP 400)
		// by current Opus-tier models. Steer behaviour via the system prompt.

		$response = wp_remote_post(
			self::API_BASE . '/messages',
			array(
				'timeout' => 60,
				'headers' => array(
					'x-api-key'         => $this->api_key,
					'anthropic-version' => self::API_VERSION,
					'Content-Type'      => 'application/json',
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

		// Safety classifiers may decline with a 200 + stop_reason "refusal".
		if ( isset( $data['stop_reason'] ) && 'refusal' === $data['stop_reason'] ) {
			throw new RuntimeException( esc_html__( 'La requête a été refusée par le modèle.', 'ai-chat-assistant' ) );
		}

		$reply = $this->extract_text( $data );

		if ( '' === $reply ) {
			throw new RuntimeException( esc_html__( 'Réponse Claude inattendue.', 'ai-chat-assistant' ) );
		}

		$usage  = isset( $data['usage'] ) ? $data['usage'] : array();
		$input  = isset( $usage['input_tokens'] ) ? (int) $usage['input_tokens'] : 0;
		$output = isset( $usage['output_tokens'] ) ? (int) $usage['output_tokens'] : 0;

		// Normalise usage keys to match the OpenAI shape used by the logger.
		$normalised_usage = array(
			'prompt_tokens'     => $input,
			'completion_tokens' => $output,
			'total_tokens'      => $input + $output,
		);

		return array(
			'reply' => $reply,
			'usage' => $normalised_usage,
			'model' => $model,
		);
	}

	/**
	 * Extract the concatenated text from Anthropic content blocks.
	 *
	 * @param array $data Decoded response body.
	 * @return string Plain-text reply.
	 */
	private function extract_text( $data ) {
		if ( empty( $data['content'] ) || ! is_array( $data['content'] ) ) {
			return '';
		}

		$text = '';
		foreach ( $data['content'] as $block ) {
			if ( isset( $block['type'], $block['text'] ) && 'text' === $block['type'] ) {
				$text .= $block['text'];
			}
		}

		return $text;
	}
}
