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

is_backend_svn_server_ready() {
    code=$(curl -k -s -o /dev/null -w "%{http_code}"  https://reverse-proxy/svnplugin/svn-project-01/sample || true)
    while [ $code -ne 401 ]; do
        sleep 1
        code=$(curl -k -s -o /dev/null -w "%{http_code}"  https://reverse-proxy/svnplugin/svn-project-01/sample || true)
    done
}

is_backend_web_server_ready() {
    code=$(curl -k -s -o /dev/null -w "%{http_code}"  https://reverse-proxy/ || true)
    while [ $code -eq 502 ]; do
        sleep 1
        code=$(curl -k -s -o /dev/null -w "%{http_code}"  https://reverse-proxy/ || true)
    done
}

setup_user

is_backend_svn_server_ready
is_backend_web_server_ready

# Workaround Cypress not being happy when started the first time in a restricted environment
CYPRESS_RUN_BINARY=/Cypress/Cypress /tuleap/node_modules/.bin/cypress verify

su -c '/tuleap/node_modules/.bin/tsc --noEmit --skipLibCheck -p /tuleap/tests/e2e/distlp/cypress/' -l runner
su -c 'CYPRESS_RUN_BINARY=/Cypress/Cypress /tuleap/node_modules/.bin/cypress run --project /tuleap/tests/e2e/distlp' -l runner
