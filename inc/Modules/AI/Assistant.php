<?php
/**
 * AI writing assistant: REST endpoint and editor script.
 *
 * @package AK\Theme
 */

declare( strict_types = 1 );

namespace AK\Theme\Modules\AI;

use AK\Theme\Core\Settings;
use WP_REST_Request;
use WP_REST_Response;
use rtCamp\WPPrimitives\Contracts\Interfaces\Registrable;

/**
 * Class Assistant
 *
 * `POST /wp-json/aktheme/v1/ai` with `{ task, input }` runs one of a fixed set of writing tasks on Claude and returns the
 * result for the editor to preview. The server owns every prompt: the editor only sends the text to work on.
 *
 * Tasks: meta_description, title_ideas, rewrite (improve, shorten, expand, grammar, seo), write_section, faq, alt_text.
 *
 * Nothing runs for visitors: the script loads only in the block editor, for users who can edit posts, when the
 * assistant is on. Post text (and, for alt text, the image) is sent to Anthropic only when an editor clicks.
 *
 * @since 1.3.0
 */
final class Assistant implements Registrable {

	/**
	 * Script handle.
	 *
	 * @var string
	 */
	public const HANDLE = 'ak-ai-assistant';

	/**
	 * Longest text accepted (characters). Longer input is refused, never silently cut.
	 *
	 * @var int
	 */
	public const MAX_INPUT = 200000;

	/**
	 * Rewrite modes and their instruction.
	 *
	 * @var array<string, string>
	 */
	private const REWRITE_MODES = [
		'improve' => 'Improve clarity, flow and persuasiveness. Keep the meaning and roughly the same length.',
		'shorten' => 'Make it about half as long. Keep the key point.',
		'expand'  => 'Expand it to about twice the length with useful, specific detail. Do not invent facts, numbers or claims about the business.',
		'grammar' => 'Fix spelling, grammar and punctuation only. Change nothing else.',
		'seo'     => 'Make it more search-friendly: lead with the main topic, use plain words people search for, keep it natural. Do not keyword-stuff.',
	];

	/**
	 * Inline HTML allowed in rewritten text (it goes back into a paragraph or heading).
	 *
	 * @var array<string, array<string, bool>>
	 */
	private const INLINE_TAGS = [
		'strong' => [],
		'em'     => [],
		'b'      => [],
		'i'      => [],
		'code'   => [],
		'br'     => [],
		'a'      => [
			'href'   => true,
			'target' => true,
			'rel'    => true,
		],
	];

	/**
	 * {@inheritDoc}
	 */
	public function register_hooks(): void {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue' ] );
	}

	/**
	 * Whether the assistant is on.
	 *
	 * @since 1.3.0
	 */
	public static function enabled(): bool {
		/**
		 * Filters whether the AI writing assistant is available.
		 *
		 * @since 1.3.0
		 *
		 * @param bool $enabled Default: the `ai_enabled` theme setting.
		 */
		return (bool) apply_filters( 'aktheme_ai_enabled', (bool) Settings::get( 'ai_enabled' ) );
	}

	/**
	 * The model client: the `aktheme_ai_client` filter, or Claude with the configured key and model.
	 *
	 * @since 1.3.0
	 */
	public static function client(): AiClient|\WP_Error {
		/**
		 * Filters the client the assistant sends its requests to. Return an AiClient to replace Claude (tests, gateways).
		 *
		 * @since 1.3.0
		 *
		 * @param AiClient|null $client Default null: use Claude.
		 */
		$client = apply_filters( 'aktheme_ai_client', null );
		if ( $client instanceof AiClient ) {
			return $client;
		}

		$key = ApiKey::get();
		if ( '' === $key ) {
			return new \WP_Error( 'aktheme_ai_no_key', __( 'Add an Anthropic API key in Appearance > AK AI first.', 'aktheme' ), [ 'status' => 400 ] );
		}

		return new ClaudeClient( $key, (string) Settings::get( 'ai_model' ) );
	}

