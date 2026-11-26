<?php
/**
 * Contract for the model behind the AI writing assistant.
 *
 * @package AK\Theme
 */

declare( strict_types = 1 );

namespace AK\Theme\Modules\AI;

/**
 * Interface AiClient
 *
 * One request, one text reply. ClaudeClient implements it with the Anthropic SDK; tests (and sites that want another
 * gateway) swap it with the `aktheme_ai_client` filter.
 *
 * @since 1.3.0
 */
interface AiClient {

	/**
	 * Send one user turn and return the reply text.
	 *
	 * @param string                                                                                                                              $system     System prompt.
	 * @param list<array{type: 'text', text: string}|array{type: 'image', mime: 'image/jpeg'|'image/png'|'image/gif'|'image/webp', data: string}> $content User content: text blocks, and images as base64 `data` with their `mime` type.
	 * @param int                                                                                                                                 $max_tokens Output cap.
	 *
	 * @return string|\WP_Error The reply text, or an error to show the editor.
	 */
	public function complete( string $system, array $content, int $max_tokens = 4096 ): string|\WP_Error;
}
