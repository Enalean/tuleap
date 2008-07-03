#!/bin/sh
#
# Copyright (c) Xerox Corporation, CodeX, Codendi 2007-2008.
# This file is licensed under the GNU General Public License version 2. See the file COPYING.
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
