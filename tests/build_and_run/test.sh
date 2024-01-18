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
    docker volume rm -f "$UNIQUE_NAME-rpm-volume" || true
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

docker build -t "$UNIQUE_NAME-rpm-builder" -f "$WORKSPACE"/sources/tools/utils/nix/nix.dockerfile "$WORKSPACE"/sources/tools/utils/nix/

docker volume create "$UNIQUE_NAME-rpm-volume"
docker run --rm -v "$UNIQUE_NAME-rpm-volume":/rpms "$UNIQUE_NAME-rpm-builder" chown "$(id -u)":"$(id -g)" /rpms

docker run -i \
    --name "$UNIQUE_NAME-rpm-builder" \
    -v "$UNIQUE_NAME-rpm-volume":/rpms \
    -v "$WORKSPACE/sources":/tuleap:ro \
    -v "$HOME/nix-content":/nix \
    -v /etc/passwd:/etc/passwd:ro \
    -w /tuleap \
    -u "$(id -u)":"$(id -g)" \
    "$UNIQUE_NAME-rpm-builder" \
    nix-shell --pure -I nixpkgs="/tuleap/tools/utils/nix/pinned-nixpkgs.nix" "/tuleap/tools/utils/nix/build-tools/" \
        --run "OS=${OS} XDG_CACHE_HOME=/home_build tools/rpm/build_rpm_inside_container.sh"

docker run -t \
    --name "$UNIQUE_NAME-rpm-installer" \
    -v "$UNIQUE_NAME-rpm-volume":/rpms \
    -v /sys/fs/cgroup:/sys/fs/cgroup:ro \
    --mount type=tmpfs,destination=/run \
    --cap-add=sys_nice \
    $INSTALL_IMAGE

mkdir -p "$WORKSPACE/results/build-and-run-$OS"

docker cp "$UNIQUE_NAME-rpm-installer":/var/log "$WORKSPACE/results/build-and-run-$OS" || true
docker cp "$UNIQUE_NAME-rpm-installer":/var/opt/remi/php82/log/php-fpm "$WORKSPACE/results/build-and-run-$OS/fpm" || true

docker cp "$UNIQUE_NAME-rpm-installer":/output/api-version.json "$WORKSPACE/results/build-and-run-$OS"
grep "$(cat "$WORKSPACE"/sources/VERSION)" "$WORKSPACE/results/build-and-run-$OS/api-version.json"
