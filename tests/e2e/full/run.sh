#!/bin/bash

# This script will execute all the tests once the platform is ready to accept them

set -euxo pipefail

setup_user() {
    if [ -d /output ]; then
        uid=$(stat -c %u /output)
        gid=$(stat -c %g /output)
    else
        mkdir /output
        chown 1000.1000 /output
        uid=1000
        gid=1000
    fi
    groupmod -g 11007 node
    usermod -u 11007 node

    if ! id runner 2>&1 >/dev/null; then
        groupadd -g $gid runner
        useradd -g $gid -u $uid -m runner
    fi
}

is_server_ready() {
    code=$(curl -k -s -o /dev/null -w "%{http_code}"  https://tuleap || true)
    while [ $code -ne 200 ]; do
        sleep 1
        code=$(curl -k -s -o /dev/null -w "%{http_code}"  https://tuleap || true)
    done
}

setup_user

is_server_ready

su -c 'cypress run --project /tuleap/tests/e2e/full' -l runner
