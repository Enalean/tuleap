#!/usr/bin/env bash

# This is the central script to execute in order to execute "e2e tests"
# This will bring-up the platform, run the tests, stop and remove everything

set -euxo pipefail

MAX_TEST_EXECUTION_TIME='60m'
TIMEOUT="$(command -v gtimeout || echo timeout)"
plugins_compose_file="$(find ./plugins/*/tests/e2e/ -name docker-compose.yml -printf '-f %p ')"

additional_compose_file=""
if [ -f "${EXTRA_COMPOSE_FILE:-}" ]; then
    additional_compose_file="-f $EXTRA_COMPOSE_FILE"
fi

case "${1:-}" in
    "mysql80")
    export DB_HOST="mysql80"
    ;;
    *)
    echo "A database type must be provided as parameter. Allowed values are:"
    echo "* mysql80"
    exit 1
esac

project_name="$(echo -n "e2e-tests-${BUILD_TAG:-dev}" | tr '.' '_' | tr '[A-Z]' '[a-z]')"
DOCKERCOMPOSE="docker-compose --project-directory . -f ./tests/e2e/compose.yaml -f ./tests/e2e/compose-run-tests.yaml -f tests/e2e/docker-compose-db-${DB_HOST}.yml $plugins_compose_file $additional_compose_file -p $project_name"

test_results_folder='./test_results_e2e_full'
if [ "$#" -eq "2" ]; then
    test_results_folder="$2"
fi

cypress_version="$(python3 -c 'import json,sys;print(json.load(sys.stdin)["version"], end="")' < ./node_modules/cypress/package.json)"

clean_env() {
    $DOCKERCOMPOSE down --remove-orphans --volumes || true
}
trap clean_env EXIT

mkdir -p "$test_results_folder" || true
rm -rf "$test_results_folder/*" || true
clean_env

export TEST_RESULT_OUTPUT="$test_results_folder"
export CYPRESS_VERSION="$cypress_version"
$DOCKERCOMPOSE up -d --build

# Give a bit of time for containers to be in "running" state
sleep 1

test_phpunit_container_id="$($DOCKERCOMPOSE ps -q test-phpunit)"
test_cypress_container_id="$($DOCKERCOMPOSE ps -q test-cypress)"

$TIMEOUT "$MAX_TEST_EXECUTION_TIME" docker wait "$test_phpunit_container_id" "$test_cypress_container_id" || \
        echo 'Tests take to much time to execute. End of execution will not be waited for!'

mkdir -p "$test_results_folder/logs"
$DOCKERCOMPOSE cp tuleap:/var/log/ "$test_results_folder/logs"
$DOCKERCOMPOSE cp tuleap:/var/opt/remi/php82/log/php-fpm/ "$test_results_folder/logs"
$DOCKERCOMPOSE logs tuleap > "$test_results_folder/logs/tuleap.log"

$DOCKERCOMPOSE logs test-phpunit > "$test_results_folder/logs/test-phpunit.log"
$DOCKERCOMPOSE logs test-cypress > "$test_results_folder/logs/test-cypress.log"

[ "$(docker inspect "$test_phpunit_container_id" --format='{{.State.ExitCode}}')" -eq 0 ]
[ "$(docker inspect "$test_cypress_container_id" --format='{{.State.ExitCode}}')" -eq 0 ]
