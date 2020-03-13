#!/bin/bash

set -ex

# Basic usage of this script: `./tools/rpm/build_and_run_packages.sh --src-dir="$(pwd)"`
# Two environnements variables can be set for additional behaviors:
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
    >&2 echo "OS environment variable must be defined (centos7|centos6)"
    exit 1
fi

docker image inspect tuleap-generated-files-builder > /dev/null 2>&1 || \
    (echo 'You should build tuleap-generated-files-builder from the sources https://tuleap.net/plugins/git/tuleap/docker/tuleap-generated-files-builder' && \
    exit 1)


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

docker run -i --rm -v "$clean_tuleap_sources":/tuleap -v "$clean_tuleap_sources":/output tuleap-generated-files-builder

docker run -i --name rpm-builder -e "EXPERIMENTAL_BUILD=${EXPERIMENTAL_BUILD:-0}" -v "$clean_tuleap_sources":/tuleap:ro enalean/tuleap-buildrpms:"$OS"-without-srpms

if [ "$OS" == "centos7" ]; then
    docker run -t -d --rm --name rpm-installer --volumes-from rpm-builder -v /sys/fs/cgroup:/sys/fs/cgroup:ro \
        --mount type=tmpfs,destination=/run enalean/tuleap-installrpms:centos7
    docker logs -f rpm-installer | tee >( grep -q 'Started Install and run Tuleap.' ) || true
    docker exec -ti rpm-installer bash
else
    docker run --rm -ti --name rpm-installer -e DB=mysql57 --volumes-from rpm-builder enalean/tuleap-installrpms:centos6
fi
