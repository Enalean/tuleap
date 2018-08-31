#!/bin/sh

set -e

PHP=/opt/remi/php72/root/usr/bin/php

generate_testsuite() {
    $PHP /usr/share/tuleap/tests/rest/bin/generate-testsuite.php /tmp /output
}

run_testsuite() {
    PHPUNIT=/usr/share/tuleap/src/vendor/bin/phpunit
    if [ -x $PHP ]; then
        PHPUNIT="$PHP $PHPUNIT"
    fi
    $PHPUNIT --configuration /tmp/suite.xml
}

generate_testsuite
run_testsuite
