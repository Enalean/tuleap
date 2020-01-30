#!/bin/sh

set -ex

setup_output_directory() {
    if [ ! -d /output ]; then
        mkdir /output
        chown codendiadm:codendiadm /output
    fi
}

setup_output_directory

/usr/share/tuleap/tests/integration/bin/setup.sh

if [ -n "$SETUP_ONLY" ] && [ "$SETUP_ONLY" != "0" ]; then
    set +x
    echo "Command to launch: $PHP_CLI /usr/share/tuleap/src/vendor/bin/phpunit --configuration /usr/share/tuleap/tests/integration/phpunit.xml --do-not-cache-result"
    exec bash
else
    sudo -E -u codendiadm $PHP_CLI /usr/share/tuleap/src/vendor/bin/phpunit --configuration /usr/share/tuleap/tests/integration/phpunit.xml --do-not-cache-result --log-junit /output/db_tests.xml
fi
