#!/usr/bin/env bash

set -ex

while [ ! -f "/data/etc/tuleap/conf/local.inc" ]; do
    echo "Data mount point no ready yet";
    sleep 1
done

/opt/remi/php73/root/bin/php /tuleap/tools/distlp/reverse-proxy/run.php

exec /sbin/nginx -g "daemon off;"
