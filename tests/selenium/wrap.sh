#!/bin/bash

# This is the central script to execute in order to execute "whole platform integration tests"
# It is meant to be called without any arguments
# This will bring-up the platform, run the tests, stop and remove everything

set -ex

DOCKERCOMPOSE="docker-compose -f docker-compose-distlp-tests.yml -p distlp-tests-${BUILD_TAG}"

clean_env() {
    $DOCKERCOMPOSE down --remove-orphans --volumes || true
}

wait_until_tests_are_executed() {
    local test_container_id="$($DOCKERCOMPOSE ps -q test)"
    docker wait "$test_container_id"
}

mkdir -p test_results || true
rm -rf test_results/* || true
clean_env

$DOCKERCOMPOSE up -d --build

wait_until_tests_are_executed

$DOCKERCOMPOSE logs backend-web > test_results/backend-web.log
$DOCKERCOMPOSE logs backend-svn > test_results/backend-svn.log
$DOCKERCOMPOSE logs test > test_results/test.log

clean_env
