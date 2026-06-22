<?php
/**
 * OpenAI Assistants API engine.
 *
 * @package WordPressAIChatbot
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WAICB_Openai_Assistant
 *
 * Implements the full Assistants API workflow:
 * get/create thread → add message → create run → poll → fetch reply.
 */
class WAICB_Openai_Assistant {

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
	 * Run the full Assistants API workflow for a given message.
	 *
	 * @param string $message    User message.
	 * @param int    $session_id DB session ID (used as thread key).
	 * @return array {reply: string, usage: array, cost: float}
	 * @throws RuntimeException On API or polling failures.
	 */
	public function chat( $message, $session_id ) {
		$assistant_id = get_option( 'waicb_assistant_id', '' );

		if ( '' === $assistant_id ) {
			throw new RuntimeException( esc_html__( 'Assistant ID non configuré.', 'ai-chat-assistant' ) );
		}

		$thread_id = $this->get_or_create_thread( $session_id );

		$this->add_message( $thread_id, $message );

		$run_id = $this->create_run( $thread_id, $assistant_id );

		$run = $this->poll_run( $thread_id, $run_id );

		if ( 'completed' !== $run['status'] ) {
			throw new RuntimeException(
				esc_html(
					sprintf(
						/* translators: %s: run status returned by OpenAI */
						__( 'Assistant run failed: %s', 'ai-chat-assistant' ),
						$run['status']
					)
				)
			);
		}

		$reply = $this->fetch_last_assistant_message( $thread_id );

		// Token usage from run object (may not always be present).
		$usage = isset( $run['usage'] ) ? $run['usage'] : array();

		return array(
			'reply' => $reply,
			'usage' => $usage,
			'model' => 'assistant',
		);
	}

	/**
	 * Get or create an OpenAI thread for a given session.
	 *
	 * Thread IDs are stored as WP options keyed by session ID.
	 *
	 * @param int $session_id DB session ID.
	 * @return string OpenAI thread ID.
	 * @throws RuntimeException On API error.
	 */
	private function get_or_create_thread( $session_id ) {
		$option_name = 'waicb_thread_' . (int) $session_id;
		$thread_id   = get_option( $option_name, '' );

		if ( '' !== $thread_id ) {
			return $thread_id;
		}

		$response = $this->api_request( 'POST', '/threads', array() );

		if ( ! isset( $response['id'] ) ) {
			throw new RuntimeException( esc_html__( 'Impossible de créer un thread OpenAI.', 'ai-chat-assistant' ) );
		}

		$thread_id = $response['id'];
		update_option( $option_name, $thread_id, false );

		return $thread_id;
	}

	/**
	 * Add a user message to an existing thread.
	 *
	 * @param string $thread_id OpenAI thread ID.
	 * @param string $message   User message content.
	 * @return void
	 * @throws RuntimeException On API error.
	 */
	private function add_message( $thread_id, $message ) {
		$this->api_request(
			'POST',
			'/threads/' . $thread_id . '/messages',
			array(
				'role'    => 'user',
				'content' => $message,
			)
		);
	}

	/**
	 * Create a run for a thread.
	 *
	 * @param string $thread_id    OpenAI thread ID.
	 * @param string $assistant_id OpenAI assistant ID.
	 * @return string Run ID.
	 * @throws RuntimeException On API error.
	 */
	private function create_run( $thread_id, $assistant_id ) {
		$response = $this->api_request(
			'POST',
			'/threads/' . $thread_id . '/runs',
			array( 'assistant_id' => $assistant_id )
		);

		if ( ! isset( $response['id'] ) ) {
			throw new RuntimeException( esc_html__( 'Impossible de créer un run OpenAI.', 'ai-chat-assistant' ) );
		}

		return $response['id'];
	}

	/**
	 * Poll a run until it reaches a terminal state.
	 *
	 * @param string $thread_id OpenAI thread ID.
	 * @param string $run_id    OpenAI run ID.
	 * @return array Run object array.
	 * @throws RuntimeException On API error or timeout.
	 */
	private function poll_run( $thread_id, $run_id ) {
		$terminal     = array( 'completed', 'failed', 'cancelled', 'expired' );
		$max_attempts = 30;
		$attempt      = 0;

		do {
			sleep( 1 );
			$run = $this->get_run( $thread_id, $run_id );
			$attempt++;
		} while (
			! in_array( $run['status'], $terminal, true )
			&& $attempt < $max_attempts
		);

		if ( ! in_array( $run['status'], $terminal, true ) ) {
			throw new RuntimeException( esc_html__( "Délai d'attente de l'assistant dépassé.", 'ai-chat-assistant' ) );
		}

		return $run;
	}

	/**
	 * Retrieve the current state of a run.
	 *
	 * @param string $thread_id OpenAI thread ID.
	 * @param string $run_id    OpenAI run ID.
	 * @return array Run object.
	 * @throws RuntimeException On API error.
	 */
	private function get_run( $thread_id, $run_id ) {
		return $this->api_request(
			'GET',
			'/threads/' . $thread_id . '/runs/' . $run_id,
			null
		);
	}

	/**
	 * Fetch the latest assistant message from a thread.
	 *
	 * @param string $thread_id OpenAI thread ID.
	 * @return string Plain-text reply.
	 * @throws RuntimeException On API error or missing content.
	 */
	private function fetch_last_assistant_message( $thread_id ) {
		$response = $this->api_request(
			'GET',
			'/threads/' . $thread_id . '/messages?limit=1&order=desc',
			null
		);

		if ( ! isset( $response['data'][0]['content'][0]['text']['value'] ) ) {
			throw new RuntimeException( esc_html__( "Réponse de l'assistant introuvable.", 'ai-chat-assistant' ) );
		}

		return $response['data'][0]['content'][0]['text']['value'];
	}

	/**
	 * Generic OpenAI API request via wp_remote_*.
	 *
	 * @param string     $method HTTP method ('GET' or 'POST').
	 * @param string     $path   API path (e.g. '/threads').
	 * @param array|null $body   Request body (null for GET).
	 * @return array Decoded JSON response.
	 * @throws RuntimeException On WP_Error or non-2xx response.
	 */
	private function api_request( $method, $path, $body ) {
		$url = self::API_BASE . $path;

		$args = array(
			'timeout' => 60,
			'method'  => $method,
			'headers' => array(
				'Authorization' => 'Bearer ' . $this->api_key,
				'Content-Type'  => 'application/json',
				'OpenAI-Beta'   => 'assistants=v2',
			),
		);

		if ( null !== $body ) {
			$args['body'] = wp_json_encode( $body );
		}

		$response = 'GET' === $method
			? wp_remote_get( $url, $args )
			: wp_remote_post( $url, $args );

		if ( is_wp_error( $response ) ) {
			throw new RuntimeException( esc_html( $response->get_error_message() ) );
		}

		$code = wp_remote_retrieve_response_code( $response );
		$raw  = wp_remote_retrieve_body( $response );
		$data = json_decode( $raw, true );

		if ( $code < 200 || $code >= 300 ) {
			$err = isset( $data['error']['message'] ) ? esc_html( $data['error']['message'] ) : 'HTTP ' . (int) $code;
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- already escaped above.
			throw new RuntimeException( $err );
		}

		return is_array( $data ) ? $data : array();
	}
}
