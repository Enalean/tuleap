#!/bin/sh

set -e

setup_composer() {
    (cd /usr/share/tuleap && scl enable git19 "/usr/local/bin/composer.phar --no-interaction install")
}

generate_testsuite() {
    php /usr/share/tuleap/tests/bin/generate-phpunit-testsuite.php /tmp /output noboostrap
}

run_testsuite() {
    PHPUNIT=/usr/share/tuleap/vendor/bin/phpunit
    if [ -x /opt/rh/rh-php56/root/usr/bin/php ]; then
        PHPUNIT="/opt/rh/rh-php56/root/usr/bin/php $PHPUNIT"
    fi
    $PHPUNIT --configuration /tmp/suite.xml
}

setup_composer
generate_testsuite
run_testsuite
