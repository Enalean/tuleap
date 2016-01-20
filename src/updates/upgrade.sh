#!/bin/sh
#
# Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
# This file is licensed under the GNU General Public License version 2. See the file COPYING.
#
# Purpose:
#    Launcher for Codendi Upgrade
#

if [ $1 ]
then
    php -q upgrade.php $1
else
    echo "Missing argument : script name";
    echo "Usage: upgrade.sh [script name]";
    exit 2;
fi
