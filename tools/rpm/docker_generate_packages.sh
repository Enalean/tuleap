#!/bin/bash

set -e

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

options=`getopt -o h -l output-dir: -- "$@"`

eval set -- "$options"
while true
do
    case "$1" in
    --output-dir)
        RPM_DIR=$2;
        shift 2;;
    --)
        shift 1; break ;;
    *)
        break ;;
    esac
done

if [ -z "$RPM_DIR" ]; then
    echo "You must specify --output-dir argument";
    exit 1;
fi

docker rm srpms-builder || true
docker rm rpm-builder   || true

docker run -ti --name srpms-builder -v $DIR/../..:/tuleap enalean/tuleap-buildsrpms

docker run -ti --name rpm-builder --volumes-from srpms-builder enalean/tuleap-buildrpms:centos5 --php php53 --folder rhel5
docker cp rpm-builder:/tmp/build/RPMS/noarch $RPM_DIR/rhel5-53
docker rm rpm-builder

docker run -ti --name rpm-builder --volumes-from srpms-builder enalean/tuleap-buildrpms:centos5 --php php --folder rhel5
docker cp rpm-builder:/tmp/build/RPMS/noarch $RPM_DIR/rhel5
docker rm rpm-builder

docker run -ti --name rpm-builder --volumes-from srpms-builder enalean/tuleap-buildrpms:centos6 --php php --folder rhel6
docker cp rpm-builder:/tmp/build/RPMS/noarch $RPM_DIR/rhel6
docker rm rpm-builder

docker rm srpms-builder
