#!/usr/bin/env bash

set -ex

while [ ! -f "/data/etc/tuleap/conf/local.inc" ]; do
    echo "Data mount point no ready yet";
    sleep 1
done

while [ ! -f "/data/etc/tuleap/conf/redis.inc" ]; do
    echo "Waiting for redis conf to be written"
    sleep 1
done

ln -s /data/etc/tuleap /etc/tuleap

/usr/share/tuleap/src/utils/tuleap wait-for-redis

/usr/share/tuleap/src/utils/tuleap config-set init_mode supervisord

/opt/remi/php73/root/bin/php /usr/share/tuleap/tools/distlp/backend-svn/run.php

exec supervisord -n
