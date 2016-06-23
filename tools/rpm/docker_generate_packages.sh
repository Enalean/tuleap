#!/bin/bash

set -e

options=$(getopt -o h -l output-dir:,src-dir: -- "$@")

eval set -- "$options"
while true
do
    case "$1" in
    --output-dir)
        RPM_DIR=$2;
        shift 2;;
    --src-dir)
        SRC_DIR=$2;
        shift 2;;
    --)
        shift 1; break ;;
    *)
        break ;;
    esac
done

if [ -z "$RPM_DIR" ] || [ -z "$SRC_DIR" ]; then
    echo "You must specify --output-dir and --src-dir arguments";
    exit 1;
fi

docker rm srpms-builder || true
docker rm rpm-builder   || true

docker run -i --name srpms-builder -v "$SRC_DIR":/tuleap enalean/tuleap-buildsrpms

docker run -i --name rpm-builder --volumes-from srpms-builder enalean/tuleap-buildrpms:centos6 --php php --folder rhel6
docker cp rpm-builder:/rpms/RPMS/noarch "$RPM_DIR"/rhel6
docker rm rpm-builder

docker rm srpms-builder
