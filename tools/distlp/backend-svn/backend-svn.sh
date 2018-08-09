#!/usr/bin/env bash

set -ex

while [ ! -f "/data/etc/tuleap/conf/local.inc" ]; do
    echo "Data mount point no ready yet";
    sleep 1
done

/opt/remi/php56/root/bin/php /usr/share/tuleap/tools/distlp/backend-svn/run.php

exec supervisord -n
