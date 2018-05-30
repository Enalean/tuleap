#!/bin/sh

# This script will execute all the tests once the platform is ready to accept them

set -ex

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

    groupadd -g $gid runner
    useradd -g $gid -u $uid -m runner
}

is_server_ready() {
    code=$(curl -k -s -o /dev/null -w "%{http_code}"  https://reverse-proxy/svnplugin/svn-project-01/sample || true)
    while [ $code -ne 401 ]; do
        sleep 1
        code=$(curl -k -s -o /dev/null -w "%{http_code}"  https://reverse-proxy/svnplugin/svn-project-01/sample || true)
    done
}

setup_user

is_server_ready

su -c 'npm install cypress@^2.1.0 && `npm bin`/cypress verify' -l runner
su -c '`npm bin`/cypress run --project /tuleap/tests/e2e/distlp' -l runner
