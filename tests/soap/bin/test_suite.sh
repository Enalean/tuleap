#!/usr/bin/env bash

set -ex

setup_composer() {
    (cd /usr/share/tuleap/tests/soap && /usr/local/bin/composer.phar --no-interaction install)
}

generate_testsuite() {
    /opt/rh/rh-php56/root/usr/bin/php /usr/share/tuleap/tests/soap/bin/generate-testsuite.php /tmp /output
}

run_testsuite() {
    /opt/rh/rh-php56/root/usr/bin/php /usr/share/tuleap/tests/soap/vendor/bin/phpunit --configuration /tmp/suite.xml
}

setup_composer
generate_testsuite
run_testsuite
