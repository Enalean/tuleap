#!/bin/sh

set -e

PHP=/opt/remi/php72/root/usr/bin/php

setup_composer() {
    (cd /usr/share/tuleap/tests/rest && scl enable rh-git29 "$PHP /usr/local/bin/composer.phar --no-interaction install")
}

generate_testsuite() {
    php /usr/share/tuleap/tests/rest/bin/generate-testsuite.php /tmp /output
}

run_testsuite() {
    PHPUNIT=/usr/share/tuleap/src/vendor/bin/phpunit
    if [ -x $PHP ]; then
        PHPUNIT="$PHP $PHPUNIT"
    fi
    $PHPUNIT --configuration /tmp/suite.xml
}

setup_composer
generate_testsuite
run_testsuite
