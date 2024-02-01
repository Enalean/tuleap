#!/usr/bin/env bash

set -euo pipefail

MAX_TEST_EXECUTION_TIME='30m'
READLINK="$(command -v greadlink || echo readlink)"
TIMEOUT="$(command -v gtimeout || echo timeout)"

BASEDIR="$(dirname "$($READLINK -f "$0")")/../../../"
export BASEDIR
pushd "$BASEDIR"

case "${1:-}" in
    "82")
    export PHP_VERSION="php82"
    ;;
    *)
    echo "A PHP version must be provided as parameter. Allowed values are:"
    echo "* 81"
    echo "* 82"
    exit 1
esac

case "${2:-}" in
    "mysql80")
    export DB_HOST="mysql80"
    ;;
    *)
    echo "A database type must be provided as parameter. Allowed values are:"
    echo "* mysql80"
    exit 1
esac

plugins_compose_file="$(find ./plugins/*/tests/rest/ -name docker-compose.yml -printf '-f %p ')"
project_name="$(echo -n "rest-${PHP_VERSION}-${DB_HOST}-${BUILD_TAG:-$RANDOM}" | tr '.' '_' | tr '[A-Z]' '[a-z]')"
DOCKERCOMPOSE="docker-compose --project-name $project_name -f tests/rest/docker-compose.yml -f tests/rest/docker-compose-${DB_HOST}.yml $plugins_compose_file"

function cleanup {
    if [ -n "${TESTS_RESULT:-}" ]; then
        $DOCKERCOMPOSE cp tests:/output/. "$TESTS_RESULT" || echo "Failed to copy tests result"
    fi
    $DOCKERCOMPOSE down
}
trap cleanup EXIT

if [ -n "${SETUP_ONLY:-}" ] && [ "$SETUP_ONLY" != "0" ]; then
    $DOCKERCOMPOSE up -d --scale tests=0
    $DOCKERCOMPOSE run tests /usr/share/tuleap/tests/rest/bin/run.sh setup
else
    $TIMEOUT "$MAX_TEST_EXECUTION_TIME" $DOCKERCOMPOSE up --abort-on-container-exit --exit-code-from=tests
fi
