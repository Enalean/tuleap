#!/bin/bash

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

docker pull $DOCKER_REGISTRY/enalean/tuleap-buildrpms:"$OS"-without-srpms
docker run -i --name "$UNIQUE_NAME-rpm-builder" -v "$WORKSPACE/sources":/tuleap:ro $DOCKER_REGISTRY/enalean/tuleap-buildrpms:"$OS"-without-srpms

if [ "$OS" == "centos7" ]; then
    docker pull $DOCKER_REGISTRY/enalean/tuleap-installrpms:ci-centos7
    docker run -t --name "$UNIQUE_NAME-rpm-installer" --volumes-from "$UNIQUE_NAME-rpm-builder" -v /sys/fs/cgroup:/sys/fs/cgroup:ro \
        --mount type=tmpfs,destination=/run $DOCKER_REGISTRY/enalean/tuleap-installrpms:ci-centos7
else
    docker pull $DOCKER_REGISTRY/enalean/tuleap-installrpms:ci-centos6
    docker run -i --name "$UNIQUE_NAME-rpm-installer" -e DB=mysql57 --volumes-from "$UNIQUE_NAME-rpm-builder" $DOCKER_REGISTRY/enalean/tuleap-installrpms:ci-centos6
fi

mkdir -p "$WORKSPACE/results/build-and-run-$OS"
docker cp "$UNIQUE_NAME-rpm-installer":/output/index.html "$WORKSPACE/results/build-and-run-$OS"
docker cp "$UNIQUE_NAME-rpm-installer":/var/log/nginx "$WORKSPACE/results/build-and-run-$OS/nginx"
docker cp "$UNIQUE_NAME-rpm-installer":/var/opt/remi/php73/log/php-fpm "$WORKSPACE/results/build-and-run-$OS/fpm"
docker cp "$UNIQUE_NAME-rpm-installer":/var/log/tuleap "$WORKSPACE/results/build-and-run-$OS/tuleap"

grep "Dev Build $(cat "$WORKSPACE"/sources/VERSION)" "$WORKSPACE/results/build-and-run-$OS/index.html"
