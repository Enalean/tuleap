#!/usr/bin/perl
#
# CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
# Copyright (c) Xerox Corporation, CodeX, 2001-2006. All Rights Reserved
# This file is licensed under the GNU General Public License
#
# $Id$
#
# Purpose:
#    Automatically fix SELinux contexts to allow proper access to Apache and MySAL
#

require("include.pl");  # Include all the predefined functions and variables
$CHCON='/usr/bin/chcon';
$context="root:object_r:httpd_sys_content_t";

# /usr/share/codex -> CodeX main Web tree, documentation, plugins, etc.
`$CHCON -R -h $context $codex_dir`;

# /etc/codex -> for licence, site-content...
`$CHCON -R -h $context $sys_custom_dir`;

# /var/lib/codex -> for ftp, etc.
`$CHCON -R -h $context $sys_data_dir`;

# /home/codexadm. Apache needs access to '.subversion' (Server update plugin), '.cvs' (Passerelle plugin)
`$CHCON -R -h $context /home/$sys_http_user`;

# /home/groups -> project web sites
`$CHCON -R -h $context $grpdir_prefix`;

`$CHCON -h $context /svnroot`;
`$CHCON -h $context /cvsroot`;
