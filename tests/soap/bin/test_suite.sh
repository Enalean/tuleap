#!/usr/bin/env bash

set -ex

generate_testsuite() {
    /opt/rh/rh-php56/root/usr/bin/php /usr/share/tuleap/tests/soap/bin/generate-testsuite.php /tmp /output
}

run_testsuite() {
    /opt/rh/rh-php56/root/usr/bin/php /usr/share/tuleap/src/vendor/bin/phpunit --configuration /tmp/suite.xml
}

generate_testsuite
run_testsuite
