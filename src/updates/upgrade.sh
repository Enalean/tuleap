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
#    Launcher for CodeX Upgrade
#

if [ $1 ]
then
    php -q upgrade.php $1
else
    echo "Missing argument : script name";
    echo "Usage: upgrade.sh [script name]";
    exit 2;
fi
