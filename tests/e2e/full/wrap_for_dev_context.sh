#!/bin/bash

# This is the central script to execute in order to execute "e2e tests"
# This will bring-up the platform, open cypress

set -euxo pipefail
plugins_compose_file="$(find ./plugins/*/tests/e2e/ -name docker-compose.yml -printf '-f %p ')"
DOCKERCOMPOSE="docker-compose -f docker-compose-e2e-full-tests-with-app.yml $plugins_compose_file -p e2e-tests"

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

HOSTS="$($DOCKERCOMPOSE ps -q | xargs docker inspect --format='{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}{{range .NetworkSettings.Networks}}{{range .Aliases}} {{ . }}{{end}}{{end}}' | grep -v mysql57)"
echo -e "Please set in /etc/hosts:\n\e[32m$HOSTS\e[0m"
