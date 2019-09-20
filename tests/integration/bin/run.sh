#!/bin/sh

set -ex

setup_runner_account() {
    USER_ID=$(stat -c '%u' /usr/share/tuleap)
    GROUP_ID=$(stat -c '%g' /usr/share/tuleap)

    if [[ "$USER_ID" -eq 0 && "$GROUP_ID" -eq 0 ]]; then
        USER_ID=1000
        GROUP_ID=1000
    fi

    groupadd -g $GROUP_ID runner
    useradd -u $USER_ID -g $GROUP_ID runner
    echo "runner soft nproc unlimited" >> /etc/security/limits.d/90-nproc.conf

    if [ ! -d /output ]; then
        mkdir /output
        chown $USER_ID:$GROUP_ID /output
    fi
}

setup_runner_account

/usr/share/tuleap/tests/integration/bin/setup.sh

if [ -n "$SETUP_ONLY" ] && [ "$SETUP_ONLY" != "0" ]; then
  echo "Command to launch: $PHP_CLI /usr/share/tuleap/src/vendor/bin/phpunit --configuration /usr/share/tuleap/tests/integration/phpunit.xml --do-not-cache-result"
  exec bash
else
    sudo -E -u runner $PHP_CLI /usr/share/tuleap/src/vendor/bin/phpunit --configuration /usr/share/tuleap/tests/integration/phpunit.xml --do-not-cache-result --log-junit /output/db_tests.xml
fi
