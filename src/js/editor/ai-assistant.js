/**
 * AK AI: the AI writing assistant in the block editor.
 *
 * - Sidebar ("AK AI"): meta description, title ideas, write a section, FAQ.
 * - Block toolbar: Rewrite (paragraphs and headings) and Alt text (images).
 *
 * Every result opens in a preview first; nothing changes until the editor clicks Replace or Insert, and undo works.
 * Loaded only in the block editor (enqueue_block_editor_assets), never on the front end.
 */

import apiFetch from '@wordpress/api-fetch';
import {
	BlockControls,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { createBlock, getBlockType, rawHandler } from '@wordpress/blocks';
import {
	Button,
	ExternalLink,
	Modal,
	Notice,
	PanelBody,
	RangeControl,
	Spinner,
	TextareaControl,
	ToolbarButton,
	ToolbarDropdownMenu,
	ToolbarGroup,
} from '@wordpress/components';
import { createHigherOrderComponent } from '@wordpress/compose';
import { useDispatch, useSelect } from '@wordpress/data';
import {
	PluginSidebar,
	PluginSidebarMoreMenuItem,
	store as editorStore,
} from '@wordpress/editor';
import { RawHTML, useState } from '@wordpress/element';
import { addFilter } from '@wordpress/hooks';
import { __, _n, sprintf } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';

import {
	buildRequest,
	errorMessage,
	faqBlockSpecs,
	richTextToString,
	specsToBlocks,
	toPlainText,
} from '../shared/ai-utils';

const config = window.akthemeAI || {
	hasKey: false,
	canManage: false,
	settingsUrl: '',
};

const SIDEBAR = 'aktheme-ai';

const icon = (
	<svg
		xmlns="http://www.w3.org/2000/svg"
		viewBox="0 0 24 24"
		width="24"
		height="24"
		aria-hidden="true"
		focusable="false"
	>
		<path d="M12 2l1.8 5.4L19 9l-5.2 1.6L12 16l-1.8-5.4L5 9l5.2-1.6L12 2zm6.5 11l.9 2.6 2.6.9-2.6.9-.9 2.6-.9-2.6-2.6-.9 2.6-.9.9-2.6zM6 15l.7 2.1 2.1.7-2.1.7L6 20.6l-.7-2.1-2.1-.7 2.1-.7L6 15z" />
	</svg>
);

/**
 * Run one assistant task.
 *
 * @param {string} task  Task.
 * @param {Object} input Input.
 * @return {Promise<string|string[]|Object[]>} Result.
 */
async function runTask(task, input) {
	const response = await apiFetch(buildRequest(task, input));
	return response.result;
}

/**
 * Track one request: busy flag, error and result.
 *
 * @return {Object} State and a `run` function.
 */
function useTask() {
	const [busy, setBusy] = useState(false);
	const [error, setError] = useState('');
	const [result, setResult] = useState(null);

	const run = async (task, input) => {
		setBusy(true);
		setError('');
		setResult(null);
		try {
			const value = await runTask(task, input);
			setResult(value);
			return value;
		} catch (e) {
			setError(errorMessage(e, __('The AI request failed.', 'aktheme')));
			return null;
		} finally {
			setBusy(false);
		}
	};

	return { busy, error, result, setResult, setError, run };
}

/**
 * Explains what to do when no API key is configured.
 */
function NoKeyNotice() {
	return (
		<Notice status="warning" isDismissible={false}>
			{__('Add an Anthropic API key to use the AI assistant.', 'aktheme')}{' '}
			{config.canManage ? (
				<a href={config.settingsUrl}>
					{__('Open AK AI settings', 'aktheme')}
				</a>
			) : (
				__('Ask an administrator to set it up.', 'aktheme')
			)}
		</Notice>
	);
}

/**
 * Error notice.
 *
 * @param {Object} props         Props.
 * @param {string} props.message Message.
 */
function ErrorNotice({ message }) {
	return message ? (
		<Notice status="error" isDismissible={false}>
			{message}
		</Notice>
	) : null;
}

/**
 * The current post's title and plain text.
 *
 * @return {{title: string, text: string}} Post.
 */
function usePostText() {
	return useSelect((select) => {
		const editor = select(editorStore);
		return {
			title: editor.getEditedPostAttribute('title') || '',
			text: toPlainText(editor.getEditedPostContent()),
		};
	}, []);
}

/**
 * Sidebar: meta description.
 */
function MetaDescriptionPanel() {
	const post = usePostText();
	const { editPost } = useDispatch(editorStore);
	const task = useTask();
	const [draft, setDraft] = useState('');

	const generate = async () => {
		const value = await task.run('meta_description', post);
		if (value) {
			setDraft(value);
		}
	};

	return (
		<PanelBody title={__('Meta description', 'aktheme')} initialOpen>
			<p>
				{__(
					'Writes a search-result description (about 155 characters) from this page and saves it as the excerpt.',
					'aktheme'
				)}
			</p>
			<Button
				variant="secondary"
				onClick={generate}
				isBusy={task.busy}
				disabled={task.busy || !post.text}
			>
				{__('Generate', 'aktheme')}
			</Button>
			{task.busy && <Spinner />}
			<ErrorNotice message={task.error} />
			{draft && (
				<>
					<TextareaControl
						__nextHasNoMarginBottom
						label={__('Suggestion (edit freely)', 'aktheme')}
						help={sprintf(
							/* translators: %d: number of characters. */
							_n(
								'%d character',
								'%d characters',
								draft.length,
								'aktheme'
							),
							draft.length
						)}
						value={draft}
						onChange={setDraft}
					/>
					<Button
						variant="primary"
						onClick={() => {
							editPost({ excerpt: draft });
							setDraft('');
						}}
					>
						{__('Use as excerpt', 'aktheme')}
					</Button>
				</>
			)}
		</PanelBody>
	);
}

/**
 * Sidebar: title ideas.
 */
function TitleIdeasPanel() {
	const post = usePostText();
	const { editPost } = useDispatch(editorStore);
	const task = useTask();

	return (
		<PanelBody title={__('Title ideas', 'aktheme')} initialOpen={false}>
			<Button
				variant="secondary"
				onClick={() => task.run('title_ideas', post)}
				isBusy={task.busy}
				disabled={task.busy || !post.text}
			>
				{__('Suggest 5 titles', 'aktheme')}
			</Button>
			{task.busy && <Spinner />}
			<ErrorNotice message={task.error} />
			{Array.isArray(task.result) && (
				<ul className="aktheme-ai-titles">
					{task.result.map((title) => (
						<li key={title}>
							<Button
								variant="link"
								onClick={() => editPost({ title })}
								label={__('Use this title', 'aktheme')}
								showTooltip
							>
								{title}
							</Button>
						</li>
					))}
				</ul>
			)}
		</PanelBody>
	);
}

/**
 * Insert blocks after the selected block, or at the end.
 *
 * @return {(blocks: Object|Object[]) => void} Insert function.
 */
function useInsert() {
	const { insertBlocks } = useDispatch(blockEditorStore);
	const { selected, getIndex, getRoot } = useSelect((select) => {
		const store = select(blockEditorStore);
		return {
			selected: store.getSelectedBlockClientId(),
			getIndex: store.getBlockIndex,
			getRoot: store.getBlockRootClientId,
		};
	}, []);

	return (blocks) => {
		if (selected) {
			insertBlocks(
				blocks,
				getIndex(selected) + 1,
				getRoot(selected) || undefined
			);
		} else {
			insertBlocks(blocks);
		}
	};
}

/**
 * Sidebar: write a section from a brief.
 */
function WriteSectionPanel() {
	const post = usePostText();
	const insert = useInsert();
	const task = useTask();
	const [brief, setBrief] = useState('');

	return (
		<PanelBody title={__('Write a section', 'aktheme')} initialOpen={false}>
			<TextareaControl
				__nextHasNoMarginBottom
				label={__('What should the section say?', 'aktheme')}
				help={__(
					'e.g. "Why choose us: fast delivery, local team, 10 years of experience"',
					'aktheme'
				)}
				value={brief}
				onChange={setBrief}
			/>
			<Button
				variant="secondary"
				onClick={() => task.run('write_section', { ...post, brief })}
				isBusy={task.busy}
				disabled={task.busy || !brief.trim()}
			>
				{__('Write', 'aktheme')}
			</Button>
			{task.busy && <Spinner />}
			<ErrorNotice message={task.error} />
			{'string' === typeof task.result && task.result && (
				<PreviewModal
					title={__('New section', 'aktheme')}
					onClose={() => task.setResult(null)}
					actions={[
						{
							label: __('Insert', 'aktheme'),
							primary: true,
							onClick: () =>
								insert(rawHandler({ HTML: task.result })),
						},
					]}
				>
					<RawHTML>{task.result}</RawHTML>
				</PreviewModal>
			)}
		</PanelBody>
	);
}

/**
 * Sidebar: FAQ.
 */
function FaqPanel() {
	const post = usePostText();
	const insert = useInsert();
	const task = useTask();
	const [count, setCount] = useState(5);

	const blocks = () =>
		specsToBlocks(
			faqBlockSpecs(task.result, !!getBlockType('ak/faq'), {
				eyebrow: __('FAQ', 'aktheme'),
				heading: __('Frequently asked questions', 'aktheme'),
			}),
			createBlock
		);

	return (
		<PanelBody
			title={__('FAQ (with schema)', 'aktheme')}
			initialOpen={false}
		>
			<RangeControl
				__nextHasNoMarginBottom
				__next40pxDefaultSize
				label={__('Questions', 'aktheme')}
				value={count}
				onChange={(value) => setCount(value || 5)}
				min={3}
				max={10}
			/>
			<Button
				variant="secondary"
				onClick={() => task.run('faq', { ...post, count })}
				isBusy={task.busy}
				disabled={task.busy || !post.text}
			>
				{__('Write FAQ', 'aktheme')}
			</Button>
			{task.busy && <Spinner />}
			<ErrorNotice message={task.error} />
			{Array.isArray(task.result) && task.result.length > 0 && (
				<PreviewModal
					title={__('FAQ', 'aktheme')}
					onClose={() => task.setResult(null)}
					actions={[
						{
							label: __('Insert', 'aktheme'),
							primary: true,
							onClick: () => insert(blocks()),
						},
					]}
				>
					<dl>
						{task.result.map((item) => (
							<div key={item.question}>
								<dt>
									<strong>{item.question}</strong>
								</dt>
								<dd>
									<RawHTML>{item.answer}</RawHTML>
								</dd>
							</div>
						))}
					</dl>
				</PreviewModal>
			)}
		</PanelBody>
	);
}

/**
 * Preview modal with action buttons. Each action closes the modal after running.
 *
 * @param {Object}     props          Props.
 * @param {string}     props.title    Title.
 * @param {() => void} props.onClose  Close handler.
 * @param {Array}      props.actions  [{label, onClick, primary}].
 * @param {Object}     props.children Preview content.
 */
function PreviewModal({ title, onClose, actions, children }) {
	return (
		<Modal title={title} onRequestClose={onClose} size="medium">
			<div className="aktheme-ai-preview">{children}</div>
			<div
				style={{
					display: 'flex',
					gap: '8px',
					justifyContent: 'flex-end',
					marginTop: '16px',
				}}
			>
				<Button variant="tertiary" onClick={onClose}>
					{__('Cancel', 'aktheme')}
				</Button>
				{actions.map((action) => (
					<Button
						key={action.label}
						variant={action.primary ? 'primary' : 'secondary'}
						onClick={() => {
							action.onClick();
							onClose();
						}}
					>
						{action.label}
					</Button>
				))}
			</div>
		</Modal>
	);
}

/**
 * The sidebar.
 */
function AssistantSidebar() {
	return (
		<>
			<PluginSidebarMoreMenuItem target={SIDEBAR} icon={icon}>
				{__('AK AI', 'aktheme')}
			</PluginSidebarMoreMenuItem>
			<PluginSidebar
				name={SIDEBAR}
				title={__('AK AI', 'aktheme')}
				icon={icon}
			>
				{!config.hasKey ? (
					<PanelBody>
						<NoKeyNotice />
					</PanelBody>
				) : (
					<>
						<MetaDescriptionPanel />
						<TitleIdeasPanel />
						<WriteSectionPanel />
						<FaqPanel />
						<PanelBody>
							<p>
								{__(
									'Text is sent to Claude (Anthropic) only when you click a button. Always check facts before publishing.',
									'aktheme'
								)}{' '}
								<ExternalLink href="https://www.anthropic.com/legal/privacy">
									{__('Privacy', 'aktheme')}
								</ExternalLink>
							</p>
						</PanelBody>
					</>
				)}
			</PluginSidebar>
		</>
	);
}

registerPlugin('aktheme-ai', { render: AssistantSidebar });

/**
 * Block toolbar: Rewrite for paragraphs and headings.
 *
 * @param {Object}                  props               Block edit props.
 * @param {string}                  props.name          Block name.
 * @param {string}                  props.clientId      Block client ID.
 * @param {Object}                  props.attributes    Block attributes.
 * @param {(attrs: Object) => void} props.setAttributes Attribute setter.
 */
function RewriteControl({ name, clientId, attributes, setAttributes }) {
	const task = useTask();
	const { insertBlocks } = useDispatch(blockEditorStore);
	const index = useSelect(
		(select) => select(blockEditorStore).getBlockIndex(clientId),
		[clientId]
	);
	const root = useSelect(
		(select) => select(blockEditorStore).getBlockRootClientId(clientId),
		[clientId]
	);
	const text = richTextToString(attributes.content);

	const modes = [
		['improve', __('Improve writing', 'aktheme')],
		['shorten', __('Make shorter', 'aktheme')],
		['expand', __('Make longer', 'aktheme')],
		['grammar', __('Fix spelling and grammar', 'aktheme')],
		['seo', __('Make more SEO-friendly', 'aktheme')],
	];

	return (
		<>
			<BlockControls group="other">
				<ToolbarGroup>
					<ToolbarDropdownMenu
						icon={icon}
						label={__('Rewrite with AI', 'aktheme')}
						controls={modes.map(([mode, title]) => ({
							title,
							isDisabled: task.busy || !toPlainText(text),
							onClick: () => task.run('rewrite', { text, mode }),
						}))}
					/>
				</ToolbarGroup>
			</BlockControls>
			{task.busy && (
				<Modal
					title={__('Rewriting…', 'aktheme')}
					onRequestClose={() => {}}
					isDismissible={false}
					size="small"
				>
					<Spinner />
				</Modal>
			)}
			{task.error && (
				<Modal
					title={__('AI rewrite', 'aktheme')}
					onRequestClose={() => task.setError('')}
					size="small"
				>
					<ErrorNotice message={task.error} />
				</Modal>
			)}
			{'string' === typeof task.result && task.result && (
				<PreviewModal
					title={__('Rewritten text', 'aktheme')}
					onClose={() => task.setResult(null)}
					actions={[
						{
							label: __('Insert below', 'aktheme'),
							onClick: () =>
								insertBlocks(
									createBlock(name, {
										...attributes,
										content: task.result,
									}),
									index + 1,
									root || undefined
								),
						},
						{
							label: __('Replace', 'aktheme'),
							primary: true,
							onClick: () =>
								setAttributes({ content: task.result }),
						},
					]}
				>
					<RawHTML>{task.result}</RawHTML>
				</PreviewModal>
			)}
		</>
	);
}

/**
 * Block toolbar: alt text for images.
 *
 * @param {Object}                  props               Block edit props.
 * @param {Object}                  props.attributes    Block attributes.
 * @param {(attrs: Object) => void} props.setAttributes Attribute setter.
 */
function AltTextControl({ attributes, setAttributes }) {
	const task = useTask();
	const [draft, setDraft] = useState('');
	const pageTitle = useSelect(
		(select) => select(editorStore).getEditedPostAttribute('title') || '',
		[]
	);

	const generate = async () => {
		const value = await task.run('alt_text', {
			attachment_id: attributes.id,
			context: [richTextToString(attributes.caption), pageTitle]
				.map(toPlainText)
				.filter(Boolean)
				.join(' | '),
		});
		if (value) {
			setDraft(value);
		}
	};

	return (
		<>
			<BlockControls group="other">
				<ToolbarGroup>
					<ToolbarButton
						icon={icon}
						label={__('Write alt text with AI', 'aktheme')}
						onClick={generate}
						disabled={task.busy || !attributes.id}
						isBusy={task.busy}
					/>
				</ToolbarGroup>
			</BlockControls>
			{task.error && (
				<Modal
					title={__('AI alt text', 'aktheme')}
					onRequestClose={() => task.setError('')}
					size="small"
				>
					<ErrorNotice message={task.error} />
				</Modal>
			)}
			{draft && (
				<PreviewModal
					title={__('Alt text', 'aktheme')}
					onClose={() => setDraft('')}
					actions={[
						{
							label: __('Use', 'aktheme'),
							primary: true,
							onClick: () => setAttributes({ alt: draft }),
						},
					]}
				>
					<TextareaControl
						__nextHasNoMarginBottom
						label={__('Alt text (edit freely)', 'aktheme')}
						value={draft}
						onChange={setDraft}
					/>
				</PreviewModal>
			)}
		</>
	);
}

const withAiControls = createHigherOrderComponent(
	(BlockEdit) => (props) => {
		const { name, isSelected } = props;
		const isText = 'core/paragraph' === name || 'core/heading' === name;
		const isImage = 'core/image' === name;

		return (
			<>
				<BlockEdit {...props} />
				{config.hasKey && isSelected && isText && (
					<RewriteControl {...props} />
				)}
				{config.hasKey && isSelected && isImage && (
					<AltTextControl {...props} />
				)}
			</>
		);
	},
	'withAkthemeAiControls'
);

addFilter('editor.BlockEdit', 'aktheme/ai-controls', withAiControls);
