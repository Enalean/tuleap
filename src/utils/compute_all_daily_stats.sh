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

CODENDI_UTILS_PREFIX="/usr/share/tuleap/src/utils"
dump_dir=`/usr/bin/tuleap config-get dump_dir`
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
