#!/bin/sh
#
# CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
# Copyright (c) Xerox Corporation, CodeX, 2001-2004. All Rights Reserved
# This file is licensed under the CodeX Component Software License
# http://codex.xerox.com
#
# $Id$
#
# Purpose:
#    Chain all the stats scripts that have to be run daily
#

UTILSHOME="/home/httpd/SF/utils"

# First the script that do the analysis
# of both the CodeX main site and the
# ftp andhttp downloads of CodeX project
# files
cd $UTILSHOME/download
./stats_logparse.sh $*

# Then the script that analyzes CVS history
# files and reshape them all in one single
# file for later analysis
cd $UTILSHOME/cvs1
./cvs_history_parse.pl $*

# Now make all the stat internal to the
# CodeX DB
cd $UTILSHOME/underworld-root
./stats_nightly.sh $*


# Then insert the per project Web page
# views (subdomain views)in the stats_project table
cd $UTILSHOME/projects-fileserver
./stats_projects_logparse.pl $*

