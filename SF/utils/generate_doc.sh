#!/bin/sh
#
# CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
# Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001. All Rights Reserved# http://codex.xerox.com
#
# $Id$
#
#  License:
#    This file is subject to the terms and conditions of the GNU General Public
#    license. See the file COPYING in the main directory of this archive for
#    more details.
#
# Purpose:
#    Automatically re-generate online documentation
#
CURRENTDIR=`pwd`
BASEDIR="/home/httpd/documentation"
CMDDIR=$BASEDIR/user_guide/cmd
cd $BASEDIR/user_guide/xml/en_US
# check if some need some update
COUNT=`cvs -q -d:pserver:sbouhet@cvs.codex.codex.xerox.com:/cvsroot/codex update | wc -l`
if [ $COUNT == 0 ]
then
        # No changes in the documentation
        exit 0
fi
$CMDDIR/xml2html.sh CodeX_User_Guide.xml ../../html/en_US/ 2>1 >/tmp/log_xml2html_$$
if [ $? != 0 ]
then
        echo "CodeX documentation generation failed!"
	echo "See error log below:"
	echo ""
	cat /tmp/log_xml2html_$$
        exit 1
fi
$CMDDIR/xml2pdf.sh CodeX_User_Guide.xml ../../pdf/en_US/CodeX_User_Guide_new.pdf 2>1 >/tmp/log_xml2pdf_$$
if [ $? != 0 ]
then
        echo "CodeX documentation generation failed!"
	echo "See error log below:"
        echo ""
        cat /tmp/log_xml2pdf_$$
        exit 1
fi
cd $BASEDIR/user_guide/pdf/en_US
cp -f CodeX_User_Guide.pdf CodeX_User_Guide_old.pdf
mv CodeX_User_Guide_new.pdf CodeX_User_Guide.pdf
cd "$CURRENTDIR"
exit 0
