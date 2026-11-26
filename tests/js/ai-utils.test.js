import {
	AI_PATH,
	buildRequest,
	errorMessage,
	faqBlockSpecs,
	richTextToString,
	specsToBlocks,
	toPlainText,
} from '../../src/js/shared/ai-utils';

const labels = { eyebrow: 'FAQ', heading: 'Frequently asked questions' };
const items = [
	{ question: 'How much?', answer: 'From <strong>₹24,999</strong>.' },
	{ question: 'How long?', answer: 'Two to four weeks.' },
];

describe('buildRequest', () => {
	it('posts the task and input to the assistant route', () => {
		expect(
			buildRequest('rewrite', { text: 'Hi', mode: 'shorten' })
		).toEqual({
			path: AI_PATH,
			method: 'POST',
			data: { task: 'rewrite', input: { text: 'Hi', mode: 'shorten' } },
		});
	});
});

describe('richTextToString', () => {
	it('handles strings, RichTextData and empty values', () => {
		expect(richTextToString('a <em>b</em>')).toBe('a <em>b</em>');
		expect(
			richTextToString({ toHTMLString: () => '<strong>x</strong>' })
		).toBe('<strong>x</strong>');
		expect(richTextToString(null)).toBe('');
		expect(richTextToString(undefined)).toBe('');
	});
});

describe('toPlainText', () => {
	it('drops block comments and tags and keeps line breaks between blocks', () => {
		const html =
			'<!-- wp:heading --><h2>Title &amp; more</h2><!-- /wp:heading -->\n<!-- wp:paragraph --><p>One&nbsp;<a href="#">link</a>.</p><!-- /wp:paragraph -->';
		expect(toPlainText(html)).toBe('Title & more\nOne link .');
	});

	it('removes scripts and styles', () => {
		expect(
			toPlainText('<p>a</p><script>alert(1)</script><style>p{}</style>')
		).toBe('a');
	});

	it('returns an empty string for empty input', () => {
		expect(toPlainText('')).toBe('');
		expect(toPlainText(null)).toBe('');
	});
});

describe('faqBlockSpecs', () => {
	it('nests the items in the ak/faq accordion when the block exists', () => {
		const [faq] = faqBlockSpecs(items, true, labels);
		expect(faq.name).toBe('ak/faq');

		const container = faq.innerBlocks[0];
		expect(container.attributes.className).toBe('ak-container narrow');

		const [head, accordion] = container.innerBlocks;
		expect(head.innerBlocks.map((b) => b.name)).toEqual([
			'core/paragraph',
			'core/heading',
		]);
		expect(accordion.attributes.className).toBe('faq-acc');
		expect(accordion.innerBlocks).toHaveLength(2);
		expect(accordion.innerBlocks[0]).toEqual({
			name: 'core/details',
			attributes: { summary: 'How much?' },
			innerBlocks: [
				{
					name: 'core/paragraph',
					attributes: { content: 'From <strong>₹24,999</strong>.' },
					innerBlocks: [],
				},
			],
		});
	});

	it('falls back to a heading and Details blocks', () => {
		const specs = faqBlockSpecs(items, false, labels);
		expect(specs.map((s) => s.name)).toEqual([
			'core/heading',
			'core/details',
			'core/details',
		]);
		expect(specs[0].attributes).toEqual({ content: labels.heading });
	});

	it('skips incomplete items', () => {
		const specs = faqBlockSpecs(
			[
				{ question: 'Q?', answer: '' },
				null,
				{ question: 'Ok?', answer: 'Yes.' },
			],
			false,
			labels
		);
		expect(specs).toHaveLength(2);
	});
});

describe('specsToBlocks', () => {
	it('builds blocks recursively with the given createBlock', () => {
		const createBlock = jest.fn((name, attributes, innerBlocks) => ({
			name,
			attributes,
			innerBlocks,
		}));
		const blocks = specsToBlocks(
			faqBlockSpecs(items, true, labels),
			createBlock
		);

		expect(blocks[0].name).toBe('ak/faq');
		// ak/faq, container, head, eyebrow, heading, accordion, 2 details, 2 paragraphs.
		expect(createBlock).toHaveBeenCalledTimes(10);
	});
});

describe('errorMessage', () => {
	it('uses the error message, else the fallback', () => {
		expect(errorMessage({ message: 'Bad key' }, 'Failed')).toBe('Bad key');
		expect(errorMessage({}, 'Failed')).toBe('Failed');
		expect(errorMessage(undefined, 'Failed')).toBe('Failed');
	});
});
