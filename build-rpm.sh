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

DOCKERIMAGE=build-plugin-enalean-licensemanager-rpm

PACKAGE_VERSION=$(tr -d '[:space:]' < VERSION)

RELEASE=1
LAST_TAG="$(git describe --abbrev=0 --tags 2> /dev/null)"
if [ "$LAST_TAG" == "$PACKAGE_VERSION" ]; then
    NB_COMMITS=$(git log --oneline "$LAST_TAG"..HEAD | wc -l)
    if [ "$NB_COMMITS" -gt 0 ]; then
	    RELEASE=$((NB_COMMITS + 1))
    fi
fi

docker build -t "$DOCKERIMAGE" rpm
docker run --rm -v "$TULEAP_PATH":/tuleap:ro -v "$(pwd)":/plugin:ro -v "$WORKSPACE":/output -e UID="$(id -u)" -e GID="$(id -g)" -e RELEASE="$RELEASE" "$DOCKERIMAGE"
