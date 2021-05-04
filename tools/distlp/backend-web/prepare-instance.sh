#!/bin/bash

set -euxo pipefail

# Workaround that at the time of switch to php 7.4 enalean/tuleap-aio-dev image will not contain the php 7.4 service
# (because it need the commit where all the php 7.4 changes are included).
# At a later point the following code can be removed.
if [ "$PHP_VERSION" = "php74" ]; then
    /bin/cp -f /usr/share/tuleap/src/utils/systemd/tuleap-php-fpm.service /lib/systemd/system/tuleap-php-fpm.service
    systemctl daemon-reload
    systemctl restart tuleap-php-fpm
fi

systemctl reload nginx

sudo -u codendiadm /usr/share/tuleap/src/utils/php-launcher.sh  /usr/share/tuleap/tools/utils/admin/activate_plugin.php svn
touch /etc/tuleap/svn_plugin_installed
/usr/share/tuleap/src/utils/tuleap import-project-xml -u admin --automap=no-email,create:A -i /usr/share/tuleap/tests/e2e/distlp/_fixtures/svn_project_01 --use-lame-password
/usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/src/utils/svn/svnroot_push.php
