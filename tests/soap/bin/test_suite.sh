#!/usr/bin/env bash

set -ex

setup_composer() {
    cp /usr/share/tuleap/tests/soap/bin/composer.json /usr/share/tuleap
    (cd /usr/share/tuleap && /usr/local/bin/composer.phar --no-interaction install)
}

generate_testsuite() {
    php /usr/share/tuleap/tests/bin/generate-phpunit-testsuite-soap.php /tmp /output
}

run_testsuite() {
    PHPUNIT=/usr/share/tuleap/vendor/bin/phpunit
    $PHPUNIT --configuration /tmp/suite.xml
}

setup_composer
generate_testsuite
run_testsuite
