#!/bin/sh
#
# CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
# Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001-2004. All Rights Reserved
# This file is licensed under the CodeX Component Software License
# http://codex.xerox.com
#
# Purpose:
#    How to compile the fileforge file. This script must be executed from root account !!
#
gcc fileforge.c -o fileforge
chown root.root fileforge
chmod u+s fileforge
mv fileforge /usr/local/bin

