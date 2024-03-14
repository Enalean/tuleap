#!/usr/bin/env bash

set -exuo pipefail

RESULT_FOLDER="${WORKSPACE}/results/js-test-results/"

mkdir -p "$RESULT_FOLDER"
export CI_MODE="true"
timeout 1h pnpm test
pnpm -r exec -- "$(pwd)"/lib/frontend/build-system-configurator/bin/copy-js-test-results.sh "$RESULT_FOLDER"
