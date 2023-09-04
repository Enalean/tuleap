#!/usr/bin/env bash

set -euo pipefail

MAX_TEST_EXECUTION_TIME='30m'
READLINK="$(command -v greadlink || echo readlink)"
TIMEOUT="$(command -v gtimeout || echo timeout)"

BASEDIR="$(dirname "$($READLINK -f "$0")")/../../../"
export BASEDIR
pushd "$BASEDIR"

case "${1:-}" in
    "81")
    export PHP_VERSION="php81"
    ;;
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
    echo "* mariadb103"
    exit 1
esac

project_name="$(echo -n "db-${PHP_VERSION}-${DB_HOST}-${BUILD_TAG:-$RANDOM}" | tr '.' '_' | tr '[A-Z]' '[a-z]')"
DOCKERCOMPOSE="docker-compose --project-name $project_name -f tests/integration/docker-compose.yml"

function cleanup {
    if [ -n "${TESTS_RESULT:-}" ]; then
        $DOCKERCOMPOSE cp tests:/output/. "$TESTS_RESULT" || echo "Failed to copy tests result"
    fi
    $DOCKERCOMPOSE down
}
trap cleanup EXIT

if [ -n "${SETUP_ONLY:-}" ] && [ "$SETUP_ONLY" != "0" ]; then
    $DOCKERCOMPOSE up -d "$DB_HOST"
    $DOCKERCOMPOSE run -e SETUP_ONLY=1 tests /usr/share/tuleap/tests/integration/bin/run.sh
else
    $TIMEOUT "$MAX_TEST_EXECUTION_TIME" $DOCKERCOMPOSE up --abort-on-container-exit --exit-code-from=tests "$DB_HOST" tests
fi
