#!/bin/sh
#
# Tuleap
# Copyright (c) Enalean, 2019-Present. All Rights Reserved.
# Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
# http://www.codendi.com
#
# 
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
if [ -z "$CODENDI_LOCAL_INC" ]; then 
    CODENDI_LOCAL_INC=/etc/tuleap/conf/local.inc
fi
CODENDI_UTILS_PREFIX=`/bin/grep '^\$codendi_utils_prefix' $CODENDI_LOCAL_INC | /bin/sed -e 's/\$codendi_utils_prefix\s*=\s*\(.*\);\(.*\)/\1/' | tr -d '"' | tr -d "'"`
dump_dir=`/bin/grep '^\$dump_dir' $CODENDI_LOCAL_INC | /bin/sed -e 's/\$dump_dir\s*=\s*\(.*\);\(.*\)/\1/' | tr -d '"' | tr -d "'"`
export dump_dir

# First the script that do the analysis
# of both the Codendi main site and the
# ftp andhttp downloads of Codendi project
# files
cd $CODENDI_UTILS_PREFIX/download
./stats_logparse.sh $*

# Then the script that analyzes CVS history
# files and reshape them all in one single
# file for later analysis
cd $CODENDI_UTILS_PREFIX/cvs1
./cvs_history_parse.pl $*

# Now make all the stat internal to the
# Codendi DB
cd $CODENDI_UTILS_PREFIX/underworld-root
./stats_nightly.sh $*


# Then insert the per project Web page
# views (subdomain views)in the stats_project table
cd $CODENDI_UTILS_PREFIX/projects-fileserver
./stats_projects_logparse.pl $*

