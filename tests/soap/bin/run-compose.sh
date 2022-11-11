#!/usr/bin/env bash

set -euo pipefail

MAX_TEST_EXECUTION_TIME='30m'
READLINK="$(command -v greadlink || echo readlink)"
TIMEOUT="$(command -v gtimeout || echo timeout)"

BASEDIR="$(dirname "$($READLINK -f "$0")")/../../../"
export BASEDIR
pushd "$BASEDIR"

case "${1:-}" in
    "80")
    export PHP_VERSION="php80"
    ;;
    *)
    echo "A PHP version must be provided as parameter. Allowed values are:"
    echo "* 80"
    exit 1
esac

case "${2:-}" in
    "mysql57")
    export DB_HOST="mysql57"
    ;;
    "mysql80")
    export DB_HOST="mysql80"
    ;;
    "mariadb103")
    export DB_HOST="mariadb-10.3"
    ;;
    *)
    echo "A database type must be provided as parameter. Allowed values are:"
    echo "* mysql57"
    echo "* mysql80"
    echo "* mariadb103"
    exit 1
esac

project_name="$(echo -n "soap-${PHP_VERSION}-${DB_HOST}-${BUILD_TAG:-$RANDOM}" | tr '.' '_' | tr '[A-Z]' '[a-z]')"
DOCKERCOMPOSE="docker-compose --project-name $project_name -f tests/soap/docker-compose.yml"

function cleanup {
    if [ -n "${TESTS_RESULT:-}" ]; then
        docker cp "$($DOCKERCOMPOSE ps -q tests)":/output/. "$TESTS_RESULT" || echo "Failed to copy tests result"
    fi
    $DOCKERCOMPOSE down
}
trap cleanup EXIT

$TIMEOUT "$MAX_TEST_EXECUTION_TIME" $DOCKERCOMPOSE up --abort-on-container-exit --exit-code-from=tests "$DB_HOST" tests
