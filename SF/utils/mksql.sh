#!/bin/sh
#
# CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
# Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2002. All Rights Reserved
# http://codex.xerox.com
#
# $Id$
#
#  License:
#    This file is subject to the terms and conditions of the GNU General Public
#    license. See the file COPYING in the main directory of this archive for
#    more details.
#
# Purpose:
#    Create the CodeX database with the right initial values

if [ $# != 1 ]
then
    echo "Usage: mksql.sh <CodeX domain name>"
    exit 1
fi 

# Determine the script location
progname=$0
scriptdir=`dirname $progname`
sqldir="$scriptdir/../db/mysql"

sed s/_DOMAIN_NAME_/$1/g $sqldir/database_initvalues.sql > /tmp/mksql_$$

mysql -u sourceforge -p sourceforge < $sqldir/database_structure.sql
mysql -u sourceforge -p sourceforge < /tmp/mksql_$$




