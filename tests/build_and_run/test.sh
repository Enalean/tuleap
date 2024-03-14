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

rpms="$(mktemp -d)"

function cleanup {
    docker rm -fv "$UNIQUE_NAME-rpm-installer" || true
    docker volume rm -f "$UNIQUE_NAME-rpm-volume" || true
    rm -rf "$rpms"
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

nix-shell --pure -I nixpkgs="$WORKSPACE/sources/tools/utils/nix/pinned-nixpkgs.nix" "$WORKSPACE/sources/tools/utils/nix/build-tools/" \
    --run "cd $WORKSPACE/sources && \
        OS=${OS} RELEASE=1 tools/rpm/build_all_rpm.sh $WORKSPACE/sources $rpms"

docker volume create "$UNIQUE_NAME-rpm-volume"
docker run --rm \
    -v "$UNIQUE_NAME-rpm-volume":/rpms \
    -v "$rpms":/source-rpms:ro \
    --entrypoint=/bin/sh \
    $INSTALL_IMAGE -c 'cp -a /source-rpms/* /rpms/'

docker run -t \
    --name "$UNIQUE_NAME-rpm-installer" \
    -v "$UNIQUE_NAME-rpm-volume":/rpms \
    -v /sys/fs/cgroup:/sys/fs/cgroup:rw \
    --mount type=tmpfs,destination=/run \
    --cap-add=sys_nice \
    $INSTALL_IMAGE

mkdir -p "$WORKSPACE/results/build-and-run-$OS"

docker cp "$UNIQUE_NAME-rpm-installer":/var/log "$WORKSPACE/results/build-and-run-$OS" || true
docker cp "$UNIQUE_NAME-rpm-installer":/var/opt/remi/php82/log/php-fpm "$WORKSPACE/results/build-and-run-$OS/fpm" || true
docker cp "$UNIQUE_NAME-rpm-installer":/root/.tuleap_passwd "$WORKSPACE/results/build-and-run-$OS/tuleap_passwd" || true

docker cp "$UNIQUE_NAME-rpm-installer":/output/api-version.json "$WORKSPACE/results/build-and-run-$OS"
grep "$(cat "$WORKSPACE"/sources/VERSION)" "$WORKSPACE/results/build-and-run-$OS/api-version.json"
