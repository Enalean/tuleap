#!/usr/bin/env bash

set -ex

# Basic usage of this script: `./tools/rpm/build_and_run_packages.sh --src-dir="$(pwd)"`
# Two environments variables can be set for additional behaviors:
#  * `ENTERPRISE=1` to build Tuleap Enterprise packages
#  * `EXPERIMENTAL_BUILD=1` to build experimental parts (e.g. a Tuleap Enterprise package not yet ready to be included in a major release)

options=$(getopt -o h -l src-dir: -- "$@")

eval set -- "$options"
while true
do
    case "$1" in
    --src-dir)
        SRC_DIR=$2;
        shift 2;;
    --)
        shift 1; break ;;
    *)
        break ;;
    esac
done

if [ -z "$SRC_DIR" ]; then
    echo "You must specify --src-dir argument";
    exit 1;
fi

if [ -z "$OS" ]; then
    >&2 echo "OS environment variable must be defined (centos7|el9)"
    exit 1
fi

export DOCKER_BUILDKIT=1

clean_tuleap_sources="$(mktemp -d)"
rpms="$(mktemp -d)"

function cleanup {
    docker rm -fv rpm-installer || true
    docker volume rm -f rpm-volume || true
    git worktree remove -f "$clean_tuleap_sources"
    rm -rf "$rpms"
}
trap cleanup EXIT

git worktree add --detach "$clean_tuleap_sources/"

if [ "$ENTERPRISE" == "1" ]; then
    touch "$clean_tuleap_sources/ENTERPRISE_BUILD"
fi

nix-shell --pure -I nixpkgs="$clean_tuleap_sources/tools/utils/nix/pinned-nixpkgs.nix" "$clean_tuleap_sources/tools/utils/nix/build-tools/" \
    --run "export EXPERIMENTAL_BUILD=${EXPERIMENTAL_BUILD:-0} && export OS=${OS} && export RELEASE=${RELEASE:-1} && \
        cd $clean_tuleap_sources && \
        tools/utils/scripts/generated-files-builder.sh prod && \
        tools/rpm/build_all_rpm.sh $clean_tuleap_sources $rpms"

if [ "$OS" == "centos7" ]; then
    INSTALL_IMAGE=tuleap-installrpms:centos7
    docker build --target interactive --tag $INSTALL_IMAGE -f "$SRC_DIR/tools/docker/install-rpms/centos7.dockerfile" "$SRC_DIR/tools/docker/install-rpms/"
elif [ "$OS" == "el9" ]; then
    INSTALL_IMAGE=tuleap-installrpms:el9
    docker build --target interactive --tag $INSTALL_IMAGE -f "$SRC_DIR/tools/docker/install-rpms/rockylinux9.dockerfile" "$SRC_DIR/tools/docker/install-rpms/"
else
    >&2 echo "OS environment variable does not have a valid value"
    exit 1
fi

docker volume create "rpm-volume"
docker run --rm \
    -v "rpm-volume":/rpms \
    -v "$rpms":/source-rpms:ro \
    --entrypoint=/bin/sh \
    $INSTALL_IMAGE -c 'cp -a /source-rpms/* /rpms/'

docker run -t -d --rm \
    --name rpm-installer \
    -v rpm-volume:/rpms \
    -v /sys/fs/cgroup:/sys/fs/cgroup:rw \
    --mount type=tmpfs,destination=/run \
    --cap-add=sys_nice \
    $INSTALL_IMAGE
docker logs -f rpm-installer | tee >( grep -q 'Started Install and run Tuleap.' ) || true
docker exec -ti rpm-installer bash
