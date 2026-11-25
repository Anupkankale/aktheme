<?php
// This file is generated. Do not modify it manually.
return array(
	'achievements' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'ak/achievements',
		'title' => 'AK Achievements',
		'category' => 'ak',
		'icon' => 'chart-bar',
		'description' => 'Navy card with animated counters.',
		'keywords' => array(
			'ak',
			'section'
		),
		'version' => '1.0.0',
		'textdomain' => 'aktheme',
		'supports' => array(
			'anchor' => true,
			'html' => false
		),
		'ak' => array(
			'tagName' => 'section',
			'className' => 'achieve',
			'anchor' => 'presence'
		),
		'editorScript' => 'file:./index.js',
		'style' => array(
			'ak-base',
			'file:./style-index.css'
		),
		'viewScript' => 'file:./view.js'
	),
	'analytics' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'ak/analytics',
		'title' => 'AK Image + Bullet List',
		'category' => 'ak',
		'icon' => 'analytics',
		'description' => 'Image beside an Insights header, dotted list and button.',
		'keywords' => array(
			'ak',
			'section'
		),
		'version' => '1.0.0',
		'textdomain' => 'aktheme',
		'supports' => array(
			'anchor' => true,
			'html' => false,
			'align' => array(
				'full'
			)
		),
		'attributes' => array(
			'align' => array(
				'type' => 'string',
				'default' => 'full'
			)
		),
		'ak' => array(
			'tagName' => 'section',
			'className' => 'section analytics',
			'anchor' => ''
		),
		'editorScript' => 'file:./index.js',
		'style' => array(
			'ak-base',
			'file:./style-index.css'
		)
	),
	'float-contact' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'ak/float-contact',
		'title' => 'AK Floating Contact',
		'category' => 'ak',
		'icon' => 'whatsapp',
		'description' => 'Fixed WhatsApp contact link (bottom left).',
		'keywords' => array(
			'ak',
			'section'
		),
		'version' => '1.0.0',
		'textdomain' => 'aktheme',
		'supports' => array(
			'anchor' => true,
			'html' => false,
			'align' => array(
				'full'
			)
		),
		'attributes' => array(
			'align' => array(
				'type' => 'string',
				'default' => 'full'
			)
		),
		'ak' => array(
			'tagName' => 'div',
			'className' => 'float-contact',
			'anchor' => ''
		),
		'editorScript' => 'file:./index.js',
		'style' => array(
			'ak-base',
			'file:./style-index.css'
		)
	),
	'process' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'ak/process',
		'title' => 'AK Process Timeline',
		'category' => 'ak',
		'icon' => 'editor-ol',
		'description' => 'Header, then steps on a horizontal timeline (title, dot, text) and a button. Vertical on phones.',
		'keywords' => array(
			'ak',
			'section'
		),
		'version' => '1.0.0',
		'textdomain' => 'aktheme',
		'supports' => array(
			'anchor' => true,
			'html' => false,
			'align' => array(
				'full'
			)
		),
		'attributes' => array(
			'align' => array(
				'type' => 'string',
				'default' => 'full'
			)
		),
		'ak' => array(
			'tagName' => 'section',
			'className' => 'section process',
			'anchor' => 'admission'
		),
		'editorScript' => 'file:./index.js',
		'style' => array(
			'ak-base',
			'file:./style-index.css'
		)
	),
	'subnav' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'ak/subnav',
		'title' => 'AK Section Navigation',
		'category' => 'ak',
		'icon' => 'menu',
		'description' => 'Sticky bar with links to sections on the page (highlights the section in view).',
		'keywords' => array(
			'ak',
			'section'
		),
		'version' => '1.0.0',
		'textdomain' => 'aktheme',
		'supports' => array(
			'anchor' => true,
			'html' => false,
			'align' => array(
				'full'
			)
		),
		'attributes' => array(
			'align' => array(
				'type' => 'string',
				'default' => 'full'
			)
		),
		'ak' => array(
			'tagName' => 'nav',
			'className' => 'subnav',
			'anchor' => 'subnav'
		),
		'editorScript' => 'file:./index.js',
		'style' => array(
			'ak-base',
			'file:./style-index.css'
		),
		'viewScript' => 'file:./view.js'
	),
	'usp-bento' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'ak/usp-bento',
		'title' => 'AK USP Bento Grid',
		'category' => 'ak',
		'icon' => 'grid-view',
		'description' => 'Six feature cards in a two-column grid and a button.',
		'keywords' => array(
			'ak',
			'section'
		),
		'version' => '1.0.0',
		'textdomain' => 'aktheme',
		'supports' => array(
			'anchor' => true,
			'html' => false,
			'align' => array(
				'full'
			)
		),
		'attributes' => array(
			'align' => array(
				'type' => 'string',
				'default' => 'full'
			)
		),
		'ak' => array(
			'tagName' => 'section',
			'className' => 'section usp',
			'anchor' => 'usp'
		),
		'editorScript' => 'file:./index.js',
		'style' => array(
			'ak-base',
			'file:./style-index.css'
		)
	),
	'who-can-join' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'ak/who-can-join',
		'title' => 'AK Who Can Join',
		'category' => 'ak',
		'icon' => 'groups',
		'description' => 'Accordion (one open at a time, smooth animation) beside a photo.',
		'keywords' => array(
			'ak',
			'section'
		),
		'version' => '1.0.0',
		'textdomain' => 'aktheme',
		'supports' => array(
			'anchor' => true,
			'html' => false,
			'align' => array(
				'full'
			)
		),
		'attributes' => array(
			'align' => array(
				'type' => 'string',
				'default' => 'full'
			)
		),
		'ak' => array(
			'tagName' => 'section',
			'className' => 'section join',
			'anchor' => 'who-can-join'
		),
		'editorScript' => 'file:./index.js',
		'style' => array(
			'ak-base',
			'file:./style-index.css'
		),
		'viewScript' => 'file:./view.js',
		'styles' => array(
			array(
				'name' => 'default',
				'label' => 'Lines',
				'isDefault' => true
			),
			array(
				'name' => 'boxed',
				'label' => 'Boxed'
			)
		)
	),
	'alumni-video' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'ak/alumni-video',
		'title' => 'AK Alumni Videos',
		'category' => 'ak',
		'icon' => 'video-alt3',
		'description' => 'Video gallery: main player and three thumbnails. Click a video image → Link → paste its YouTube URL; it plays in place.',
		'keywords' => array(
			'ak',
			'section',
			'video',
			'youtube',
			'testimonials'
		),
		'version' => '1.0.0',
		'textdomain' => 'aktheme',
		'supports' => array(
			'anchor' => true,
			'html' => false,
			'align' => array(
				'full'
			)
		),
		'attributes' => array(
			'align' => array(
				'type' => 'string',
				'default' => 'full'
			)
		),
		'ak' => array(
			'tagName' => 'section',
			'className' => 'section alumni',
			'anchor' => ''
		),
		'editorScript' => 'file:./index.js',
		'style' => array(
			'ak-base',
			'file:./style-index.css'
		),
		'viewScript' => 'file:./view.js',
		'styles' => array(
			array(
				'name' => 'default',
				'label' => 'Default',
				'isDefault' => true
			),
			array(
				'name' => 'home',
				'label' => 'Home'
			)
		)
	),
	'cta' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'ak/cta',
		'title' => 'AK Call to Action',
		'category' => 'ak',
		'icon' => 'button',
		'description' => 'Rounded deep-blue CTA card with heading, text, button and photo.',
		'keywords' => array(
			'ak',
			'section'
		),
		'version' => '1.0.0',
		'textdomain' => 'aktheme',
		'supports' => array(
			'anchor' => true,
			'html' => false,
			'align' => array(
				'full'
			)
		),
		'attributes' => array(
			'align' => array(
				'type' => 'string',
				'default' => 'full'
			)
		),
		'ak' => array(
			'tagName' => 'section',
			'className' => 'cta-wrap',
			'anchor' => ''
		),
		'editorScript' => 'file:./index.js',
		'style' => array(
			'ak-base',
			'file:./style-index.css'
		),
		'styles' => array(
			array(
				'name' => 'default',
				'label' => 'Default',
				'isDefault' => true
			),
			array(
				'name' => 'compact',
				'label' => 'Compact'
			),
			array(
				'name' => 'home',
				'label' => 'Home'
			)
		)
	),
	'faq' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'ak/faq',
		'title' => 'AK FAQ',
		'category' => 'ak',
		'icon' => 'editor-help',
		'description' => 'Accordion of Details blocks (first 5 shown, See More for the rest); outputs FAQPage structured data.',
		'keywords' => array(
			'ak',
			'section'
		),
		'version' => '1.0.0',
		'textdomain' => 'aktheme',
		'supports' => array(
			'anchor' => true,
			'html' => false,
			'align' => array(
				'full'
			)
		),
		'attributes' => array(
			'align' => array(
				'type' => 'string',
				'default' => 'full'
			)
		),
		'ak' => array(
			'tagName' => 'section',
			'className' => 'section faq',
			'anchor' => 'faq'
		),
		'editorScript' => 'file:./index.js',
		'style' => array(
			'ak-base',
			'file:./style-index.css'
		),
		'viewScript' => 'file:./view.js',
		'styles' => array(
			array(
				'name' => 'default',
				'label' => 'Default',
				'isDefault' => true
			),
			array(
				'name' => 'compact',
				'label' => 'Compact'
			),
			array(
				'name' => 'home',
				'label' => 'Home'
			)
		)
	),
	'testimonials' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'ak/testimonials',
		'title' => 'AK Testimonials',
		'category' => 'ak',
		'icon' => 'format-quote',
		'description' => 'Header, quote cards with a LinkedIn link, and a View More link. Duplicate a card to add one; set each LinkedIn link to the person\'s profile.',
		'keywords' => array(
			'ak',
			'section',
			'testimonials',
			'reviews',
			'clients'
		),
		'version' => '1.0.0',
		'textdomain' => 'aktheme',
		'supports' => array(
			'anchor' => true,
			'html' => false,
			'align' => array(
				'full'
			)
		),
		'attributes' => array(
			'align' => array(
				'type' => 'string',
				'default' => 'full'
			)
		),
		'ak' => array(
			'tagName' => 'section',
			'className' => 'section testimonials',
			'anchor' => ''
		),
		'editorScript' => 'file:./index.js',
		'style' => array(
			'ak-base',
			'file:./style-index.css'
		),
		'viewScript' => 'file:./view.js',
		'styles' => array(
			array(
				'name' => 'default',
				'label' => 'Default',
				'isDefault' => true
			),
			array(
				'name' => 'compact',
				'label' => 'Compact'
			),
			array(
				'name' => 'home',
				'label' => 'Home'
			)
		)
	),
	'cost-banner' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'ak/cost-banner',
		'title' => 'AK Cost Banner',
		'category' => 'ak',
		'icon' => 'money-alt',
		'description' => 'Rounded fee card: price, scholarship ribbon and eligibility button.',
		'keywords' => array(
			'ak',
			'section'
		),
		'version' => '1.0.0',
		'textdomain' => 'aktheme',
		'supports' => array(
			'anchor' => true,
			'html' => false
		),
		'ak' => array(
			'tagName' => 'section',
			'className' => 'cost-banner',
			'anchor' => ''
		),
		'editorScript' => 'file:./index.js',
		'style' => array(
			'ak-base',
			'file:./style-index.css'
		),
		'styles' => array(
			array(
				'name' => 'default',
				'label' => 'Default',
				'isDefault' => true
			),
			array(
				'name' => 'home',
				'label' => 'Home'
			)
		)
	),
	'hero' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'ak/hero',
		'title' => 'AK Hero',
		'category' => 'ak',
		'icon' => 'cover-image',
		'description' => 'Headline, stats, button, enquiry form card and accreditation logos (a marquee on phones).',
		'keywords' => array(
			'ak',
			'section'
		),
		'version' => '1.0.0',
		'textdomain' => 'aktheme',
		'supports' => array(
			'anchor' => true,
			'html' => false,
			'align' => array(
				'full'
			)
		),
		'attributes' => array(
			'align' => array(
				'type' => 'string',
				'default' => 'full'
			)
		),
		'ak' => array(
			'tagName' => 'section',
			'className' => 'hero',
			'anchor' => 'top'
		),
		'editorScript' => 'file:./index.js',
		'style' => array(
			'ak-base',
			'file:./style-index.css'
		),
		'viewScript' => 'file:./view.js',
		'styles' => array(
			array(
				'name' => 'default',
				'label' => 'Light',
				'isDefault' => true
			),
			array(
				'name' => 'dark',
				'label' => 'Dark'
			),
			array(
				'name' => 'home',
				'label' => 'Home'
			)
		)
	),
	'highlights' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'ak/highlights',
		'title' => 'AK Highlights',
		'category' => 'ak',
		'icon' => 'yes-alt',
		'description' => 'Overview header, icon list (bold label in gold), button and an illustration.',
		'keywords' => array(
			'ak',
			'section'
		),
		'version' => '1.0.0',
		'textdomain' => 'aktheme',
		'supports' => array(
			'anchor' => true,
			'html' => false,
			'align' => array(
				'full'
			)
		),
		'attributes' => array(
			'align' => array(
				'type' => 'string',
				'default' => 'full'
			)
		),
		'ak' => array(
			'tagName' => 'section',
			'className' => 'highlights',
			'anchor' => 'overview'
		),
		'editorScript' => 'file:./index.js',
		'style' => array(
			'ak-base',
			'file:./style-index.css'
		),
		'styles' => array(
			array(
				'name' => 'default',
				'label' => 'Default',
				'isDefault' => true
			),
			array(
				'name' => 'wide',
				'label' => 'Wide'
			),
			array(
				'name' => 'home',
				'label' => 'Home'
			)
		)
	),
	'section' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'ak/section',
		'title' => 'AK Section (blank)',
		'category' => 'ak',
		'icon' => 'align-wide',
		'description' => 'Empty section with the AK design (fonts, buttons, utilities). Build any new section inside it; add u-bg-navy / u-bg-soft / u-bg-card for a background.',
		'keywords' => array(
			'ak',
			'section',
			'blank',
			'container'
		),
		'version' => '1.0.0',
		'textdomain' => 'aktheme',
		'supports' => array(
			'anchor' => true,
			'html' => false,
			'align' => array(
				'full'
			)
		),
		'attributes' => array(
			'align' => array(
				'type' => 'string',
				'default' => 'full'
			)
		),
		'ak' => array(
			'tagName' => 'section',
			'className' => 'section ak-blank',
			'anchor' => ''
		),
		'editorScript' => 'file:./index.js',
		'viewScript' => 'file:./view.js',
		'style' => array(
			'ak-base',
			'file:./style-index.css'
		),
		'styles' => array(
			array(
				'name' => 'default',
				'label' => 'Default',
				'isDefault' => true
			),
			array(
				'name' => 'home',
				'label' => 'Home'
			)
		)
	),
	'specializations' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'ak/specializations',
		'title' => 'AK Specializations',
		'category' => 'ak',
		'icon' => 'tag',
		'description' => 'Our Programs: linked program cards, 4 per view, continuous marquee scroll.',
		'keywords' => array(
			'ak',
			'section'
		),
		'version' => '1.0.0',
		'textdomain' => 'aktheme',
		'supports' => array(
			'anchor' => true,
			'html' => false,
			'align' => array(
				'full'
			)
		),
		'attributes' => array(
			'align' => array(
				'type' => 'string',
				'default' => 'full'
			)
		),
		'ak' => array(
			'tagName' => 'section',
			'className' => 'section specs',
			'anchor' => 'specializations'
		),
		'editorScript' => 'file:./index.js',
		'style' => array(
			'ak-base',
			'file:./style-index.css'
		),
		'viewScript' => 'file:./view.js',
		'styles' => array(
			array(
				'name' => 'default',
				'label' => 'Four per view (scrolling)',
				'isDefault' => true
			),
			array(
				'name' => 'five',
				'label' => 'Five per row'
			),
			array(
				'name' => 'home',
				'label' => 'Home'
			)
		)
	),
	'step-cards' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'ak/step-cards',
		'title' => 'AK Step Cards',
		'category' => 'ak',
		'icon' => 'grid-view',
		'description' => 'Header, then numbered step cards in two columns (navy and light, alternating), each with a gold tag, title, text and photo.',
		'keywords' => array(
			'ak',
			'section'
		),
		'version' => '1.0.0',
		'textdomain' => 'aktheme',
		'supports' => array(
			'anchor' => true,
			'html' => false,
			'align' => array(
				'full'
			)
		),
		'attributes' => array(
			'align' => array(
				'type' => 'string',
				'default' => 'full'
			)
		),
		'ak' => array(
			'tagName' => 'section',
			'className' => 'section step-cards',
			'anchor' => 'research-steps'
		),
		'editorScript' => 'file:./index.js',
		'style' => array(
			'ak-base',
			'file:./style-index.css'
		),
		'styles' => array(
			array(
				'name' => 'default',
				'label' => 'Default',
				'isDefault' => true
			),
			array(
				'name' => 'home',
				'label' => 'Home'
			)
		),
		'viewScript' => 'file:./view.js'
	),
	'tabs' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'ak/tabs',
		'title' => 'AK Tabs',
		'category' => 'ak',
		'icon' => 'index-card',
		'description' => 'Header, a row of tab labels and one panel per label (e.g. Skills / Who should do / Careers / Tools). The first tab is open.',
		'keywords' => array(
			'ak',
			'section',
			'tabs'
		),
		'version' => '1.0.0',
		'textdomain' => 'aktheme',
		'supports' => array(
			'anchor' => true,
			'html' => false,
			'align' => array(
				'full'
			)
		),
		'attributes' => array(
			'align' => array(
				'type' => 'string',
				'default' => 'full'
			)
		),
		'ak' => array(
			'tagName' => 'section',
			'className' => 'section tabs',
			'anchor' => 'learn-more'
		),
		'editorScript' => 'file:./index.js',
		'style' => array(
			'ak-base',
			'file:./style-index.css'
		),
		'viewScript' => 'file:./view.js',
		'styles' => array(
			array(
				'name' => 'default',
				'label' => 'Default',
				'isDefault' => true
			),
			array(
				'name' => 'home',
				'label' => 'Home'
			)
		)
	)
);
