/**
 * Pure helpers for the AI writing assistant (no WordPress imports, so Jest can test them directly).
 */

export const AI_PATH = '/aktheme/v1/ai';

export const REWRITE_MODES = ['improve', 'shorten', 'expand', 'grammar', 'seo'];

/**
 * The REST request for one assistant task.
 *
 * @param {string} task  Task name (meta_description, title_ideas, rewrite, write_section, faq, alt_text).
 * @param {Object} input Task input.
 * @return {{path: string, method: string, data: {task: string, input: Object}}} apiFetch options.
 */
export function buildRequest(task, input) {
	return { path: AI_PATH, method: 'POST', data: { task, input } };
}

/**
 * A block attribute that may be a string or RichTextData (WordPress 6.5+) as an HTML string.
 *
 * @param {string|{toHTMLString: () => string}|null|undefined} value Attribute value.
 * @return {string} HTML.
 */
export function richTextToString(value) {
	if (null === value || undefined === value) {
		return '';
	}
	if ('string' === typeof value) {
		return value;
	}
	if ('function' === typeof value.toHTMLString) {
		return value.toHTMLString();
	}
	return String(value);
}

/**
 * Plain text from block HTML: tags and block comments removed, common entities decoded, whitespace collapsed.
 *
 * @param {string} html HTML.
 * @return {string} Text.
 */
export function toPlainText(html) {
	const entities = {
		'&amp;': '&',
		'&lt;': '<',
		'&gt;': '>',
		'&quot;': '"',
		'&#039;': "'",
		'&#39;': "'",
		'&nbsp;': ' ',
	};

	return String(html || '')
		.replace(/<!--[\s\S]*?-->/g, ' ')
		.replace(/<(script|style)[^>]*>[\s\S]*?<\/\1>/gi, ' ')
		.replace(/<\/(p|h[1-6]|li|div|summary|figcaption|br)>/gi, '\n')
		.replace(/<br\s*\/?>/gi, '\n')
		.replace(/<[^>]+>/g, ' ')
		.replace(/&(amp|lt|gt|quot|#0?39|nbsp);/g, (m) => entities[m] || m)
		.replace(/[ \t]+/g, ' ')
		.replace(/\s*\n\s*/g, '\n')
		.replace(/\n{3,}/g, '\n\n')
		.trim();
}

/**
 * Block specs for generated FAQs. With the theme's ak/faq block (FAQPage schema) the items go inside its accordion;
 * otherwise they become a heading and plain Details blocks.
 *
 * @param {Array<{question: string, answer: string}>} items    FAQ items.
 * @param {boolean}                                   hasAkFaq Whether the ak/faq block is registered.
 * @param {{eyebrow: string, heading: string}}        labels   Translated labels.
 * @return {Array<{name: string, attributes: Object, innerBlocks: Array}>} Block specs.
 */
export function faqBlockSpecs(items, hasAkFaq, labels) {
	const details = (items || [])
		.filter((item) => item && item.question && item.answer)
		.map((item) => ({
			name: 'core/details',
			attributes: { summary: item.question },
			innerBlocks: [
				{
					name: 'core/paragraph',
					attributes: { content: item.answer },
					innerBlocks: [],
				},
			],
		}));

	const heading = {
		name: 'core/heading',
		attributes: { content: labels.heading, className: 'title' },
		innerBlocks: [],
	};

	if (!hasAkFaq) {
		return [
			{ ...heading, attributes: { content: labels.heading } },
			...details,
		];
	}

	const layout = { type: 'default' };

	return [
		{
			name: 'ak/faq',
			attributes: {},
			innerBlocks: [
				{
					name: 'core/group',
					attributes: { className: 'ak-container narrow', layout },
					innerBlocks: [
						{
							name: 'core/group',
							attributes: { className: 'sec-head', layout },
							innerBlocks: [
								{
									name: 'core/paragraph',
									attributes: {
										content: labels.eyebrow,
										className: 'eyebrow-line faq-eyebrow',
									},
									innerBlocks: [],
								},
								heading,
							],
						},
						{
							name: 'core/group',
							attributes: { className: 'faq-acc', layout },
							innerBlocks: details,
						},
					],
				},
			],
		},
	];
}

/**
 * Turn block specs into blocks.
 *
 * @param {Array}                                                            specs       Block specs from faqBlockSpecs().
 * @param {(name: string, attributes: Object, innerBlocks: Array) => Object} createBlock The `createBlock` function from `wp.blocks`.
 * @return {Array} Blocks.
 */
export function specsToBlocks(specs, createBlock) {
	return specs.map((spec) =>
		createBlock(
			spec.name,
			spec.attributes,
			specsToBlocks(spec.innerBlocks || [], createBlock)
		)
	);
}

/**
 * The message of an apiFetch error.
 *
 * @param {{message?: string}|undefined} error    Rejection value.
 * @param {string}                       fallback Fallback message.
 * @return {string} Message.
 */
export function errorMessage(error, fallback) {
	if (error && 'string' === typeof error.message && error.message) {
		return error.message;
	}
	return fallback;
}
