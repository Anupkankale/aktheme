#!/usr/bin/env bash
#
# Build the installable theme: dist/<slug>/ (the files WordPress needs) and dist/<slug>-<version>.zip
# (upload it in Appearance > Themes > Add New > Upload Theme).
#
# Usage: npm run package     (or: bash bin/package.sh [--skip-build])

set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

SLUG="$(basename "$ROOT")"
SLUG="$(node -p "require('./package.json').name" 2>/dev/null || echo "$SLUG")"
VERSION="$(sed -n 's/^ \* Version:[[:space:]]*//p' style.css | head -1)"
DIST="$ROOT/dist"
OUT="$DIST/$SLUG"
ZIP="$DIST/$SLUG-$VERSION.zip"

if [[ "${1:-}" != "--skip-build" ]]; then
	echo "Building assets..."
	npm run build:prod >/dev/null
fi

if [[ ! -f assets/build/blocks/blocks-manifest.php ]]; then
	echo "assets/build is missing: run npm run build:prod first." >&2
	exit 1
fi

rm -rf "$DIST"
mkdir -p "$OUT"

echo "Copying theme files..."
rsync -a --exclude-from=.distignore --exclude=/vendor ./ "$OUT/"

echo "Installing runtime PHP packages (no dev)..."
cp composer.json composer.lock "$OUT/"
composer install --working-dir="$OUT" --no-dev --optimize-autoloader --no-interaction --no-progress --quiet
rm "$OUT/composer.json" "$OUT/composer.lock"

echo "Zipping..."
(cd "$DIST" && zip -rqX "$ZIP" "$SLUG")

echo "Done: $ZIP ($(du -h "$ZIP" | cut -f1)), version $VERSION"
