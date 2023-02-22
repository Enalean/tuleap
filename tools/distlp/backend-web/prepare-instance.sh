#!/usr/bin/env bash

set -euxo pipefail

systemctl reload nginx

sudo -u codendiadm /usr/share/tuleap/src/utils/tuleap plugin:install svn
touch /etc/tuleap/svn_plugin_installed
/usr/share/tuleap/src/utils/tuleap import-project-xml -u admin --automap=no-email,create:A -i /usr/share/tuleap/tests/e2e/distlp/_fixtures/svn_project_01 --use-lame-password
/usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/src/utils/svn/svnroot_push.php
