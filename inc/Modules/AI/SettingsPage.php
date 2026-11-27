<?php
/**
 * Appearance > AK AI: API key, model, switches and MCP connection details.
 *
 * @package AK\Theme
 */

declare( strict_types = 1 );

namespace AK\Theme\Modules\AI;

use AK\Theme\Core\Settings;
use rtCamp\WPPrimitives\Contracts\Abstracts\AbstractAdminPage;

/**
 * Class SettingsPage
 *
 * Core WordPress UI only. Saving goes through admin-post.php with a nonce and a `manage_options` check. The API key
 * field is write-only: the page shows where the key comes from and its last four characters, never the key.
 *
 * @since 1.3.0
 */
final class SettingsPage extends AbstractAdminPage {

	/**
	 * Notice transient prefix (per user).
	 *
	 * @var string
	 */
	private const NOTICE = 'aktheme_ai_notice_';

	/**
	 * {@inheritDoc}
	 */
	public static function get_slug(): string {
		return 'aktheme-ai';
	}

	/**
	 * {@inheritDoc}
	 */
	public function register_hooks(): void {
		parent::register_hooks();

		add_action( 'admin_post_aktheme_ai_save', [ $this, 'save' ] );
		add_action( 'admin_post_aktheme_ai_test', [ $this, 'test' ] );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_parent_slug(): string {
		return 'themes.php';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_page_title(): string {
		return __( 'AK AI', 'aktheme' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_menu_title(): string {
		return __( 'AK AI', 'aktheme' );
	}

	/**
	 * Save the form.
	 *
	 * @since 1.3.0
	 */
	public function save(): void {
		$this->guard( 'aktheme_ai_save' );

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Verified in guard().
		if ( isset( $_POST['remove_key'] ) ) {
			ApiKey::delete();
		} elseif ( ! empty( $_POST['api_key'] ) ) {
			$key = sanitize_text_field( wp_unslash( $_POST['api_key'] ) );
			if ( ! str_starts_with( $key, 'sk-ant-' ) ) {
				$this->notice( 'error', __( 'That does not look like an Anthropic API key (they start with sk-ant-). Nothing was saved.', 'aktheme' ) );
				$this->back();
			}
			ApiKey::save( $key );
		}

		$model = isset( $_POST['ai_model'] ) ? sanitize_key( wp_unslash( $_POST['ai_model'] ) ) : '';
		update_option(
			Settings::OPTION,
			Settings::sanitize(
				[
					'ai_enabled'  => ! empty( $_POST['ai_enabled'] ),
					'mcp_enabled' => ! empty( $_POST['mcp_enabled'] ),
					'ai_model'    => $model,
				]
			)
		);
		// phpcs:enable

		$this->notice( 'success', __( 'Settings saved.', 'aktheme' ) );
		$this->back();
	}

	/**
	 * Send one tiny request to check the key and model.
	 *
	 * @since 1.3.0
	 */
	public function test(): void {
		$this->guard( 'aktheme_ai_test' );

		$client = Assistant::client();
		$reply  = is_wp_error( $client )
			? $client
			: $client->complete(
				'Reply with the single word OK.',
				[
					[
						'type' => 'text',
						'text' => 'Connection test.',
					],
				],
				256
			);

		if ( is_wp_error( $reply ) ) {
			$this->notice( 'error', $reply->get_error_message() );
		} else {
			/* translators: %s: model ID. */
			$this->notice( 'success', sprintf( __( 'Connected. %s answered.', 'aktheme' ), (string) Settings::get( 'ai_model' ) ) );
		}

		$this->back();
	}

	/**
	 * {@inheritDoc}
	 */
	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$notice = get_transient( self::NOTICE . get_current_user_id() );
		delete_transient( self::NOTICE . get_current_user_id() );

		$settings = Settings::all();
		$source   = ApiKey::source();
		$sources  = [
			'constant' => __( 'from the AKTHEME_ANTHROPIC_API_KEY constant in wp-config.php', 'aktheme' ),
			'env'      => __( 'from the ANTHROPIC_API_KEY environment variable', 'aktheme' ),
			'option'   => __( 'saved here (encrypted)', 'aktheme' ),
		];
		$user     = wp_get_current_user();
		$command  = sprintf(
			'claude mcp add --transport http aktheme %s --header "Authorization: Basic $(printf \'%%s\' \'%s:YOUR-APPLICATION-PASSWORD\' | base64)"',
			McpServer::url(),
			$user->user_login
		);
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'AK AI', 'aktheme' ); ?></h1>
			<?php if ( is_array( $notice ) ) : ?>
				<div class="notice notice-<?php echo esc_attr( (string) $notice['type'] ); ?> is-dismissible"><p><?php echo esc_html( (string) $notice['message'] ); ?></p></div>
			<?php endif; ?>

			<h2><?php esc_html_e( 'AI writing assistant', 'aktheme' ); ?></h2>
			<p><?php esc_html_e( 'Adds an "AK AI" panel and block toolbar buttons to the editor: meta descriptions, title ideas, rewrites, new sections, FAQs and image alt text, written by Claude. Text (and, for alt text, the image) is sent to Anthropic only when an editor clicks one of them.', 'aktheme' ); ?></p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="aktheme_ai_save">
				<?php wp_nonce_field( 'aktheme_ai_save' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="aktheme-api-key"><?php esc_html_e( 'Anthropic API key', 'aktheme' ); ?></label></th>
						<td>
							<?php if ( '' !== $source ) : ?>
								<p>
									<?php
									/* translators: 1: last four characters of the key, 2: where the key comes from. */
									echo esc_html( sprintf( __( 'A key ending in %1$s is in use, %2$s.', 'aktheme' ), ApiKey::last4(), $sources[ $source ] ) );
									?>
								</p>
							<?php endif; ?>
							<?php if ( in_array( $source, [ '', 'option' ], true ) ) : ?>
								<input type="password" id="aktheme-api-key" name="api_key" class="regular-text code" autocomplete="off" placeholder="<?php echo esc_attr( '' === $source ? 'sk-ant-…' : __( 'Enter a new key to replace it', 'aktheme' ) ); ?>">
								<?php if ( 'option' === $source ) : ?>
									<label><input type="checkbox" name="remove_key" value="1"> <?php esc_html_e( 'Remove the saved key', 'aktheme' ); ?></label>
								<?php endif; ?>
								<p class="description">
									<?php esc_html_e( 'Create one at console.anthropic.com. Safer: define AKTHEME_ANTHROPIC_API_KEY in wp-config.php instead, so the key is never stored in the database.', 'aktheme' ); ?>
								</p>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="aktheme-ai-model"><?php esc_html_e( 'Model', 'aktheme' ); ?></label></th>
						<td>
							<select id="aktheme-ai-model" name="ai_model">
								<?php foreach ( Settings::AI_MODELS as $model ) : ?>
									<option value="<?php echo esc_attr( $model ); ?>" <?php selected( $settings['ai_model'], $model ); ?>><?php echo esc_html( $model ); ?></option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'claude-opus-5-5 is the default. Requests use low effort, since the tasks are short; Opus, Sonnet and Fable retry automatically on a fallback model if a request is declined.', 'aktheme' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Switches', 'aktheme' ); ?></th>
						<td>
							<label><input type="checkbox" name="ai_enabled" value="1" <?php checked( $settings['ai_enabled'] ); ?>> <?php esc_html_e( 'Show the AI writing assistant in the editor', 'aktheme' ); ?></label><br>
							<label><input type="checkbox" name="mcp_enabled" value="1" <?php checked( $settings['mcp_enabled'] ); ?>> <?php esc_html_e( 'Serve the MCP endpoint for AI agents', 'aktheme' ); ?></label>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="aktheme_ai_test">
				<?php wp_nonce_field( 'aktheme_ai_test' ); ?>
				<?php submit_button( __( 'Test connection', 'aktheme' ), 'secondary', 'submit', false, ApiKey::has() ? [] : [ 'disabled' => 'disabled' ] ); ?>
			</form>

			<hr>
			<h2><?php esc_html_e( 'Connect an AI agent (MCP)', 'aktheme' ); ?></h2>
			<?php if ( ! $settings['mcp_enabled'] ) : ?>
				<p><?php esc_html_e( 'The MCP endpoint is switched off.', 'aktheme' ); ?></p>
			<?php else : ?>
				<p><?php esc_html_e( 'Claude Code, Claude Desktop and other MCP clients can read and change this site\'s design, header, footer, menus, shared sections and pages through the theme\'s abilities. Each change keeps revisions, and each user can only do what their role allows.', 'aktheme' ); ?></p>
				<ol>
					<li>
						<?php
						printf(
							/* translators: %s: link to the profile screen. */
							esc_html__( 'Create an application password in %s (use an Editor account to allow page changes only).', 'aktheme' ),
							'<a href="' . esc_url( admin_url( 'profile.php#application-passwords-section' ) ) . '">' . esc_html__( 'Users > Profile', 'aktheme' ) . '</a>'
						);
						?>
					</li>
					<li><?php esc_html_e( 'Endpoint:', 'aktheme' ); ?> <code><?php echo esc_html( McpServer::url() ); ?></code></li>
					<li><?php esc_html_e( 'Claude Code: run this in a terminal (replace the password):', 'aktheme' ); ?><br><textarea class="large-text code" rows="3" readonly onclick="this.select()"><?php echo esc_textarea( $command ); ?></textarea></li>
				</ol>
				<?php if ( ! wp_is_application_passwords_available() ) : ?>
					<div class="notice notice-warning inline"><p><?php esc_html_e( 'Application passwords are unavailable on this site. They need HTTPS (or a local development site).', 'aktheme' ); ?></p></div>
				<?php endif; ?>
				<?php if ( ! function_exists( 'wp_get_abilities' ) ) : ?>
					<div class="notice notice-warning inline"><p><?php esc_html_e( 'The abilities need WordPress 6.9 or later.', 'aktheme' ); ?></p></div>
				<?php endif; ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Check the capability and nonce, or stop.
	 *
	 * @param string $action Nonce action.
	 */
	private function guard( string $action ): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to change these settings.', 'aktheme' ), 403 );
		}
		check_admin_referer( $action );
	}

	/**
	 * Remember a notice for the next page load.
	 *
	 * @param string $type    success|error.
	 * @param string $message Message.
	 */
	private function notice( string $type, string $message ): void {
		set_transient(
			self::NOTICE . get_current_user_id(),
			[
				'type'    => $type,
				'message' => $message,
			],
			MINUTE_IN_SECONDS
		);
	}

	/**
	 * Back to the page.
	 */
	private function back(): never {
		wp_safe_redirect( admin_url( 'themes.php?page=' . self::get_slug() ) );
		exit;
	}
}
