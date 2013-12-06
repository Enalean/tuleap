#!/usr/bin/perl
# 
# Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
#
# This file is a part of Codendi.
#
# Codendi is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# Codendi is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with Codendi. If not, see <http://www.gnu.org/licenses/>.
#


# Purpose:
#    Automatically fix SELinux contexts to allow proper access to Apache and MySAL
#

$INSTALL_DIR="/usr/share/codendi";
require("$INSTALL_DIR/src/utils/include.pl");  # Include all the predefined functions and variables
$CHCON='/usr/bin/chcon';
$SEMODULE='/usr/sbin/semodule';

# Check OS Version
my $os="";
my $os_version=-1;
my $lsb_release = `which lsb_release`;
chop $lsb_release;
if (-e "$lsb_release" && `$lsb_release -s -i` =~ /(centos|redhatenterprise)/i) {
    $os="rhel";
    $os_version = `lsb_release -s -r`;
    chop $os_version;
}

if ($os != "rhel") {
    print "Unknow OS, abort (lsb_release not installed ?)\n";
    exit;
}

my $httpd_context = "root:object_r:httpd_sys_content_t:s0";
my $ftpro_context = "system_u:object_r:public_content_t:s0";
my $ftprw_context = "system_u:object_r:public_content_rw_t:s0";
my $cvs_context   = "system_u:object_r:cvs_data_t:s0";
if ($os_version < 6) {
    $httpd_context = "root:object_r:httpd_sys_content_t";
    $ftpro_context = "system_u:object_r:public_content_t";
    $ftprw_context = "system_u:object_r:public_content_rw_t";
    $cvs_context   = "system_u:object_r:cvs_data_t";
}

if (( ! -e $CHCON ) || ( ! -e "/etc/selinux/config" ) || ( `grep -i '^SELINUX=disabled' /etc/selinux/config`)) {
   # SELinux not installed or disabled: nothing to do
   exit;
}

# /usr/share/codendi -> codendi main Web tree, documentation, plugins, etc.
`$CHCON -R -h $httpd_context $codendi_dir`;

# /etc/codendi -> for licence, site-content...
`$CHCON -R -h $httpd_context $sys_custom_dir`;

# /var/lib/codendi
`$CHCON -R -h $httpd_context $sys_data_dir`;

# FTP directories
`$CHCON -R -h $ftpro_context $sys_data_dir/ftp`;
`$CHCON -R -h $ftprw_context  $sys_data_dir/ftp/incoming`;
# Releases must be accessed from httpd
`$CHCON -R -h $httpd_context $sys_data_dir/ftp/codendi`;

# Allow anonymous FTP writes
`setsebool -P allow_ftpd_anon_write=1`;
# Allow access to user's home with FTP
`setsebool -P ftp_home_dir 1`;

# /home/codendiadm. Apache needs access to '.subversion' (Server update plugin), '.cvs' (Passerelle plugin)
`$CHCON -R -h $httpd_context /home/$sys_http_user`;

# /home/groups -> project web sites
`$CHCON -R -h $httpd_context $grpdir_prefix`;

`$CHCON -h $httpd_context /svnroot`;
`$CHCON -h $cvs_context /cvsroot`;
`$CHCON -R -h $cvs_context /cvsroot/`;

# Allow scripts to connect to the internet (e.g. for external RSS feeds)
`setsebool -P httpd_can_network_connect=1`;

# Relax restriction on NSCD (need to access MySQL)
`setsebool -P nscd_disable_trans 1`;

# Reload SELinux modules
opendir(DIR, "$INSTALL_DIR/tools/selinux") || die "Can't opendir SELinux modules: $!";
while($file=readdir(DIR)) {
  if ($file=~/\.pp$/) {
      `$SEMODULE -i $INSTALL_DIR/tools/selinux/$file`;
  }
}
closedir DIR;

