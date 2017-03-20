#!/usr/bin/env bash

set -ex

if [ ! -d "/data/etc/tuleap" ]; then
    echo "Missing data mount point";
    exit 1;
fi

/opt/rh/rh-php56/root/bin/php /usr/share/tuleap/tools/distlp/backend-svn/run.php

exec supervisord -n
