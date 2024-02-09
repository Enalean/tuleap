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

docker build -t tuleap-generated-files-builder -f "$SRC_DIR"/tools/utils/nix/nix.dockerfile "$SRC_DIR"/tools/utils/nix/

clean_tuleap_sources="$(mktemp -d)"

function cleanup {
    docker rm -fv rpm-builder rpm-installer || true
    docker volume rm -f rpm-volume || true
    docker volume rm -f nix-volume || true
    git worktree remove -f "$clean_tuleap_sources"
}
trap cleanup EXIT

docker rm rpm-builder || true

git worktree add --detach "$clean_tuleap_sources/"

if [ "$ENTERPRISE" == "1" ]; then
    touch "$clean_tuleap_sources/ENTERPRISE_BUILD"
fi

docker volume create rpm-volume
docker volume create nix-volume
docker run --rm -v rpm-volume:/rpms tuleap-generated-files-builder chown "$(id -u)":"$(id -g)" /rpms
docker run --rm -v nix-volume:/nix tuleap-generated-files-builder chown "$(id -u)":"$(id -g)" /nix

docker run -i --name rpm-builder \
    -v rpm-volume:/rpms -v "$clean_tuleap_sources":/tuleap -w /tuleap \
    -v nix-volume:/nix \
    -v /etc/passwd:/etc/passwd:ro \
    -u "$(id -u)":"$(id -g)" \
    tuleap-generated-files-builder \
    nix-shell --pure -I nixpkgs="/tuleap/tools/utils/nix/pinned-nixpkgs.nix" "/tuleap/tools/utils/nix/build-tools/" \
        --run "export EXPERIMENTAL_BUILD=${EXPERIMENTAL_BUILD:-0} && export OS=${OS} && tools/utils/scripts/generated-files-builder.sh prod && XDG_CACHE_HOME=/home_build tools/rpm/build_rpm_inside_container.sh"

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

docker run -t -d --rm \
    --name rpm-installer \
    -v rpm-volume:/rpms \
    -v /sys/fs/cgroup:/sys/fs/cgroup:rw \
    --mount type=tmpfs,destination=/run \
    --cap-add=sys_nice \
    $INSTALL_IMAGE
docker logs -f rpm-installer | tee >( grep -q 'Started Install and run Tuleap.' ) || true
docker exec -ti rpm-installer bash
