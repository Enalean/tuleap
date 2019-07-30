#!/bin/sh

set -e

run_testsuite() {
    PHPUNIT=/usr/share/tuleap/tests/rest/vendor/bin/phpunit
    if [ -x "$PHP_CLI" ]; then
        PHPUNIT="$PHP_CLI $PHPUNIT"
    fi
    $PHPUNIT --configuration /usr/share/tuleap/tests/rest/phpunit.xml --do-not-cache-result --log-junit /output/rest_tests.xml $1
}

run_testsuite $1
