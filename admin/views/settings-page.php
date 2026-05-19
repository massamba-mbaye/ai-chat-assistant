<?php
/**
 * Admin view — Settings page.
 *
 * @package WordPressAIChatbot
 */

defined( 'ABSPATH' ) || exit;

$waicb_api_key_stored  = get_option( 'waicb_api_key', '' );
$waicb_has_api_key     = '' !== $waicb_api_key_stored;
$waicb_mode            = get_option( 'waicb_mode', 'chat' );
$waicb_model           = get_option( 'waicb_model', 'gpt-4o-mini' );
$waicb_system_prompt   = WAICB_Crypto::decrypt( get_option( 'waicb_system_prompt', '' ) );
$waicb_temperature     = (float) get_option( 'waicb_temperature', 0.7 );
$waicb_max_tokens      = (int) get_option( 'waicb_max_tokens', 1024 );
$waicb_history_limit   = (int) get_option( 'waicb_history_limit', 20 );
$waicb_assistant_id    = get_option( 'waicb_assistant_id', '' );
$waicb_widget_position = get_option( 'waicb_widget_position', 'bottom-right' );
$waicb_widget_title    = get_option( 'waicb_widget_title', 'Assistant IA' );
$waicb_widget_color    = get_option( 'waicb_widget_color', '#C49A2E' );
$waicb_welcome_message = get_option( 'waicb_welcome_message', __( 'Bonjour ! Comment puis-je vous aider ?', 'ai-chat-assistant' ) );
$waicb_cookie_days     = (int) get_option( 'waicb_cookie_days', 90 );
$waicb_enabled         = (bool) get_option( 'waicb_enabled', true );

