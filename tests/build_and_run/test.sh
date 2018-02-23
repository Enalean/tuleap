#!/bin/bash

set -ex

if [ -z "$OS" ]; then
    >&2 echo "OS environment variable should be defined"
    exit 1
fi

UNIQUE_NAME="$JOB_NAME-$BUILD_NUMBER-$OS"

function cleanup {
    docker rm -fv "$UNIQUE_NAME-rpm-builder" || true
    docker rm -fv "$UNIQUE_NAME-rpm-installer" || true
}
trap cleanup EXIT

docker run -i --name "$UNIQUE_NAME-rpm-builder" -v "$WORKSPACE/sources":/tuleap:ro $DOCKER_REGISTRY/enalean/tuleap-buildrpms:"$OS"-without-srpms

if [[ "$OS" == 'centos7' ]]; then
    exit 0
fi

docker run -i --name "$UNIQUE_NAME-rpm-installer" --volumes-from "$UNIQUE_NAME-rpm-builder" $DOCKER_REGISTRY/enalean/tuleap-installrpms:ci

mkdir -p "$WORKSPACE/results/build-and-run-$OS"
docker cp "$UNIQUE_NAME-rpm-installer":/output/index.html "$WORKSPACE/results/build-and-run-$OS"

grep "version $(cat "$WORKSPACE"/sources/VERSION)" "$WORKSPACE/results/build-and-run-$OS/index.html"