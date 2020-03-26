#!/usr/bin/env bash

set -ex

generate_testsuite() {
    "$PHP_CLI" /usr/share/tuleap/tests/soap/bin/generate-testsuite.php /tmp /output
}

run_testsuite() {
    "$PHP_CLI" /usr/share/tuleap/src/vendor/bin/phpunit --do-not-cache-result --configuration /tmp/suite.xml
}

generate_testsuite
run_testsuite
