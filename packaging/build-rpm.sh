#!/bin/sh

# Find the path of this directory
if [ -f "$0" ]; then
    mydir=$(dirname $(readlink -f $0))
else
    mydir=$(dirname $(readlink -f $(which $0)))
fi

RELEASE=1
LAST_TAG=$(git describe --abbrev=0 --tags)
NB_COMMITS=$(git log --oneline $LAST_TAG..HEAD | wc -l)
if [ $NB_COMMITS -gt 0 ]; then
    RELEASE=$NB_COMMITS
fi

docker run --rm -e UID=`id -u` -e GID=`id -g` -e RELEASE=$RELEASE -v $mydir/..:/realtime enalean/build-tuleap-realtime
