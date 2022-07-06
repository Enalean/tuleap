#!/usr/bin/env bash

set -euo pipefail

CURRENT_FOLDER="$(pwd)"
DESTINATION_FOLDER="$1"

basename="$(basename "$CURRENT_FOLDER")"

if [[ -f "$CURRENT_FOLDER"/js-test-results/junit.xml ]]; then
    cp "$CURRENT_FOLDER"/js-test-results/junit.xml "$DESTINATION_FOLDER"/junit-"$basename".xml
fi

if [[ -f "$CURRENT_FOLDER"/js-test-results/cobertura-coverage.xml ]]; then
    cp "$CURRENT_FOLDER"/js-test-results/cobertura-coverage.xml "$DESTINATION_FOLDER"/cobertura-coverage-"$basename".xml
fi
