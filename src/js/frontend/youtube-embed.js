/**
 * YouTube Embed blocks: the player iframe has no address until the visitor agrees in the consent notice
 * (inc/Modules/Media/Video.php gate_embed() keeps the video ID in data-ak-youtube). Then it loads from
 * youtube-nocookie.com. Enqueued only on pages with such a block.
 */

/**
 * Internal dependencies
 */
import { each, videoConsent } from '../../blocks-shared/view-helpers';

each('.ak-yt-embed iframe[data-ak-youtube]', (frame) => {
	const id = frame.dataset.akYoutube;
	if (!/^[\w-]{11}$/.test(id || '')) {
		return;
	}

	const box =
		frame.closest('.wp-block-embed__wrapper') || frame.parentElement;

	videoConsent(box, () => {
		frame.src = 'https://www.youtube-nocookie.com/embed/' + id + '?rel=0';
		frame.removeAttribute('data-ak-youtube');
	});
});
