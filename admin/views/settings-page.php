<?php
/**
 * Admin view — Settings page (Cloud-only).
 *
 * @package WordPressAIChatbot
 */

defined( 'ABSPATH' ) || exit;

$waicb_has_cloud_key   = '' !== get_option( 'waicb_cloud_key', '' );
$waicb_instructions    = get_option( 'waicb_instructions', '' );
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
	<h1><?php esc_html_e( 'AI Chat Assistant — Réglages', 'ai-chat-assistant' ); ?></h1>

	<?php if ( $waicb_saved ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Réglages enregistrés.', 'ai-chat-assistant' ); ?></p></div>
	<?php endif; ?>

	<?php if ( $waicb_cleared ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Toutes les conversations ont été supprimées.', 'ai-chat-assistant' ); ?></p></div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="waicb_save_settings">
		<?php wp_nonce_field( 'waicb_settings_save' ); ?>

		<!-- ── Connexion Jokko AI ─────────────────────────────────────── -->
		<h2><?php esc_html_e( 'Connexion Jokko AI', 'ai-chat-assistant' ); ?></h2>
		<p class="description" style="max-width:680px;">
			<?php
			printf(
				/* translators: %s: dashboard URL */
				esc_html__( 'Le chatbot est propulsé par le service Jokko AI (crédits prépayés, 1 crédit = 1 message). Créez un compte et récupérez votre clé sur %s, puis collez-la ci-dessous. Aucune clé OpenAI/Anthropic à fournir.', 'ai-chat-assistant' ),
				'<a href="' . esc_url( WAICB_CLOUD_DASHBOARD ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( WAICB_CLOUD_DASHBOARD ) . '</a>'
			);
			?>
		</p>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="waicb_cloud_key"><?php esc_html_e( 'Clé de compte', 'ai-chat-assistant' ); ?></label>
				</th>
				<td>
					<input type="password" id="waicb_cloud_key" name="waicb_cloud_key" class="regular-text"
					       value="<?php echo $waicb_has_cloud_key ? '****************' : ''; ?>"
					       autocomplete="new-password" placeholder="aica_live_...">
					<p class="description"><?php esc_html_e( 'Laissez vide pour conserver la valeur actuelle. Enregistrez avant de tester.', 'ai-chat-assistant' ); ?></p>
					<button type="button" id="waicb-test-cloud" class="button button-secondary" data-provider="cloud" style="margin-top:6px;">
						<?php esc_html_e( 'Tester la connexion', 'ai-chat-assistant' ); ?>
					</button>
					<span id="waicb-test-cloud-result" style="margin-left:10px;font-weight:600;"></span>
					<p style="margin:12px 0 0;">
						<?php if ( $waicb_has_cloud_key ) : ?>
							<a href="<?php echo esc_url( WAICB_CLOUD_DASHBOARD ); ?>" target="_blank" rel="noopener" class="button button-secondary">
								<?php esc_html_e( 'Gérer mes crédits / Recharger', 'ai-chat-assistant' ); ?>
							</a>
						<?php else : ?>
							<a href="<?php echo esc_url( WAICB_CLOUD_DASHBOARD . '?signup=1' ); ?>" target="_blank" rel="noopener" class="button button-primary">
								<?php esc_html_e( 'Créer un compte Jokko AI (gratuit)', 'ai-chat-assistant' ); ?>
							</a>
						<?php endif; ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="waicb_instructions"><?php esc_html_e( 'Instructions de l\'assistant', 'ai-chat-assistant' ); ?></label>
				</th>
				<td>
					<textarea id="waicb_instructions" name="waicb_instructions" rows="6" class="large-text"
					          maxlength="2000" placeholder="<?php esc_attr_e( 'Ex. : Tu es l\'assistant du site Exemple. Réponds en français, de façon concise et chaleureuse, à propos de nos services…', 'ai-chat-assistant' ); ?>"><?php echo esc_textarea( $waicb_instructions ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Définit le rôle et le ton de votre assistant (persona). Transmis au service Jokko AI à chaque message. 2000 caractères max.', 'ai-chat-assistant' ); ?></p>
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
