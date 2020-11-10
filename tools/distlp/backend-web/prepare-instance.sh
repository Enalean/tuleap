#!/bin/bash

set -euxo pipefail

# setup.sh script starts nginx and PHP-FPM by default, we want to let supervisord starts them when ready
service php73-php-fpm stop
service nginx stop

/usr/share/tuleap/src/utils/tuleap import-project-xml -u admin --automap=no-email,create:A -i /usr/share/tuleap/tests/e2e/distlp/_fixtures/svn_project_01 --use-lame-password
/usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/src/utils/svn/svnroot_push.php
