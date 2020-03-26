#!/usr/bin/env bash

set -x

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

/usr/share/tuleap/tests/soap/bin/setup.sh

sudo -E -u runner "/usr/share/tuleap/tests/soap/bin/test_suite.sh"
