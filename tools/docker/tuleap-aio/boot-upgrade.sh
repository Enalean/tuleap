#!/bin/bash

set -e

# On start, ensure db is consistent with data (useful for version bump)
/usr/lib/forgeupgrade/bin/forgeupgrade --config=/etc/codendi/forgeupgrade/config.ini update

# Ensure system will be synchronized ASAP (once system starts)
/usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/src/utils/launch_system_check.php

# Switch to php 5.6 + nginx
if [ ! -f "/etc/nginx/conf.d/tuleap.conf" ]; then
    /usr/share/tuleap/tools/utils/php72/run.php

    mv /etc/nginx /data/etc
    ln -s /data/etc/nginx /etc/nginx

    mkdir -p /data/etc/opt/remi/php72/
    mv /etc/opt/remi/php72/php-fpm.d/ /data/etc/opt/remi/php72/
    ln -s /data/etc/opt/remi/php72/php-fpm.d /etc/opt/remi/php72/php-fpm.d
fi
