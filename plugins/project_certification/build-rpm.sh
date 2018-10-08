#!/bin/bash

set -e

if [ ! -d "$TULEAP_PATH" ]; then
    echo "*** ERROR: TULEAP_PATH is missing"
    exit 1
fi

if [ ! -d "$WORKSPACE" ]; then
    echo "*** ERROR: WORKSPACE is missing"
    exit 1
fi

DOCKERIMAGE=build-plugin-project-certification

docker build -t "$DOCKERIMAGE" rpm
docker run --rm \
    -e DIST="${DIST:-.el6}" \
    -e "RELEASE=$RELEASE" \
    -v "$TULEAP_PATH":/tuleap:ro \
    -v "$WORKSPACE":/output \
    -e UID="$(id -u)" \
    -e GID="$(id -g)" \
    "$DOCKERIMAGE"
