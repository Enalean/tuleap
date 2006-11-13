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

# /usr/share/codex -> CodeX main Web tree, documentation, plugins, etc.
`$CHCON -R -h -t httpd_sys_content_t $codex_dir`;

# /etc/codex -> for licence, site-content...
`$CHCON -R -h -t httpd_sys_content_t $sys_custom_dir`;

# /var/lib/codex -> for ftp, etc.
`$CHCON -R -h -t httpd_sys_content_t $sys_data_dir`;

# /home/codexadm/.subversion -> SVN needs access to codexadm subversion settings
`$CHCON -R -h -t httpd_sys_content_t /home/$sys_http_user/.subversion`;

# /home/groups -> project web sites
`$CHCON -R -h -t httpd_sys_content_t $grpdir_prefix`;

`$CHCON -h -t httpd_sys_content_t /svnroot`;
`$CHCON -h -t httpd_sys_content_t /cvsroot`;
