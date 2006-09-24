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
$MySQLBackupDir="/var/lib/codex/backup/mysql";

# /usr/share/codex -> CodeX main Web tree, documentation, plugins, etc.
`chcon -R -h -t httpd_sys_content_t $codex_dir`;

# /etc/codex -> for licence, site-content...
`chcon -R -h -t httpd_sys_content_t $sys_custom_dir`;

# /var/lib/codex -> for ftp, etc.
`chcon -R -h -t httpd_sys_content_t $sys_data_dir`;

# /var/lib/codex/backup/mysql -> for MySQL bin log files 
# Note: this should be set AFTER $sys_data_dir, because it is a subdirectory
`chcon -R -h -t mysqld_var_run_t $MySQLBackupDir`;

# /home/codexadm/.subversion -> SVN needs access to codexadm subversion settings
`chcon -R -h -t httpd_sys_content_t /home/$sys_http_user/.subversion`;

# /home/groups -> project web sites
`chcon -R -h -t httpd_sys_content_t $grpdir_prefix`;

#chcon -h -t httpd_sys_content_t /svnroot
