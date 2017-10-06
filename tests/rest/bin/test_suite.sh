#!/bin/sh

set -e

setup_composer() {
    (cd /usr/share/tuleap/tests/rest && scl enable rh-git29 "/usr/local/bin/composer.phar --no-interaction install")
}

generate_testsuite() {
    php /usr/share/tuleap/tests/rest/bin/generate-testsuite.php /tmp /output
}

run_testsuite() {
    PHPUNIT=/usr/share/tuleap/tests/rest/vendor/bin/phpunit
    if [ -x /opt/rh/rh-php70/root/usr/bin/php ]; then
        PHPUNIT="/opt/rh/rh-php70/root/usr/bin/php $PHPUNIT"
    fi
    $PHPUNIT --configuration /tmp/suite.xml
}

setup_composer
generate_testsuite
run_testsuite
