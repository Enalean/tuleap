#!/usr/bin/env bash

set -euxo pipefail

setup_output_directory() {
    if [ ! -d /output ]; then
        mkdir /output
        chown codendiadm:codendiadm /output
    fi
}

setup_output_directory

/usr/share/tuleap/tests/integration/bin/setup.sh

if [ "${SETUP_ONLY:-0}" -eq 1 ]; then
    set +x
    echo "Command to launch: sudo -E -u codendiadm $PHP_CLI /usr/share/tuleap/src/vendor/bin/phpunit --configuration /usr/share/tuleap/tests/integration/phpunit.xml --do-not-cache-result"
    exec bash
else
    if [ "${SEED:-0}" -eq 0 ]; then random_seed=""; else random_seed="--random-order-seed ${SEED}"; fi
    sudo -E -u codendiadm $PHP_CLI /usr/share/tuleap/src/vendor/bin/phpunit --configuration /usr/share/tuleap/tests/integration/phpunit.xml --do-not-cache-result --log-junit /output/db_tests.xml ${random_seed}
fi
