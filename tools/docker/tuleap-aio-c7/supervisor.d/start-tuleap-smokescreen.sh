#!/usr/bin/env bash

set -euo pipefail

CONF_FILE="$(mktemp)"
trap 'rm -f -- "$CONF_FILE"' EXIT

tuleap smokescreen-configuration-dump > "$CONF_FILE"
chmod o+r "$CONF_FILE"
exec sudo -u tuleap-smokescreen /usr/bin/tuleap-smokescreen --config-file="$CONF_FILE"
