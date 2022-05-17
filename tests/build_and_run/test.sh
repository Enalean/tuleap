#!/usr/bin/env bash

# Note: images are pulled each and every time to ensure we are running the last version of the image.
#       This is required because we don't version the images

set -ex

if [ -z "$OS" ]; then
    >&2 echo "OS environment variable must be defined"
    exit 1
fi

UNIQUE_NAME="$JOB_NAME-$BUILD_NUMBER-$OS"

function cleanup {
    docker rm -fv "$UNIQUE_NAME-rpm-builder" || true
    docker rm -fv "$UNIQUE_NAME-rpm-installer" || true
}
trap cleanup EXIT

docker build -t "$UNIQUE_NAME-rpm-builder" -f "$WORKSPACE"/sources/tools/utils/nix/build-tools.dockerfile "$WORKSPACE"/sources/tools/utils/nix/
docker run -i --name "$UNIQUE_NAME-rpm-builder" -v /rpms -v "$WORKSPACE/sources":/tuleap:ro -w /tuleap "$UNIQUE_NAME-rpm-builder" tools/rpm/build_rpm_inside_container.sh

if [ "$OS" == "centos7" ]; then
    docker pull ${DOCKER_REGISTRY:-ghcr.io}/enalean/tuleap-installrpms:ci-centos7
    docker run -t --name "$UNIQUE_NAME-rpm-installer" --volumes-from "$UNIQUE_NAME-rpm-builder" -v /sys/fs/cgroup:/sys/fs/cgroup:ro \
        -v /dev/null:/etc/yum.repos.d/tuleap.repo:ro \
        --mount type=tmpfs,destination=/run ${DOCKER_REGISTRY:-ghcr.io}/enalean/tuleap-installrpms:ci-centos7
else
    >&2 echo "OS environment variable value does not have a valid value"
    exit 1
fi

mkdir -p "$WORKSPACE/results/build-and-run-$OS"

docker cp "$UNIQUE_NAME-rpm-installer":/var/log/nginx "$WORKSPACE/results/build-and-run-$OS/nginx" || true
docker cp "$UNIQUE_NAME-rpm-installer":/var/opt/remi/php80/log/php-fpm "$WORKSPACE/results/build-and-run-$OS/fpm" || true
docker cp "$UNIQUE_NAME-rpm-installer":/var/log/tuleap "$WORKSPACE/results/build-and-run-$OS/tuleap" || true

docker cp "$UNIQUE_NAME-rpm-installer":/output/index.html "$WORKSPACE/results/build-and-run-$OS"
grep "Dev Build $(cat "$WORKSPACE"/sources/VERSION)" "$WORKSPACE/results/build-and-run-$OS/index.html"
