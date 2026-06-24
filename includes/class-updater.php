<?php
/**
 * Self-hosted update checker — GitHub Releases.
 *
 * Lets sites that installed the plugin from a GitHub .zip receive update
 * notifications in the WordPress admin without the WordPress.org repository.
 *
 * Requires each GitHub Release to either attach a packaged `.zip` asset
 * (recommended — keeps the plugin folder name intact) or rely on the GitHub
 * source zipball (the folder is then normalised by fix_source_dir()).
 *
 * Zero dependencies — native wp_remote_* + the GitHub REST API.
 *
 * @package WordPressAIChatbot
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WAICB_Updater
 */
class WAICB_Updater {

	/** GitHub repository owner. */
	const GITHUB_USER = 'massamba-mbaye';

	/** GitHub repository name. */
	const GITHUB_REPO = 'ai-chat-assistant';

	/** Transient key caching the latest-release payload. */
	const CACHE_KEY = 'waicb_github_release';

	/** Cache TTL for a successful lookup (seconds). */
	const CACHE_TTL = 21600; // 6 hours.

	/** @var string Plugin basename, e.g. ai-chat-assistant/ai-chat-assistant.php */
	private $basename;

	/** @var string Plugin slug (folder name). */
	private $slug;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->basename = plugin_basename( WAICB_FILE );
		$this->slug     = dirname( $this->basename );
		if ( '.' === $this->slug || '' === $this->slug ) {
			$this->slug = self::GITHUB_REPO;
		}
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function init() {
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ) );
		add_filter( 'plugins_api', array( $this, 'plugins_api' ), 10, 3 );
		add_filter( 'upgrader_source_selection', array( $this, 'fix_source_dir' ), 10, 4 );
	}

	/**
	 * Inject update info into the plugins update transient.
	 *
	 * @param mixed $transient Update transient object.
	 * @return mixed
	 */
	public function check_update( $transient ) {
		if ( ! is_object( $transient ) || empty( $transient->checked ) ) {
			return $transient;
		}

		$release = $this->get_remote_release();
		if ( empty( $release['tag_name'] ) ) {
			return $transient;
		}

		$remote_version  = ltrim( $release['tag_name'], 'vV' );
		$current_version = isset( $transient->checked[ $this->basename ] )
			? $transient->checked[ $this->basename ]
			: WAICB_VERSION;

		$info = (object) array(
			'id'          => self::GITHUB_USER . '/' . self::GITHUB_REPO,
			'slug'        => $this->slug,
			'plugin'      => $this->basename,
			'new_version' => $remote_version,
			'url'         => isset( $release['html_url'] ) ? $release['html_url'] : '',
			'package'     => '',
		);

		if ( version_compare( $remote_version, $current_version, '>' ) ) {
			$package = $this->get_package_url( $release );
			if ( '' !== $package ) {
				$info->package = $package;
				$transient->response[ $this->basename ] = $info;
			}
		} else {
			// Ensure WP records that no update is needed for this plugin.
			unset( $transient->response[ $this->basename ] );
			$info->new_version = $current_version;
			$transient->no_update[ $this->basename ] = $info;
		}

		return $transient;
	}

	/**
	 * Provide the "View details" popup data.
	 *
	 * @param false|object|array $result The result object/array.
	 * @param string             $action The API action being performed.
	 * @param object             $args   Request arguments.
	 * @return false|object|array
	 */
	public function plugins_api( $result, $action, $args ) {
		if ( 'plugin_information' !== $action ) {
			return $result;
		}
		if ( empty( $args->slug ) || $args->slug !== $this->slug ) {
			return $result;
		}

		$release = $this->get_remote_release();
		if ( empty( $release['tag_name'] ) ) {
			return $result;
		}

		$readme   = $this->read_local_readme();
		$sections = array();

		foreach ( array(
			'description'  => 'Description',
			'installation' => 'Installation',
			'faq'          => 'Frequently Asked Questions',
		) as $key => $title ) {
			$html = $this->readme_section( $readme, $title );
			if ( '' !== $html ) {
				$sections[ $key ] = $html;
			}
		}

		$changelog = $this->readme_section( $readme, 'Changelog' );
		if ( '' === $changelog ) {
			$changelog = wp_kses_post( wpautop( isset( $release['body'] ) ? $release['body'] : '' ) );
		}
		$sections['changelog'] = $changelog;

		return (object) array(
			'name'           => 'AI Chat Assistant',
			'slug'           => $this->slug,
			'version'        => ltrim( $release['tag_name'], 'vV' ),
			'author'         => '<a href="https://www.im-mass.com">Massamba MBAYE</a>',
			'author_profile' => 'https://www.im-mass.com',
			'homepage'       => 'https://www.im-mass.com/plugins/ai-chat-assistant',
			'requires'       => '6.0',
			'tested'         => '6.9',
			'requires_php'   => '7.4',
			'last_updated'   => isset( $release['published_at'] ) ? $release['published_at'] : '',
			'download_link'  => $this->get_package_url( $release ),
			'sections'       => $sections,
		);
	}

	/**
	 * Read the plugin's bundled readme.txt.
	 *
	 * @return string
	 */
	private function read_local_readme() {
		$path = WAICB_DIR . 'readme.txt';
		if ( ! is_readable( $path ) ) {
			return '';
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$content = file_get_contents( $path );
		return is_string( $content ) ? $content : '';
	}

	/**
	 * Extract a "== Section ==" block from the readme and render it to HTML.
	 *
	 * @param string $readme  Full readme contents.
	 * @param string $section Section title (without the == markers).
	 * @return string HTML, or empty string if not found.
	 */
	private function readme_section( $readme, $section ) {
		if ( '' === $readme ) {
			return '';
		}
		$pattern = '/==\s*' . preg_quote( $section, '/' ) . '\s*==\s*(.*?)(?=\n==\s|\z)/s';
		if ( ! preg_match( $pattern, $readme, $m ) ) {
			return '';
		}
		return $this->format_readme( $m[1] );
	}

	/**
	 * Convert the limited WordPress-readme markup to safe HTML.
	 *
	 * Handles "= Heading =" -> <h4>, "* item" -> <ul>, "1. item" -> <ol>,
	 * blank-line paragraphs, and inline `code` / **bold**.
	 *
	 * @param string $text Raw section text.
	 * @return string HTML.
	 */
	private function format_readme( $text ) {
		$lines     = preg_split( '/\r\n|\r|\n/', trim( $text ) );
		$html      = '';
		$list_type = '';
		$para      = '';

		$flush_para = function () use ( &$para, &$html ) {
			if ( '' !== trim( $para ) ) {
				$html .= '<p>' . wp_kses_post( $this->inline_md( trim( $para ) ) ) . '</p>';
			}
			$para = '';
		};
		$close_list = function () use ( &$list_type, &$html ) {
			if ( '' !== $list_type ) {
				$html     .= '</' . $list_type . '>';
				$list_type = '';
			}
		};

		foreach ( $lines as $line ) {
			$t = trim( $line );

			if ( preg_match( '/^=\s*(.+?)\s*=$/', $t, $m ) ) {
				$close_list();
				$flush_para();
				$html .= '<h4>' . esc_html( $m[1] ) . '</h4>';
			} elseif ( preg_match( '/^\*\s+(.+)$/', $t, $m ) ) {
				$flush_para();
				if ( 'ul' !== $list_type ) {
					$close_list();
					$html     .= '<ul>';
					$list_type = 'ul';
				}
				$html .= '<li>' . wp_kses_post( $this->inline_md( $m[1] ) ) . '</li>';
			} elseif ( preg_match( '/^\d+\.\s+(.+)$/', $t, $m ) ) {
				$flush_para();
				if ( 'ol' !== $list_type ) {
					$close_list();
					$html     .= '<ol>';
					$list_type = 'ol';
				}
				$html .= '<li>' . wp_kses_post( $this->inline_md( $m[1] ) ) . '</li>';
			} elseif ( '' === $t ) {
				$close_list();
				$flush_para();
			} else {
				$para .= ( '' !== $para ? ' ' : '' ) . $t;
			}
		}

		$close_list();
		$flush_para();

		return $html;
	}

	/**
	 * Minimal inline markup: `code` and **bold**.
	 *
	 * @param string $s Text.
	 * @return string
	 */
	private function inline_md( $s ) {
		$s = preg_replace( '/`([^`]+)`/', '<code>$1</code>', $s );
		$s = preg_replace( '/\*\*(.+?)\*\*/', '<strong>$1</strong>', $s );
		return $s;
	}

	/**
	 * Normalise the extracted source directory to the plugin slug.
	 *
	 * GitHub source zipballs extract to "user-repo-<sha>"; without this WP would
	 * install the update into the wrong folder. Release .zip assets that already
	 * contain the correct folder are left untouched.
	 *
	 * @param string $source        Path to the extracted source.
	 * @param string $remote_source Path to the downloaded archive's parent dir.
	 * @param object $upgrader      WP_Upgrader instance.
	 * @param array  $hook_extra    Extra args (includes 'plugin').
	 * @return string|WP_Error
	 */
	public function fix_source_dir( $source, $remote_source, $upgrader, $hook_extra = array() ) {
		global $wp_filesystem;

		if ( empty( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $this->basename ) {
			return $source;
		}

		$desired = trailingslashit( $remote_source ) . $this->slug . '/';

		if ( untrailingslashit( $source ) === untrailingslashit( $desired ) ) {
			return $source;
		}

		if ( $wp_filesystem && $wp_filesystem->move( $source, $desired ) ) {
			return $desired;
		}

		return $source;
	}

	/** @var array|null Per-request cache so repeated calls share one lookup. */
	private static $runtime_cache = null;

	/**
	 * Fetch (and cache) the latest GitHub release payload.
	 *
	 * Cached both for the lifetime of the request (static) and across requests
	 * (transient), so check_update() + plugins_api() don't hit GitHub twice.
	 *
	 * @return array Decoded release array, or empty array on failure.
	 */
	private function get_remote_release() {
		if ( null !== self::$runtime_cache ) {
			return self::$runtime_cache;
		}

		$cached = get_transient( self::CACHE_KEY );
		if ( false !== $cached ) {
			self::$runtime_cache = is_array( $cached ) ? $cached : array();
			return self::$runtime_cache;
		}

		$url = 'https://api.github.com/repos/' . self::GITHUB_USER . '/' . self::GITHUB_REPO . '/releases/latest';

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 15,
				'headers' => array(
					'Accept'     => 'application/vnd.github+json',
					'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url(),
				),
			)
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			// Cache the failure briefly to avoid hammering the API.
			set_transient( self::CACHE_KEY, array(), HOUR_IN_SECONDS );
			self::$runtime_cache = array();
			return self::$runtime_cache;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		$data = is_array( $data ) ? $data : array();

		set_transient( self::CACHE_KEY, $data, self::CACHE_TTL );
		self::$runtime_cache = $data;

		return self::$runtime_cache;
	}

	/**
	 * Resolve the downloadable package URL from a release.
	 *
	 * Prefers an attached .zip asset; falls back to the GitHub source zipball.
	 *
	 * @param array $release Release payload.
	 * @return string Package URL, or empty string.
	 */
	private function get_package_url( $release ) {
		if ( ! empty( $release['assets'] ) && is_array( $release['assets'] ) ) {
			foreach ( $release['assets'] as $asset ) {
				if ( ! empty( $asset['browser_download_url'] )
					&& isset( $asset['name'] )
					&& '.zip' === strtolower( substr( $asset['name'], -4 ) )
				) {
					return $asset['browser_download_url'];
				}
			}
		}

		return isset( $release['zipball_url'] ) ? $release['zipball_url'] : '';
	}
}
