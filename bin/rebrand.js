#! /usr/bin/env node

/* eslint no-console: 0 */

/**
 * Rebrand this framework for a client: theme name, slug/text domain, PHP namespace and PHP prefixes.
 *
 * Usage:
 *   node bin/rebrand.js --name="Acme Studio" [--slug=acme-studio] [--short=Acme] [--dry-run]
 *
 * What changes:
 *   "AK Theme"   -> --name               (style.css Theme Name, docs)
 *   aktheme      -> --slug               (text domain, pattern slugs, package names)
 *   AK\Theme     -> <Namespace>\Theme    (PHP namespace, from --name in PascalCase)
 *   aktheme_     -> <slug_>              (PHP functions, options, shortcodes, meta keys)
 *   AKTHEME_     -> <SLUG_>              (PHP constants and the PHPCS prefix list)
 *   AK (word)    -> --short              (block titles, inserter category label, comments)
 *
 * What stays: the block names (`ak/*`) and CSS classes (`ak-*`). Visitors never see them, and renaming them would
 * break content saved with the blocks. Run it once, on a fresh copy, before any content is created.
 */

const fs = require('fs');
const path = require('path');

const args = Object.fromEntries(
	process.argv.slice(2).map((arg) => {
		const [key, ...rest] = arg.replace(/^--/, '').split('=');
		return [key, rest.length ? rest.join('=') : true];
	})
);

if (!args.name || args.help) {
	console.log(
		'Usage: node bin/rebrand.js --name="Client Name" [--slug=client-name] [--short=Client] [--dry-run]'
	);
	process.exit(args.help ? 0 : 1);
}

const name = String(args.name).trim();
const slug = String(
	args.slug ||
		name
			.toLowerCase()
			.replace(/[^a-z0-9]+/g, '-')
			.replace(/^-|-$/g, '')
);

if (!/^[a-z][a-z0-9-]{2,}$/.test(slug)) {
	console.error(
		`Slug "${slug}" must start with a letter and be at least 3 characters (a-z, 0-9, -).`
	);
	process.exit(1);
}

const snake = slug.replace(/-/g, '_');
const constant = snake.toUpperCase();
const namespace = name
	.replace(/[^A-Za-z0-9 ]+/g, ' ')
	.split(/\s+/)
	.filter(Boolean)
	.map((word) => word[0].toUpperCase() + word.slice(1))
	.join('')
	.replace(/^[0-9]+/, '');
const short = String(args.short || name.split(/\s+/)[0]);

/* Order matters: the specific tokens first. */
const replacements = [
	[/AK\\\\Theme/g, `${namespace}\\\\Theme`],
	[/AK\\Theme/g, `${namespace}\\Theme`],
	[/AK Theme/g, name],
	[/(?<![A-Za-z0-9])AKTHEME(?![A-Za-z0-9])/g, constant],
	[/(?<![A-Za-z0-9])aktheme_/g, `${snake}_`],
	[/<element value="aktheme"\/>/g, `<element value="${snake}"/>`],
	[/(?<![A-Za-z0-9_-])aktheme(?![A-Za-z0-9_])/g, slug],
	[/(?<![A-Za-z0-9_\\-])AK(?![A-Za-z0-9_\\])/g, short],
];

const root = path.resolve(__dirname, '..');
const skipDirs = new Set(['node_modules', 'vendor', '.git', 'build']);
const textExt = new Set([
	'.php',
	'.js',
	'.mjs',
	'.json',
	'.html',
	'.scss',
	'.css',
	'.md',
	'.xml',
	'.dist',
	'.neon',
	'.yml',
	'.yaml',
	'.txt',
	'.example',
]);
const self = path.resolve(__filename);
let changed = 0;

const walk = (dir) => {
	for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
		const full = path.join(dir, entry.name);

		if (entry.isDirectory()) {
			if (!skipDirs.has(entry.name)) {
				walk(full);
			}
			continue;
		}

		if (
			full === self ||
			!textExt.has(path.extname(entry.name)) ||
			entry.name === 'composer.lock'
		) {
			continue;
		}

		const before = fs.readFileSync(full, 'utf8');
		// The lock file only carries the package name; the rest is third-party.
		const after =
			entry.name === 'package-lock.json'
				? before.replace(/"name": "aktheme"/g, `"name": "${slug}"`)
				: replacements.reduce(
						(text, [from, to]) => text.replace(from, to),
						before
					);

		if (after !== before) {
			changed++;
			if (!args['dry-run']) {
				fs.writeFileSync(full, after);
			}
			console.log(
				`${args['dry-run'] ? 'would update' : 'updated'} ${path.relative(root, full)}`
			);
		}
	}
};

walk(root);

console.log(
	`\n${changed} file(s) ${args['dry-run'] ? 'would change' : 'changed'}: "${name}", slug ${slug}, namespace ${namespace}\\Theme, prefixes ${snake}_ / ${constant}_.`
);

if (!args['dry-run']) {
	console.log(
		`Next: rename the folder to "${slug}", then run \`composer dump-autoload\`, \`npm run lint:php:fix\` (re-aligns arrays) and \`npm run build:prod\`.`
	);
}
