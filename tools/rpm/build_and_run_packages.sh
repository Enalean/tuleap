#!/bin/bash

set -e

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

clean_tuleap_sources="$(mktemp -d)/"

function cleanup {
    docker rm srpms-builder || true
    docker rm rpm-builder || true
    rm -rf "$clean_tuleap_sources"
}
trap cleanup EXIT

docker rm srpms-builder || true
docker rm rpm-builder   || true

is_in_git_repo=$(cd "$SRC_DIR" && git rev-parse --is-inside-work-tree 2> /dev/null || true)
if [[ "$is_in_git_repo" == true ]]; then
    git checkout-index -a --prefix="$clean_tuleap_sources"
else
    cp -R "$SRC_DIR" "$clean_tuleap_sources"
fi

docker run -i --name srpms-builder -v "$clean_tuleap_sources":/tuleap enalean/tuleap-buildsrpms

docker run -i --name rpm-builder --volumes-from srpms-builder enalean/tuleap-buildrpms:centos6 --php php --folder rhel6

docker rm srpms-builder

docker run -it --rm --name rpm-installer --volumes-from rpm-builder enalean/tuleap-installrpms
