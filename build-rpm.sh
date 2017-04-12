#!/bin/bash

if [ ! -d "$TULEAP_PATH" ]; then
    echo "*** ERROR: TULEAP_PATH is missing"
    exit 1
fi

DOCKERIMAGE=build-plugin-botmattermost-agiledashboard-rpm

RELEASE=1
LAST_TAG=$(git describe --abbrev=0 --tags)
NB_COMMITS=$(git log --oneline $LAST_TAG..HEAD | wc -l)

if [ $NB_COMMITS -gt 0 ]; then
    RELEASE=$((NB_COMMITS + 1))
fi

docker build -t $DOCKERIMAGE rpm
docker run --rm -v $TULEAP_PATH:/tuleap -v $PWD:/tuleap/plugins/botmattermost_agiledashboard -v $WORKSPACE:/output -e UID=`id -u` -e GID=`id -g` -e RELEASE=$RELEASE $DOCKERIMAGE
