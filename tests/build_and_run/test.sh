#!/usr/bin/env bash

# Note: images are pulled each and every time to ensure we are running the last version of the image.
#       This is required because we don't version the images

set -ex

if [ -z "$OS" ]; then
    >&2 echo "OS environment variable must be defined"
    exit 1
fi

export DOCKER_BUILDKIT=1

UNIQUE_NAME=$(echo "$JOB_NAME-$BUILD_NUMBER-$OS" | tr '[:upper:]' '[:lower:]')

function cleanup {
    docker rm -fv "$UNIQUE_NAME-rpm-builder" || true
    docker rm -fv "$UNIQUE_NAME-rpm-installer" || true
}
trap cleanup EXIT

if [ "$OS" == "centos7" ]; then
    INSTALL_IMAGE=tuleap-installrpms:centos7-ci
    docker build --target ci --tag $INSTALL_IMAGE -f "$WORKSPACE/sources/tools/docker/install-rpms/centos7.dockerfile" "$WORKSPACE/sources/tools/docker/install-rpms/"
elif [ "$OS" == "el9" ]; then
    INSTALL_IMAGE=tuleap-installrpms:el9-ci
    docker build --target ci --tag $INSTALL_IMAGE -f "$WORKSPACE/sources/tools/docker/install-rpms/rockylinux9.dockerfile" "$WORKSPACE/sources/tools/docker/install-rpms/"
else
    >&2 echo "OS environment variable does not have a valid value"
    exit 1
fi

docker build -t "$UNIQUE_NAME-rpm-builder" -f "$WORKSPACE"/sources/tools/utils/nix/build-tools.dockerfile "$WORKSPACE"/sources/tools/utils/nix/

docker run -i \
    -e "OS=${OS}" \
    --name "$UNIQUE_NAME-rpm-builder" \
    -v /rpms \
    -v "$WORKSPACE/sources":/tuleap:ro \
    -w /tuleap \
    "$UNIQUE_NAME-rpm-builder" \
    tools/rpm/build_rpm_inside_container.sh

docker run -t \
    --name "$UNIQUE_NAME-rpm-installer" \
    --volumes-from "$UNIQUE_NAME-rpm-builder" \
    -v /sys/fs/cgroup:/sys/fs/cgroup:ro \
    --mount type=tmpfs,destination=/run \
    --cap-add=sys_nice \
    $INSTALL_IMAGE

mkdir -p "$WORKSPACE/results/build-and-run-$OS"

docker cp "$UNIQUE_NAME-rpm-installer":/var/log "$WORKSPACE/results/build-and-run-$OS" || true
docker cp "$UNIQUE_NAME-rpm-installer":/var/opt/remi/php81/log/php-fpm "$WORKSPACE/results/build-and-run-$OS/fpm" || true

docker cp "$UNIQUE_NAME-rpm-installer":/output/api-version.json "$WORKSPACE/results/build-and-run-$OS"
grep "$(cat "$WORKSPACE"/sources/VERSION)" "$WORKSPACE/results/build-and-run-$OS/api-version.json"
