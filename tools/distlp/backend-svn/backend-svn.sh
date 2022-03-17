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

while [ ! -f "/data/etc/tuleap/svn_plugin_installed" ]; do
    echo "Waiting for SVN plugin to be installed"
    sleep 1
done

export TULEAP_FPM_SESSION_MODE=redis
export REDIS_SERVER=redis

mv /etc/tuleap /etc/tuleap.old || true
ln -s /data/etc/tuleap /etc/tuleap

/bin/rm -r /var/lib/tuleap/svn_plugin || true
ln -s /data/lib/tuleap/svn_plugin /var/lib/tuleap/svn_plugin

/usr/share/tuleap/src/utils/tuleap wait-for-redis

/usr/share/tuleap/src/tuleap-cfg/tuleap-cfg.php site-deploy

systemctl enable tuleap \
    httpd \
    nginx \
    tuleap-svn-updater \
    tuleap-process-system-events-default.timer \
    tuleap-launch-system-check.timer

echo '$sys_nb_backend_workers = 1;' >> /data/etc/tuleap/conf/local.inc

exec /usr/sbin/init
