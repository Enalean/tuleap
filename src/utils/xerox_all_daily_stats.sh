#!/bin/sh
#
# CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
# Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001-2005. All Rights Reserved
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
#    Chain all the stats scripts that have to be run daily
#

# Read util directory location from local.inc
if [ -z "$CODEX_LOCAL_INC" ]; then 
    CODEX_LOCAL_INC=/etc/codex/conf/local.inc
fi
CODEX_UTILS_PREFIX=`/bin/grep '^\$codex_utils_prefix' $CODEX_LOCAL_INC | /bin/sed -e 's/\$codex_utils_prefix\s*=\s*\(.*\);\(.*\)/\1/' | tr -d '"' | tr -d "'"`
dump_dir=`/bin/grep '^\$dump_dir' $CODEX_LOCAL_INC | /bin/sed -e 's/\$dump_dir\s*=\s*\(.*\);\(.*\)/\1/' | tr -d '"' | tr -d "'"`
export dump_dir

# First the script that do the analysis
# of both the CodeX main site and the
# ftp andhttp downloads of CodeX project
# files
cd $CODEX_UTILS_PREFIX/download
./stats_logparse.sh $*

# Then the script that analyzes CVS history
# files and reshape them all in one single
# file for later analysis
cd $CODEX_UTILS_PREFIX/cvs1
./cvs_history_parse.pl $*

# Now make all the stat internal to the
# CodeX DB
cd $CODEX_UTILS_PREFIX/underworld-root
./stats_nightly.sh $*


# Then insert the per project Web page
# views (subdomain views)in the stats_project table
cd $CODEX_UTILS_PREFIX/projects-fileserver
./stats_projects_logparse.pl $*

