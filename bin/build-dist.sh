#!/usr/bin/env bash
# bin/build-dist.sh — Build distributable tgz for harper.agency
#
# Usage:
#   ./bin/build-dist.sh              # uses version from composer.json
#   ./bin/build-dist.sh 1.1.0        # override version
#
# Output: dist/harper-openmage-order-customer-tagger-{version}.tgz
#
# The archive extracts directly into the OpenMage/Magento 1 webroot:
#   tar -xzf harper-openmage-order-customer-tagger-1.0.0.tgz -C /path/to/openmage/
#
# Files land at:
#   app/code/community/HarperAgency/OrderCustomerTagger/
#   app/design/adminhtml/default/default/layout/harper_tagger.xml
#   app/design/adminhtml/default/default/template/harper_tagger/
#   app/etc/modules/HarperAgency_OrderCustomerTagger.xml

set -euo pipefail

REPO_ROOT="$(cd "$(dirname "$0")/.." && pwd)"

# ── Resolve version ────────────────────────────────────────────────────────────
if [[ -n "${1:-}" ]]; then
    VERSION="$1"
else
    VERSION=$(grep -m1 '"version"' "$REPO_ROOT/composer.json" | sed 's/.*"version": *"//;s/".*//')
fi

if [[ -z "$VERSION" ]]; then
    echo "ERROR: could not determine version. Pass it as an argument: $0 1.1.0" >&2
    exit 1
fi

ARCHIVE_NAME="harper-openmage-order-customer-tagger-${VERSION}.tgz"
DIST_DIR="$REPO_ROOT/dist"
BUILD_DIR="$(mktemp -d)"
trap 'rm -rf "$BUILD_DIR"' EXIT

echo "Building OpenMage tagger v${VERSION} → dist/${ARCHIVE_NAME}"

# ── Copy only the webroot-destined files ──────────────────────────────────────
# Module code
install -d "$BUILD_DIR/app/code/community/HarperAgency"
cp -r "$REPO_ROOT/app/code/community/HarperAgency/OrderCustomerTagger" \
      "$BUILD_DIR/app/code/community/HarperAgency/"

# Module XML declaration
install -d "$BUILD_DIR/app/etc/modules"
cp "$REPO_ROOT/app/etc/modules/HarperAgency_OrderCustomerTagger.xml" \
   "$BUILD_DIR/app/etc/modules/"

# Adminhtml layout
install -d "$BUILD_DIR/app/design/adminhtml/default/default/layout"
cp "$REPO_ROOT/app/design/adminhtml/default/default/layout/harper_tagger.xml" \
   "$BUILD_DIR/app/design/adminhtml/default/default/layout/"

# Adminhtml templates
install -d "$BUILD_DIR/app/design/adminhtml/default/default/template/harper_tagger"
cp "$REPO_ROOT/app/design/adminhtml/default/default/template/harper_tagger/"*.phtml \
   "$BUILD_DIR/app/design/adminhtml/default/default/template/harper_tagger/" 2>/dev/null || true

# ── Package ────────────────────────────────────────────────────────────────────
mkdir -p "$DIST_DIR"
tar -czf "$DIST_DIR/$ARCHIVE_NAME" -C "$BUILD_DIR" .

echo "Done: $DIST_DIR/$ARCHIVE_NAME ($(du -sh "$DIST_DIR/$ARCHIVE_NAME" | cut -f1))"

# ── Verify contents ────────────────────────────────────────────────────────────
echo ""
echo "Archive contents:"
tar -tzf "$DIST_DIR/$ARCHIVE_NAME" | grep -v '/$' | sort
