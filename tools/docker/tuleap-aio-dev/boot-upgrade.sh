#!/usr/bin/env bash

set -ex

# On start, ensure db is consistent with data (useful for version bump)
tuleap-cfg site-deploy:forgeupgrade

# Ensure system will be synchronized ASAP (once system starts)
/usr/bin/tuleap queue-system-check
