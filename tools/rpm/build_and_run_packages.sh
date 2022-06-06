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
    >&2 echo "OS environment variable must be defined (centos7)"
    exit 1
fi

docker build -t tuleap-generated-files-builder -f "$SRC_DIR"/tools/utils/nix/build-tools.dockerfile "$SRC_DIR"/tools/utils/nix/

clean_tuleap_sources="$(mktemp -d)"

function cleanup {
    docker rm -fv rpm-builder rpm-installer || true
    rm -rf "$clean_tuleap_sources"
}
trap cleanup EXIT

docker rm rpm-builder || true

is_in_git_repo=$(cd "$SRC_DIR" && git rev-parse --is-inside-work-tree 2> /dev/null || true)
if [[ "$is_in_git_repo" == true ]]; then
    git checkout-index -a --prefix="$clean_tuleap_sources/"
else
    cp -R "$SRC_DIR" "$clean_tuleap_sources/"
fi

if [ "$ENTERPRISE" == "1" ]; then
    touch "$clean_tuleap_sources/ENTERPRISE_BUILD"
fi

docker run -i --rm -v "$clean_tuleap_sources":/tuleap -w /tuleap -u "$(id -u):$(id -g)" --tmpfs /tmp/tuleap_build:rw,noexec,nosuid tuleap-generated-files-builder tools/utils/scripts/generated-files-builder.sh prod

docker run -i --name rpm-builder -e "EXPERIMENTAL_BUILD=${EXPERIMENTAL_BUILD:-0}" -v /rpms -v "$clean_tuleap_sources":/tuleap:ro -w /tuleap tuleap-generated-files-builder tools/rpm/build_rpm_inside_container.sh

if [ "$OS" == "centos7" ]; then
    docker pull ghcr.io/enalean/tuleap-installrpms:centos7
    cosign verify -key "$SRC_DIR"/tools/utils/signing-keys/tuleap-additional-tools.pub ghcr.io/enalean/tuleap-installrpms:centos7
    docker run -t -d --rm --name rpm-installer --volumes-from rpm-builder -v /sys/fs/cgroup:/sys/fs/cgroup:ro \
        -v /dev/null:/etc/yum.repos.d/tuleap.repo:ro \
        --mount type=tmpfs,destination=/run ghcr.io/enalean/tuleap-installrpms:centos7
    docker logs -f rpm-installer | tee >( grep -q 'Started Install and run Tuleap.' ) || true
    docker exec -ti rpm-installer bash
else
    >&2 echo "OS environment variable does not have a valid value"
    exit 1
fi
