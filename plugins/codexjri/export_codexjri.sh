#!/bin/sh
#
# CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
# Copyright (c) Xerox Corporation, CodeX, 2001-2004. All Rights Reserved
# This file is licensed under the CodeX Component Software License
# http://codex.xerox.com
#
# Purpose:
#    Automatically copy CodeX JRI into CodeX plugin
#

CODEXJRI_SOURCE_REPOSITORY="https://partners.xrce.xerox.com/svnroot/codexjri/dev/trunk"

CODEX_TARGET_DIR="/home/mnazaria/CodeX/dev_server/httpd"

#
# CodeXJRI sources
#
svn export --force $CODEXJRI_SOURCE_REPOSITORY/ $CODEX_TARGET_DIR/plugins/codexjri/www/sources/.

echo "##############################################"
echo "  Don't forget to commit the modifications !  "
echo "##############################################"
