#!/usr/bin/env bash
set -e

VERSION=$(jq -r '.version' package.json)

sed -i "s/^Stable tag:.*/Stable tag: $VERSION/" plugins/zero-ad-network/readme.txt
sed -i "s/^[[:space:]]*\\*[[:space:]]*Version:.*/ * Version:           $VERSION/" plugins/zero-ad-network/zero-ad-network.php
sed -i "s/define(\"ZERO_AD_NETWORK_PLUGIN_VERSION\", \".*\");/define(\"ZERO_AD_NETWORK_PLUGIN_VERSION\", \"$VERSION\");/" plugins/zero-ad-network/zero-ad-network.php