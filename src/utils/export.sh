#!/bin/bash

#
# Wrapper for export_project_xml.php that allows to be ran on a different server
# than a regular Tuleap server.
#
# This is useful when the Tuleap server is not powerful enough to deal with the
# export process or if you don't want to overload front-end by the process.

# Find the path of this directory
if [ -f "$0" ]; then
    mydir=$(dirname $(readlink -f $0))
else
    mydir=$(dirname $(readlink -f $(which $0)))
fi

SRC_DIR=$mydir/..

if [ \( ! -f $PWD/conf/local.inc \) -o \( ! -f $PWD/conf/database.inc \) -o \( ! -d $PWD/logs \) ]; then
    echo "*** ERROR: you are supposed to run this script from a directory where"
    echo "           there are conf/local.inc conf/database.inc files,"
    echo "           and logs directory"
    exit 1
fi

export TULEAP_LOCAL_INC=$PWD/conf/local.inc

php -q -d display_errors=On $mydir/export_project_xml.php $@
