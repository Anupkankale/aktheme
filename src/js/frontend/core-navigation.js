/**
 * Custom script required for the core Navigation block.
 *
 * The menu overlay (below 1200px; styles in _header-footer.scss) gets:
 * - a top row with the site logo (a copy of the header's), beside the close button;
 * - the header's Contact Us button under the menu (the overlay hides the header button);
 * - the groups as an accordion (one open at a time; the group with the current page opens with the menu); for a group
 *   with no page of its own (Discover, Resources), tapping its label opens it like its arrow does;
 * - from 640px, where the menu is a panel over the dimmed page, a tap on the dimmed page closes it.
 * Opening and closing the menu are core's (the Interactivity API); the accordion state is the theme's.
 */

/**
 * Internal dependencies
 */
import '../../css/frontend/core-navigation.scss';

const CONTAINER = '.wp-block-navigation__responsive-container';

/**
 * A copy of an element for the overlay, without ids (they must stay unique on the page).
 *
 * @param {Element} element Element to copy.
 * @return {Element} Copy.
 */
const copy = (element) => {
	const clone = element.cloneNode(true);
	clone.removeAttribute('id');
	clone.querySelectorAll('[id]').forEach((el) => el.removeAttribute('id'));
	return clone;
};

document.querySelectorAll('nav.ak-nav').forEach((nav) => {
	const container = nav.querySelector(CONTAINER);
	const dialog = nav.querySelector('.wp-block-navigation__responsive-dialog');
	if (!container || !dialog) {
		return;
	}

	const header = nav.closest('.ak-pill') || document;

	// Top row with the logo.
	const logo = header.querySelector('.wp-block-site-logo');
	if (logo && !dialog.querySelector('.ak-nav-head')) {
		const head = document.createElement('div');
		head.className = 'ak-nav-head';
		const logoCopy = copy(logo);
		logoCopy.querySelector('img')?.classList.add('ak-nav-logo');
		head.append(logoCopy);
		dialog.prepend(head);
	}

	// Contact Us under the menu.
	const cta = header.querySelector('.ak-pill-cta .wp-block-button__link');
	if (cta && !dialog.querySelector('.ak-nav-foot')) {
		const foot = document.createElement('div');
		foot.className = 'ak-nav-foot';
		const ctaCopy = copy(cta);
		ctaCopy.classList.add('ak-nav-cta');
		foot.append(ctaCopy);
		dialog.append(foot);
	}

	// The groups as an accordion. In the overlay core reports every group as open and its arrows do not close them,
	// so the theme keeps the state: a tap on a group's arrow, or on the label of a group with no page of its own
	// (Discover, Resources), opens it and closes the others. When the menu opens, the group with the current page
	// starts open. The styles only fold groups once the container has .ak-accordion (so without this script every
	// link shows).
	const groups = [
		...container.querySelectorAll(
			'.wp-block-navigation__responsive-container-content .wp-block-navigation__container > .has-child'
		),
	];
	const setOpen = (group, open) => {
		group.classList.toggle('ak-open', open);
		group
			.querySelector(':scope > .wp-block-navigation-submenu__toggle')
			?.setAttribute('aria-expanded', open ? 'true' : 'false');
	};

	if (groups.length) {
		container.classList.add('ak-accordion');

		// Capture phase: the theme answers these taps before core's own handler sees them.
		container.addEventListener(
			'click',
			(event) => {
				if (!container.classList.contains('is-menu-open')) {
					return;
				}
				const hit = event.target.closest(
					'.wp-block-navigation-submenu__toggle, a.wp-block-navigation-item__content:not([href])'
				);
				const group = hit?.parentElement;
				if (!hit || !groups.includes(group)) {
					return;
				}
				event.preventDefault();
				event.stopPropagation();
				const open = !group.classList.contains('ak-open');
				groups.forEach((g) => setOpen(g, g === group && open));
			},
			true
		);

		// Each time the menu opens (after core has set its own attributes).
		new window.MutationObserver(() => {
			if (container.classList.contains('is-menu-open')) {
				window.requestAnimationFrame(() =>
					groups.forEach((g) =>
						setOpen(
							g,
							g.classList.contains('current-menu-ancestor')
						)
					)
				);
			}
		}).observe(container, { attributes: true, attributeFilter: ['class'] });
	}

	// A tap on the dimmed page beside the panel closes the menu.
	container.addEventListener('click', (event) => {
		if (
			event.target === container &&
			container.classList.contains('is-menu-open')
		) {
			container
				.querySelector(
					'.wp-block-navigation__responsive-container-close'
				)
				?.click();
		}
	});
});