	/**
	 * Register the route.
	 *
	 * @since 1.3.0
	 */
	public function register_routes(): void {
		if ( ! self::enabled() ) {
			return;
		}

		register_rest_route(
			McpServer::NAMESPACE,
			'/ai',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'handle' ],
				'permission_callback' => static fn (): bool => current_user_can( 'edit_posts' ),
				'args'                => [
					'task'  => [
						'type'     => 'string',
						'enum'     => [ 'meta_description', 'title_ideas', 'rewrite', 'write_section', 'faq', 'alt_text' ],
						'required' => true,
					],
					'input' => [
						'type'     => 'object',
						'required' => true,
					],
				],
			]
		);
	}

	/**
	 * Run a task.
	 *
	 * @since 1.3.0
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 */
	public function handle( WP_REST_Request $request ): WP_REST_Response|\WP_Error {
		$task  = (string) $request->get_param( 'task' );
		$input = (array) $request->get_param( 'input' );

		$job = $this->build( $task, $input );
		if ( is_wp_error( $job ) ) {
			return $job;
		}

		$client = self::client();
		if ( is_wp_error( $client ) ) {
			return $client;
		}

		$reply = $client->complete( $this->system_prompt( $job['instructions'] ), $job['content'], $job['max_tokens'] );
		if ( is_wp_error( $reply ) ) {
			return $reply;
		}

		return new WP_REST_Response( [ 'result' => $this->shape( $task, $reply ) ] );
	}

	/**
	 * The request for a task: instructions, user content and output cap.
	 *
	 * @param string               $task  Task.
	 * @param array<string, mixed> $input Input.
	 *
	 * @return array{instructions: string, content: list<array{type: 'text', text: string}|array{type: 'image', mime: 'image/jpeg'|'image/png'|'image/gif'|'image/webp', data: string}>, max_tokens: int}|\WP_Error Job.
	 */
	private function build( string $task, array $input ): array|\WP_Error {
		if ( 'alt_text' === $task ) {
			return $this->build_alt_text( (int) ( $input['attachment_id'] ?? 0 ), (string) ( $input['context'] ?? '' ) );
		}

		$title = $this->text( $input, 'title' );
		if ( is_wp_error( $title ) ) {
			return $title;
		}
		$text = $this->text( $input, 'text' );
		if ( is_wp_error( $text ) ) {
			return $text;
		}

		switch ( $task ) {
			case 'meta_description':
				$instructions = 'Write the meta description for this page: one or two sentences, 140 to 155 characters, plain text, no quotes. Say what the page offers and why to click. Reply with the description only.';
				$body         = "Title: {$title}\n\nContent:\n{$text}";
				$max          = 1024;
				break;

			case 'title_ideas':
				$instructions = 'Suggest 5 SEO page titles for this content, each under 60 characters, with the main topic near the start. Reply with the 5 titles only, one per line, no numbering or quotes.';
				$body         = "Current title: {$title}\n\nContent:\n{$text}";
				$max          = 1024;
				break;

			case 'rewrite':
				$mode = (string) ( $input['mode'] ?? 'improve' );
				if ( ! isset( self::REWRITE_MODES[ $mode ] ) ) {
					return $this->invalid( __( 'Unknown rewrite mode.', 'aktheme' ) );
				}
				if ( '' === trim( wp_strip_all_tags( $text ) ) ) {
					return $this->invalid( __( 'The block has no text to rewrite.', 'aktheme' ) );
				}
				$instructions = self::REWRITE_MODES[ $mode ] . ' Keep any inline HTML tags (links, bold, italics) that still apply. Reply with the rewritten text only.';
				$body         = $text;
				$max          = 4096;
				break;

			case 'write_section':
				$brief = $this->text( $input, 'brief' );
				if ( is_wp_error( $brief ) ) {
					return $brief;
				}
				if ( '' === trim( $brief ) ) {
					return $this->invalid( __( 'Describe the section you want written.', 'aktheme' ) );
				}
				$instructions = 'Write a web page section from the brief. Use HTML only with these tags: h2, h3, p, ul, ol, li, strong, em. Start with an h2. Keep paragraphs short. Do not invent prices, statistics, testimonials or contact details: write [placeholder] where one is needed. Reply with the HTML only, no code fences.';
				$body         = "Page title: {$title}\n\nBrief:\n{$brief}\n\nExisting page content, for context and to avoid repeating it:\n{$text}";
				$max          = 8192;
				break;

			case 'faq':
				$count        = max( 3, min( 10, (int) ( $input['count'] ?? 5 ) ) );
				$instructions = "Write {$count} frequently asked questions with answers that real customers would search for about this page. Answers: 1 to 3 sentences, factual, based on the content; do not invent prices or guarantees. Format each one exactly as two lines:\nQ: <question>\nA: <answer>\nand leave a blank line between them. Reply with the questions only.";
				$body         = "Title: {$title}\n\nContent:\n{$text}";
				$max          = 4096;
				break;

			default:
				return $this->invalid( __( 'Unknown task.', 'aktheme' ) );
		}

		return [
			'instructions' => $instructions,
			'content'      => [
				[
					'type' => 'text',
					'text' => $body,
				],
			],
			'max_tokens'   => $max,
		];
	}

	/**
	 * Alt text request: the image goes inline as base64 (local and private sites are not reachable by URL).
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $context       Surrounding text, e.g. the caption or the page title.
	 *
	 * @return array{instructions: string, content: list<array{type: 'text', text: string}|array{type: 'image', mime: 'image/jpeg'|'image/png'|'image/gif'|'image/webp', data: string}>, max_tokens: int}|\WP_Error Job.
	 */
	private function build_alt_text( int $attachment_id, string $context ): array|\WP_Error {
		if ( ! current_user_can( 'upload_files' ) ) {
			return new \WP_Error( 'aktheme_ai_forbidden', __( 'You cannot use media files.', 'aktheme' ), [ 'status' => 403 ] );
		}
		if ( $attachment_id < 1 || ! wp_attachment_is_image( $attachment_id ) ) {
			return $this->invalid( __( 'Choose an image from the Media Library first.', 'aktheme' ) );
		}

		$image = $this->image_for_model( $attachment_id );
		if ( is_wp_error( $image ) ) {
			return $image;
		}

		return [
			'instructions' => 'Write the alt text for this image: what it shows, in one sentence under 125 characters, for someone who cannot see it. No "image of" or "picture of". Reply with the alt text only.',
			'content'      => [
				[
					'type' => 'image',
					'mime' => $image['mime'],
					'data' => $image['data'],
				],
				[
					'type' => 'text',
					'text' => '' === trim( $context ) ? 'Write the alt text.' : 'Where it appears: ' . wp_strip_all_tags( $context ),
				],
			],
			'max_tokens'   => 512,
		];
	}

	/**
	 * The image as base64, at most 1568px on its longest side (larger images are downscaled by the API anyway).
	 *
	 * @param int $attachment_id Attachment ID.
	 *
	 * @return array{mime: 'image/jpeg'|'image/png'|'image/gif'|'image/webp', data: string}|\WP_Error Image.
	 */
	private function image_for_model( int $attachment_id ): array|\WP_Error {
		$file = get_attached_file( $attachment_id );
		if ( ! $file || ! is_readable( $file ) ) {
			return $this->invalid( __( 'The image file could not be read.', 'aktheme' ) );
		}

		$allowed = [ 'image/jpeg', 'image/png', 'image/gif', 'image/webp' ];
		$editor  = wp_get_image_editor( $file );
		if ( is_wp_error( $editor ) ) {
			return $editor;
		}

		$size = $editor->get_size();
		if ( max( $size['width'], $size['height'] ) > 1568 ) {
			$editor->resize( 1568, 1568, false );
		}

		$mime = (string) ( wp_check_filetype( $file )['type'] ?? '' );
		$mime = in_array( $mime, $allowed, true ) ? $mime : 'image/jpeg';
		$temp = wp_tempnam( 'aktheme-alt' );
		$out  = $editor->save( $temp, $mime );
		if ( is_wp_error( $out ) ) {
			wp_delete_file( $temp );
			return $out;
		}

		$data = file_get_contents( $out['path'] ); // phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown -- A local temp file just written by the image editor.
		wp_delete_file( $temp );
		wp_delete_file( $out['path'] );

		if ( false === $data || strlen( $data ) > 5 * MB_IN_BYTES ) {
			return $this->invalid( __( 'The image is too large to describe.', 'aktheme' ) );
		}

		$saved_mime = match ( (string) $out['mime-type'] ) {
			'image/png'  => 'image/png',
			'image/gif'  => 'image/gif',
			'image/webp' => 'image/webp',
			'image/jpeg' => 'image/jpeg',
			default      => null,
		};
		if ( null === $saved_mime ) {
			return $this->invalid( __( 'This image type cannot be described. Use a JPEG, PNG, GIF or WebP image.', 'aktheme' ) );
		}

		return [
			'mime' => $saved_mime,
			'data' => base64_encode( $data ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- The Messages API takes images as base64.
		];
	}

	/**
	 * The system prompt: the site's voice plus the task.
	 *
	 * @param string $instructions Task instructions.
	 */
	private function system_prompt( string $instructions ): string {
		return sprintf(
			"You are the writing assistant for the website \"%1\$s\"%2\$s. Write for the site's visitors in the site's language (%3\$s), in a clear, friendly, professional voice. Never make up facts about the business.\n\nTask: %4\$s",
			wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
			get_bloginfo( 'description' ) ? ' (' . wp_specialchars_decode( get_bloginfo( 'description' ), ENT_QUOTES ) . ')' : '',
			get_bloginfo( 'language' ),
			$instructions
		);
	}

	/**
	 * Turn the reply into what the editor needs.
	 *
	 * @param string $task  Task.
	 * @param string $reply Model reply.
	 *
	 * @return string|list<string>|list<array{question: string, answer: string}> Result.
	 */
	private function shape( string $task, string $reply ): string|array {
		$reply = trim( (string) preg_replace( '/^```[a-z]*\s*|\s*```$/i', '', trim( $reply ) ) );

		switch ( $task ) {
			case 'title_ideas':
				$lines = array_map( static fn ( string $line ): string => trim( (string) preg_replace( '/^[\s\-\*\d\.\)]+/', '', $line ), " \t\"'" ), explode( "\n", $reply ) );
				return array_values( array_slice( array_filter( $lines ), 0, 5 ) );

			case 'faq':
				$items = [];
				preg_match_all( '/^Q:\s*(.+?)\s*\R+A:\s*(.+?)\s*(?=\R+Q:|\z)/ms', $reply, $matches, PREG_SET_ORDER );
				foreach ( $matches as $match ) {
					$items[] = [
						'question' => sanitize_text_field( $match[1] ),
						'answer'   => wp_kses( trim( $match[2] ), self::INLINE_TAGS ),
					];
				}
				return $items;

			case 'rewrite':
				return wp_kses( $reply, self::INLINE_TAGS );

			case 'write_section':
				return wp_kses(
					$reply,
					[
						'h2'     => [],
						'h3'     => [],
						'p'      => [],
						'ul'     => [],
						'ol'     => [],
						'li'     => [],
						'strong' => [],
						'em'     => [],
					]
				);

			default:
				return sanitize_text_field( $reply );
		}
	}

	/**
	 * A text input field, refused (never cut) when too long.
	 *
	 * @param array<string, mixed> $input Input.
	 * @param string               $key   Field.
	 *
	 * @return string|\WP_Error Value.
	 */
	private function text( array $input, string $key ): string|\WP_Error {
		$value = is_scalar( $input[ $key ] ?? null ) ? (string) $input[ $key ] : '';

		if ( mb_strlen( $value ) > self::MAX_INPUT ) {
			/* translators: %s: number of characters. */
			return $this->invalid( sprintf( __( 'The text is too long for the assistant (over %s characters). Select a shorter part.', 'aktheme' ), number_format_i18n( self::MAX_INPUT ) ) );
		}

		return $value;
	}

	/**
	 * A 400 error.
	 *
	 * @param string $message Message.
	 */
	private function invalid( string $message ): \WP_Error {
		return new \WP_Error( 'aktheme_ai_invalid', $message, [ 'status' => 400 ] );
	}

	/**
	 * Load the editor script (block editor only; never on the front end).
	 *
	 * @since 1.3.0
	 */
	public function enqueue(): void {
		if ( ! self::enabled() || ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		$asset_file = get_theme_file_path( 'assets/build/js/editor/ai-assistant.asset.php' );
		if ( ! is_readable( $asset_file ) ) {
			return;
		}
		$asset = require $asset_file; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable -- Built file.

		wp_enqueue_script(
			self::HANDLE,
			get_theme_file_uri( 'assets/build/js/editor/ai-assistant.js' ),
			(array) ( $asset['dependencies'] ?? [] ),
			(string) ( $asset['version'] ?? AKTHEME_VERSION ),
			true
		);
		wp_set_script_translations( self::HANDLE, 'aktheme', AKTHEME_PATH . '/languages' );

		wp_add_inline_script(
			self::HANDLE,
			'window.akthemeAI = ' . wp_json_encode(
				[
					'hasKey'      => ApiKey::has(),
					'canManage'   => current_user_can( 'manage_options' ),
					'settingsUrl' => admin_url( 'themes.php?page=' . SettingsPage::get_slug() ),
				]
			) . ';',
			'before'
		);
	}
}
