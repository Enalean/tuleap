#!/bin/bash

set -e

# Find the path of this directory
if [ -f "$0" ]; then
    mydir=$(dirname $(readlink -f $0))
else
    mydir=$(dirname $(readlink -f $(which $0)))
fi

PACKAGE_VERSION=$(cat $mydir/../package.json | grep version | cut -d: -f 2 | tr -d ',' | tr -d '"' | tr -d " ")

RELEASE=1
LAST_TAG="$(git describe --abbrev=0 --tags || echo -n '0.0.0')"
if [ "$LAST_TAG" == "$PACKAGE_VERSION" ]; then
    NB_COMMITS=$(git log --oneline $LAST_TAG..HEAD | wc -l)
    if [ $NB_COMMITS -gt 0 ]; then
	RELEASE=$(($NB_COMMITS+1))
    fi
fi

if [[ -z "$OUTPUTDIR" ]]; then
    OUTPUTDIR="$mydir"
fi

export DOCKER_BUILDKIT=1

DOCKERIMAGE_PREPARE=prepare-tuleap-realtime-"${BUILD_NUMBER:-0}"
docker build -t "$DOCKERIMAGE_PREPARE" -f "$mydir"/../nix/docker-env.nix "$mydir"/../nix/
docker run --rm -v "$mydir"/..:/realtime -w /realtime \
    --user "$(id -u):$(id -g)" --tmpfs /tmp/tuleap_realtime_build:rw,noexec,nosuid --read-only \
    -e HOME=/tmp/tuleap_realtime_build \
    "$DOCKERIMAGE_PREPARE" sh -c 'pnpm install --frozen-lockfile && pnpm run build'

DOCKERIMAGE=build-rpm-tuleap-realtime-"$OS"-"${BUILD_NUMBER:-0}"
DOCKER_BUILDKIT=0 docker build --build-arg OS="$OS" -t "$DOCKERIMAGE" "$mydir"/
docker run --rm -e UID=`id -u` -e GID=`id -g` -e RELEASE=$RELEASE -e OS="$OS" -v $mydir/..:/realtime -v "$OUTPUTDIR":/output "$DOCKERIMAGE"
