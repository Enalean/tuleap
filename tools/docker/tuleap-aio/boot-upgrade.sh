#!/bin/bash

set -e

# On start, ensure db is consistent with data (useful for version bump)
/usr/lib/forgeupgrade/bin/forgeupgrade --config=/etc/codendi/forgeupgrade/config.ini update

# Ensure system will be synchronized ASAP (once system starts)
/usr/bin/tuleap queue-system-check

# Switch to php 7.3 + nginx
if [ ! -f "/etc/nginx/conf.d/tuleap.conf" ]; then
    /usr/share/tuleap/tools/utils/php73/run.php

    mv /etc/nginx /data/etc
    ln -s /data/etc/nginx /etc/nginx

    mkdir -p /data/etc/opt/remi/php73/
    mv /etc/opt/remi/php73/php-fpm.d/ /data/etc/opt/remi/php73/
    ln -s /data/etc/opt/remi/php73/php-fpm.d /etc/opt/remi/php73/php-fpm.d
fi
