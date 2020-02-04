#!/bin/bash

# This is the central script to execute in order to execute "e2e tests"
# This will bring-up the platform, open cypress

set -euxo pipefail
DOCKERCOMPOSE="docker-compose -f docker-compose-e2e-full-tests-with-app.yml -p e2e-tests"

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

$DOCKERCOMPOSE up -d --build

TEST_RESULT_OUTPUT="$test_results_folder" $DOCKERCOMPOSE up -d --build

TULEAP_IP="$(docker inspect "$($DOCKERCOMPOSE ps -q tuleap)" --format='{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}')"
echo "Please set in /etc/hosts: $TULEAP_IP tuleap"
