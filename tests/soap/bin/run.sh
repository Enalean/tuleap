#!/usr/bin/env bash

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

# Added to workaround the migration between old and new SOAP tests
# old tests were having composer related stuff owned by root while
# the new ones are owned by runner
# This should be removed ~ end of october 2017
if [ -d /usr/share/tuleap/vendor ]; then
    USER_ID=$(stat -c '%u' /usr/share/tuleap/vendor)
    if [ "$USER_ID" -eq 0 ]; then
        rm -rf /usr/share/tuleap/composer.* /usr/share/tuleap/vendor
    fi
fi

setup_runner_account

/usr/share/tuleap/tests/soap/bin/setup.sh

su -c "/usr/share/tuleap/tests/soap/bin/test_suite.sh" -l runner
