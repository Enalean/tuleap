#!/usr/bin/env bash

set -ex

run_testsuite() {
    "$PHP_CLI" /usr/share/tuleap/src/vendor/bin/phpunit --do-not-cache-result --configuration /usr/share/tuleap/tests/soap/phpunit.xml  --log-junit /output/soap_tests.xml
}

run_testsuite
