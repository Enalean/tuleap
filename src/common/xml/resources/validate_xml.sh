#!/usr/bin/env bash

set -e

exit_code=0

# Find the path of this directory
if [ -f "$0" ]; then
    WHEREAMI=$(dirname "$(readlink -f "$0")")
else
    WHEREAMI=$(dirname "$(readlink -f "$(which "$0")")")
fi

SRC_DIR=$WHEREAMI

if [ -z "$JING" ]; then
    JING=jing
fi

TARGET_DIR=$1
PROJECT_XML="$TARGET_DIR/project.xml"
USERS_XML="$TARGET_DIR/users.xml"

if [ -f "$PROJECT_XML" ]; then
    echo "Validate project.xml"
    if $JING "$SRC_DIR/project/project.rng" "$PROJECT_XML"; then
	echo "OK !"
    else
	echo "Please check your file against $SRC_DIR/project/project.rnc"
	exit_code=1
    fi
else
    echo "project.xml is missing"
    exit_code=1
fi

if [ -f "$USERS_XML" ]; then
    echo "Validate users.xml"
    if $JING "$SRC_DIR/users.rng" "$USERS_XML"; then
	echo "OK !"
    else
	echo "Please check your file against $SRC_DIR/users.rnc"
	exit_code=1
    fi
else
    echo "users.xml is missing"
    exit_code=1
fi

exit $exit_code
