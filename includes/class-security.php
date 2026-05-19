<?php
/**
 * Security — nonce, rate limiting, sanitisation.
 *
 * IMPORTANT: This class MUST NOT call wp_send_json_error() or exit.
 * It raises exceptions only; callers handle the HTTP response.
 *
 * @package WordPressAIChatbot
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WAICB_Security
 *
 * Centralises all security-related helpers: nonce verification,
 * IP-based rate limiting, input sanitisation, UUID validation,
 * UUID generation, and IP hashing.
 */
class WAICB_Security {

	/** Maximum requests allowed per window. */
	const RATE_LIMIT_MAX = 20;

	/** Rate-limit window in seconds. */
	const RATE_LIMIT_WINDOW = 60;

	/** Maximum message length in characters. */
	const MAX_MESSAGE_LENGTH = 4000;

	/**
	 * Verify a WordPress nonce.
	 *
	 * @param string $nonce Nonce value to verify.
	 * @return void
	 * @throws InvalidArgumentException If the nonce is invalid or absent.
	 */
	public static function verify_nonce( $nonce ) {
		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'waicb_chat_nonce' ) ) {
			throw new InvalidArgumentException( esc_html__( 'Nonce invalide.', 'ai-chat-assistant' ) );
		}
	}

	/**
	 * Check and increment the rate limit for the given IP hash.
	 *
	 * Uses WP transients (one per IP hash per window).
	 *
	 * @param string $ip_hash SHA-256 hash of the visitor's IP.
	 * @return void
	 * @throws RuntimeException If the rate limit is exceeded.
	 */
	public static function check_rate_limit( $ip_hash ) {
		if ( empty( $ip_hash ) ) {
			return;
		}

		$transient_key = 'waicb_rl_' . $ip_hash;
		$count         = (int) get_transient( $transient_key );

		if ( $count >= self::RATE_LIMIT_MAX ) {
			throw new RuntimeException( esc_html__( 'Trop de requêtes. Veuillez patienter.', 'ai-chat-assistant' ) );
		}

		if ( 0 === $count ) {
			set_transient( $transient_key, 1, self::RATE_LIMIT_WINDOW );
		} else {
			set_transient( $transient_key, $count + 1, self::RATE_LIMIT_WINDOW );
		}
	}

	/**
	 * Sanitise a user-submitted chat message.
	 *
	 * @param string $raw Raw input from the request body.
	 * @return string Sanitised message.
	 * @throws InvalidArgumentException If the message is empty or too long.
	 */
	public static function sanitize_message( $raw ) {
		$sanitized = sanitize_textarea_field( $raw );

		if ( '' === $sanitized ) {
			throw new InvalidArgumentException( esc_html__( 'Le message ne peut pas être vide.', 'ai-chat-assistant' ) );
		}

		if ( mb_strlen( $sanitized ) > self::MAX_MESSAGE_LENGTH ) {
			throw new InvalidArgumentException(
				esc_html(
					sprintf(
						/* translators: %d: maximum number of characters allowed */
						__( 'Le message dépasse la limite de %d caractères.', 'ai-chat-assistant' ),
						self::MAX_MESSAGE_LENGTH
					)
				)
			);
		}

		return $sanitized; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- data value, not HTML output.
	}

	/**
	 * Sanitise and validate a session_key (must be UUID v4 format).
	 *
	 * @param string $raw Raw session_key input.
	 * @return string Validated UUID string, or empty string if invalid.
	 */
	public static function sanitize_session_key( $raw ) {
		$raw = sanitize_text_field( $raw );

		if ( ! preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $raw ) ) {
			return '';
		}

		return strtolower( $raw );
	}

	/**
	 * Generate a cryptographically secure UUID v4.
	 *
	 * @return string UUID v4 string.
	 */
	public static function generate_uuid() {
		$data    = random_bytes( 16 );
		$data[6] = chr( ( ord( $data[6] ) & 0x0f ) | 0x40 ); // version 4.
		$data[8] = chr( ( ord( $data[8] ) & 0x3f ) | 0x80 ); // variant bits.

		return vsprintf(
			'%s%s-%s-%s-%s-%s%s%s',
			str_split( bin2hex( $data ), 4 )
		);
	}

	/**
	 * Compute SHA-256 hash of the visitor's IP combined with NONCE_SALT.
	 *
	 * Never stores the raw IP address.
	 *
	 * @return string Hex-encoded SHA-256 hash.
	 */
	public static function hash_ip() {
		$ip   = self::get_client_ip();
		$salt = defined( 'NONCE_SALT' ) ? NONCE_SALT : wp_salt( 'nonce' );
		return hash( 'sha256', $ip . $salt );
	}

	/**
	 * Retrieve the visitor's IP address.
	 *
	 * @return string IP address (may be behind a proxy).
	 */
	private static function get_client_ip() {
		$keys = array(
			'HTTP_CF_CONNECTING_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_REAL_IP',
			'REMOTE_ADDR',
		);

		foreach ( $keys as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
				// Take the first IP in a comma-separated list.
				if ( strpos( $ip, ',' ) !== false ) {
					$ip = trim( explode( ',', $ip )[0] );
				}
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}

		return '0.0.0.0';
	}
}