#!/usr/bin/env bash

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

# Workaround Cypress not being happy when started the first time in a restricted environment
CYPRESS_RUN_BINARY=/Cypress/Cypress /tuleap/node_modules/.bin/cypress verify

has_failed=0

su -c '/tuleap/node_modules/.bin/tsc --noEmit --skipLibCheck -p /tuleap/tests/e2e/full' -l runner
su -c 'CYPRESS_RUN_BINARY=/Cypress/Cypress /tuleap/node_modules/.bin/cypress run --project /tuleap/tests/e2e/full' -l runner || has_failed=1

for project in $(find /tuleap/plugins/*/tests/e2e/ -maxdepth 1 -mindepth 1 -type d) ; do
    su -c "/tuleap/node_modules/.bin/tsc --noEmit --skipLibCheck -p $project" -l runner
    su -c "CYPRESS_RUN_BINARY=/Cypress/Cypress /tuleap/node_modules/.bin/cypress run --project $project" -l runner || has_failed=1
done

# Merge all xml files into a single one to ease report readability
su -c 'jrm /output/merged-e2e-results.xml "/output/e2e-result*.xml" && rm /output/e2e-result*.xml' -l runner || has_failed=1

exit $has_failed
