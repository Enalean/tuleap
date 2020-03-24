#!/bin/bash

# This is the central script to execute in order to execute "e2e tests"
# This will bring-up the platform, run the tests, stop and remove everything

set -euxo pipefail

MAX_TEST_EXECUTION_TIME='30m'
TIMEOUT="$(command -v gtimeout || echo timeout)"
DOCKERCOMPOSE="docker-compose -f docker-compose-e2e-full-tests.yml -p e2e-tests-${BUILD_TAG:-'dev'}"

test_results_folder='./test_results_e2e_full'
if [ "$#" -eq "1" ]; then
    test_results_folder="$1"
fi

clean_env() {
    $DOCKERCOMPOSE down --remove-orphans --volumes || true
}

mkdir -p "$test_results_folder" || true
rm -rf "$test_results_folder/*" || true
clean_env

TEST_RESULT_OUTPUT="$test_results_folder" $TIMEOUT "$MAX_TEST_EXECUTION_TIME" $DOCKERCOMPOSE up --build --abort-on-container-exit --exit-code-from=test

tuleap_container_id="$($DOCKERCOMPOSE ps -q tuleap)"
mkdir -p "$test_results_folder/logs"
docker cp ${tuleap_container_id}:/var/log/nginx/ "$test_results_folder/logs"
docker cp ${tuleap_container_id}:/var/opt/remi/php73/log/php-fpm/ "$test_results_folder/logs"
$DOCKERCOMPOSE logs tuleap > "$test_results_folder/logs/tuleap.log"

$DOCKERCOMPOSE logs test > "$test_results_folder/logs/test.log"

clean_env
