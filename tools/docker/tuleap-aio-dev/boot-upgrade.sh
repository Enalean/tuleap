#!/bin/bash

set -ex

if [ "$DO_NOT_LAUNCH_FORGEUPGRADE" == true ] ; then
    echo "Database may be inconsistent. You should run a forgeupgrade update."
else
    # On start, ensure db is consistent with data (useful for version bump)
    tuleap-cfg site-deploy:forgeupgrade
fi

# Ensure system will be synchronized ASAP (once system starts)
/usr/bin/tuleap queue-system-check
