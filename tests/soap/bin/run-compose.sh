#!/bin/bash

set -euo pipefail

MAX_TEST_EXECUTION_TIME='30m'
READLINK="$(command -v greadlink || echo readlink)"
TIMEOUT="$(command -v gtimeout || echo timeout)"

BASEDIR="$(dirname "$($READLINK -f "$0")")/../../../"
export BASEDIR
pushd "$BASEDIR"
DOCKERCOMPOSE="docker-compose --project-name soap-${BUILD_TAG:-$RANDOM} -f tests/soap/docker-compose.yml"

function cleanup {
    if [ -n "${TESTS_RESULT:-}" ]; then
        docker cp "$($DOCKERCOMPOSE ps -q tests)":/output/. "$TESTS_RESULT" || echo "Failed to copy tests result"
    fi
    $DOCKERCOMPOSE down
}
trap cleanup EXIT

case "${1:-}" in
    "73")
    export PHP_VERSION="php73"
    ;;
    "74")
    export PHP_VERSION="php74"
    ;;
    *)
    echo "A PHP version must be provided as parameter. Allowed values are:"
    echo "* 73"
    echo "* 74"
    exit 1
esac

case "${2:-}" in
    "mysql57")
    export DB_HOST="mysql57"
    ;;
    "mariadb103")
    export DB_HOST="mariadb-10.3"
    ;;
    *)
    echo "A database type must be provided as parameter. Allowed values are:"
    echo "* mysql57"
    echo "* mariadb103"
    exit 1
esac

$TIMEOUT "$MAX_TEST_EXECUTION_TIME" $DOCKERCOMPOSE up --abort-on-container-exit --exit-code-from=tests "$DB_HOST" tests
