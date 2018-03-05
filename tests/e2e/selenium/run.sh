#!/bin/sh

# This script will execute all the tests once the platform is ready to accept them
# Once it's done, the junit xml is moved on the output directory with the right
# credentials.

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

composer_install() {
    su -c 'composer --working-dir=/tuleap/tests/e2e/selenium install' -l runner
}

setup_user

composer_install

is_server_ready

su -c '/tuleap/tests/e2e/selenium/vendor/bin/steward run \
    -vvv \
    --logs-dir=/output \
    --server-url http://chrome:4444 \
    --capability="acceptInsecureCerts:true" \
    staging chrome' -l runner