$waicb_saved   = isset( $_GET['saved'] ) && '1' === sanitize_key( $_GET['saved'] );     // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$waicb_cleared = isset( $_GET['cleared'] ) && '1' === sanitize_key( $_GET['cleared'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
?>
<div class="wrap waicb-settings-wrap">
	<h1><?php esc_html_e( 'WordPress AI Chatbot — Réglages', 'ai-chat-assistant' ); ?></h1>

	<?php if ( $waicb_saved ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Réglages enregistrés.', 'ai-chat-assistant' ); ?></p></div>
	<?php endif; ?>

	<?php if ( $waicb_cleared ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Toutes les conversations ont été supprimées.', 'ai-chat-assistant' ); ?></p></div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="waicb_save_settings">
		<?php wp_nonce_field( 'waicb_settings_save' ); ?>

		<!-- ── Connexion OpenAI ──────────────────────────────────────── -->
		<h2><?php esc_html_e( 'Connexion OpenAI', 'ai-chat-assistant' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="waicb_api_key"><?php esc_html_e( 'Clé API OpenAI', 'ai-chat-assistant' ); ?></label>
				</th>
				<td>
					<input type="password"
					       id="waicb_api_key"
					       name="waicb_api_key"
					       class="regular-text"
					       value="<?php echo $waicb_has_api_key ? '****************' : ''; ?>"
					       autocomplete="new-password"
					       placeholder="sk-...">
					<p class="description"><?php esc_html_e( 'Laissez vide pour conserver la valeur actuelle.', 'ai-chat-assistant' ); ?></p>
					<button type="button" id="waicb-test-api" class="button button-secondary" style="margin-top:6px;">
						<?php esc_html_e( 'Tester la connexion API', 'ai-chat-assistant' ); ?>
					</button>
					<span id="waicb-test-result" style="margin-left:10px;font-weight:600;"></span>
				</td>
			</tr>
		</table>

		<!-- ── Mode ──────────────────────────────────────────────────── -->
		<h2><?php esc_html_e( 'Mode', 'ai-chat-assistant' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Moteur OpenAI', 'ai-chat-assistant' ); ?></th>
				<td>
					<label>
						<input type="radio" name="waicb_mode" value="chat" <?php checked( $waicb_mode, 'chat' ); ?>>
						<?php esc_html_e( 'Chat Completion', 'ai-chat-assistant' ); ?>
					</label>
					&nbsp;&nbsp;
					<label>
						<input type="radio" name="waicb_mode" value="assistant" <?php checked( $waicb_mode, 'assistant' ); ?>>
						<?php esc_html_e( 'Assistants API', 'ai-chat-assistant' ); ?>
					</label>
				</td>
			</tr>
		</table>

		<!-- ── Chat Completion ───────────────────────────────────────── -->
		<h2><?php esc_html_e( 'Chat Completion', 'ai-chat-assistant' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="waicb_model"><?php esc_html_e( 'Modèle', 'ai-chat-assistant' ); ?></label>
				</th>
				<td>
					<select id="waicb_model" name="waicb_model">
						<?php
						$waicb_models = array( 'gpt-4o', 'gpt-4o-mini', 'gpt-4-turbo', 'gpt-4', 'gpt-3.5-turbo' );
						foreach ( $waicb_models as $waicb_m ) :
							?>
							<option value="<?php echo esc_attr( $waicb_m ); ?>" <?php selected( $waicb_model, $waicb_m ); ?>><?php echo esc_html( $waicb_m ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="waicb_system_prompt"><?php esc_html_e( 'System Prompt', 'ai-chat-assistant' ); ?></label>
				</th>
				<td>
					<textarea id="waicb_system_prompt"
					          name="waicb_system_prompt"
					          rows="5"
					          class="large-text"><?php echo esc_textarea( $waicb_system_prompt ); ?></textarea>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="waicb_temperature"><?php esc_html_e( 'Température', 'ai-chat-assistant' ); ?></label>
				</th>
				<td>
					<input type="range"
					       id="waicb_temperature"
					       name="waicb_temperature"
					       min="0" max="2" step="0.1"
					       value="<?php echo esc_attr( $waicb_temperature ); ?>"
					       oninput="document.getElementById('waicb_temperature_val').textContent=this.value">
					<span id="waicb_temperature_val"><?php echo esc_html( $waicb_temperature ); ?></span>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="waicb_max_tokens"><?php esc_html_e( 'Tokens max', 'ai-chat-assistant' ); ?></label>
				</th>
				<td>
					<input type="number" id="waicb_max_tokens" name="waicb_max_tokens"
					       value="<?php echo esc_attr( $waicb_max_tokens ); ?>" min="1" max="4096" class="small-text">
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="waicb_history_limit"><?php esc_html_e( 'Limite historique', 'ai-chat-assistant' ); ?></label>
				</th>
				<td>
					<input type="number" id="waicb_history_limit" name="waicb_history_limit"
					       value="<?php echo esc_attr( $waicb_history_limit ); ?>" min="1" max="100" class="small-text">
					<p class="description"><?php esc_html_e( 'Nombre de messages injectés dans le contexte.', 'ai-chat-assistant' ); ?></p>
				</td>
			</tr>
		</table>

		<!-- ── Assistants API ────────────────────────────────────────── -->
		<h2><?php esc_html_e( 'Assistants API', 'ai-chat-assistant' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="waicb_assistant_id"><?php esc_html_e( 'Assistant ID', 'ai-chat-assistant' ); ?></label>
				</th>
				<td>
					<input type="text" id="waicb_assistant_id" name="waicb_assistant_id"
					       value="<?php echo esc_attr( $waicb_assistant_id ); ?>" class="regular-text"
					       placeholder="asst_...">
				</td>
			</tr>
		</table>

		<!-- ── Widget ────────────────────────────────────────────────── -->
		<h2><?php esc_html_e( 'Widget', 'ai-chat-assistant' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Icône de la bulle', 'ai-chat-assistant' ); ?></th>
				<td>
					<?php $waicb_bubble_icon = get_option( 'waicb_bubble_icon', '' ); ?>
					<input type="hidden" id="waicb_bubble_icon" name="waicb_bubble_icon"
					       value="<?php echo esc_attr( $waicb_bubble_icon ); ?>">

					<div id="waicb-icon-preview" style="margin-bottom:8px;<?php echo $waicb_bubble_icon ? '' : 'display:none;'; ?>">
						<img src="<?php echo esc_url( $waicb_bubble_icon ); ?>"
						     alt="" style="width:56px;height:56px;object-fit:cover;border-radius:50%;border:2px solid #ddd;">
					</div>

					<button type="button" id="waicb-upload-icon" class="button button-secondary">
						<?php esc_html_e( 'Choisir une image', 'ai-chat-assistant' ); ?>
					</button>
					<button type="button" id="waicb-remove-icon" class="button"
					        style="margin-left:6px;<?php echo $waicb_bubble_icon ? '' : 'display:none;'; ?>">
						<?php esc_html_e( 'Supprimer', 'ai-chat-assistant' ); ?>
					</button>
					<p class="description"><?php esc_html_e( 'Remplace l\'icône par défaut dans la bulle flottante. Taille recommandée : 56×56 px.', 'ai-chat-assistant' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="waicb_widget_title"><?php esc_html_e( 'Titre du widget', 'ai-chat-assistant' ); ?></label>
				</th>
				<td>
					<input type="text" id="waicb_widget_title" name="waicb_widget_title"
					       value="<?php echo esc_attr( $waicb_widget_title ); ?>" class="regular-text">
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="waicb_welcome_message"><?php esc_html_e( 'Message de bienvenue', 'ai-chat-assistant' ); ?></label>
				</th>
				<td>
					<textarea id="waicb_welcome_message" name="waicb_welcome_message"
					          rows="3" class="large-text"><?php echo esc_textarea( $waicb_welcome_message ); ?></textarea>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="waicb_widget_position"><?php esc_html_e( 'Position', 'ai-chat-assistant' ); ?></label>
				</th>
				<td>
					<select id="waicb_widget_position" name="waicb_widget_position">
						<option value="bottom-right" <?php selected( $waicb_widget_position, 'bottom-right' ); ?>><?php esc_html_e( 'Bas droite', 'ai-chat-assistant' ); ?></option>
						<option value="bottom-left" <?php selected( $waicb_widget_position, 'bottom-left' ); ?>><?php esc_html_e( 'Bas gauche', 'ai-chat-assistant' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="waicb_widget_color"><?php esc_html_e( 'Couleur principale', 'ai-chat-assistant' ); ?></label>
				</th>
				<td>
					<input type="color" id="waicb_widget_color" name="waicb_widget_color"
					       value="<?php echo esc_attr( $waicb_widget_color ); ?>">
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="waicb_cookie_days"><?php esc_html_e( 'Durée du cookie (jours)', 'ai-chat-assistant' ); ?></label>
				</th>
				<td>
					<input type="number" id="waicb_cookie_days" name="waicb_cookie_days"
					       value="<?php echo esc_attr( $waicb_cookie_days ); ?>" min="1" max="365" class="small-text">
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="waicb_quick_replies"><?php esc_html_e( 'Suggestions de questions', 'ai-chat-assistant' ); ?></label>
				</th>
				<td>
					<textarea id="waicb_quick_replies" name="waicb_quick_replies"
					          rows="4" class="large-text"><?php echo esc_textarea( get_option( 'waicb_quick_replies', '' ) ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Une suggestion par ligne. Affichées comme boutons cliquables au démarrage du chat.', 'ai-chat-assistant' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Activer le chatbot', 'ai-chat-assistant' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="waicb_enabled" value="1" <?php checked( $waicb_enabled ); ?>>
						<?php esc_html_e( 'Afficher le chatbot sur le site', 'ai-chat-assistant' ); ?>
					</label>
				</td>
			</tr>
		</table>

		<!-- ── Règles d'affichage ─────────────────────────────────── -->
		<?php
		$waicb_display_mode  = get_option( 'waicb_display_mode', 'all' );
		$waicb_display_pages = get_option( 'waicb_display_pages', array() );
		if ( ! is_array( $waicb_display_pages ) ) {
			$waicb_display_pages = array();
		}
		$waicb_all_pages = get_pages( array( 'sort_column' => 'post_title', 'sort_order' => 'ASC' ) );
		?>
		<h2><?php esc_html_e( 'Règles d\'affichage', 'ai-chat-assistant' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Afficher le chatbot', 'ai-chat-assistant' ); ?></th>
				<td>
					<label style="display:block;margin-bottom:6px;">
						<input type="radio" name="waicb_display_mode" value="all"
						       <?php checked( $waicb_display_mode, 'all' ); ?>>
						<?php esc_html_e( 'Sur toutes les pages', 'ai-chat-assistant' ); ?>
					</label>
					<label style="display:block;margin-bottom:6px;">
						<input type="radio" name="waicb_display_mode" value="specific"
						       <?php checked( $waicb_display_mode, 'specific' ); ?>>
						<?php esc_html_e( 'Uniquement sur les pages sélectionnées', 'ai-chat-assistant' ); ?>
					</label>
					<label style="display:block;">
						<input type="radio" name="waicb_display_mode" value="exclude"
						       <?php checked( $waicb_display_mode, 'exclude' ); ?>>
						<?php esc_html_e( 'Sur toutes les pages sauf les sélectionnées', 'ai-chat-assistant' ); ?>
					</label>
				</td>
			</tr>
			<tr id="waicb-display-pages-row"
			    <?php echo 'all' === $waicb_display_mode ? 'style="display:none;"' : ''; ?>>
				<th scope="row"><?php esc_html_e( 'Pages', 'ai-chat-assistant' ); ?></th>
				<td>
					<div style="max-height:200px;overflow-y:auto;border:1px solid #ddd;border-radius:4px;padding:8px 12px;">
						<?php foreach ( $waicb_all_pages as $waicb_page ) : ?>
						<label style="display:block;margin-bottom:4px;">
							<input type="checkbox" name="waicb_display_pages[]"
							       value="<?php echo esc_attr( $waicb_page->ID ); ?>"
							       <?php checked( in_array( (string) $waicb_page->ID, array_map( 'strval', $waicb_display_pages ), true ) ); ?>>
							<?php echo esc_html( $waicb_page->post_title ); ?>
						</label>
						<?php endforeach; ?>
						<?php if ( empty( $waicb_all_pages ) ) : ?>
							<em><?php esc_html_e( 'Aucune page trouvée.', 'ai-chat-assistant' ); ?></em>
						<?php endif; ?>
					</div>
					<p class="description"><?php esc_html_e( 'Le shortcode [ai_chatbot] fonctionne toujours indépendamment de cette règle.', 'ai-chat-assistant' ); ?></p>
				</td>
			</tr>
		</table>

		<?php submit_button( __( 'Enregistrer les réglages', 'ai-chat-assistant' ) ); ?>
	</form>

	<!-- ── Danger Zone ───────────────────────────────────────────────── -->
	<div class="waicb-danger-zone">
		<h2><?php esc_html_e( 'Zone de danger', 'ai-chat-assistant' ); ?></h2>
		<p><?php esc_html_e( 'Cette action supprime définitivement toutes les conversations et logs.', 'ai-chat-assistant' ); ?></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="waicb-clear-form">
			<input type="hidden" name="action" value="waicb_clear_conversations">
			<?php wp_nonce_field( 'waicb_clear_conversations' ); ?>
			<button type="submit" class="button waicb-btn-danger" id="waicb-clear-btn">
				<?php esc_html_e( 'Vider toutes les conversations', 'ai-chat-assistant' ); ?>
			</button>
		</form>
	</div>
</div>
