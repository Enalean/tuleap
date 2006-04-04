#!/bin/bash
#
# CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
# Copyright (c) Xerox Corporation, CodeX/CodeX Team, 2004. All Rights Reserved
# This file is licensed under the CodeX Component Software License
# http://codex.xerox.com
#
# THIS FILE IS THE PROPERTY OF XEROX AND IS ONLY DISTRIBUTED WITH A
# COMMERCIAL LICENSE OF CODEX. IT IS *NOT* DISTRIBUTED UNDER THE GNU
# PUBLIC LICENSE.
#
#  $Id: migration_24.sh 1776 2005-06-23 14:29:05Z guerin $
#
#      Originally written by Laurent Julliard 2004-2006, CodeX Team, Xerox
#
#  This file is part of the CodeX software and must be placed at the same
#  level as the CodeX, RPMS_CodeX and nonRPMS_CodeX directory when
#  delivered on a CD or by other means
#
#  This script migrates a site running CodeX 2.6 to CodeX 2.8
#


progname=$0
if [ -z "$scriptdir" ]; then 
    scriptdir=`dirname $progname`
fi
cd ${scriptdir};TOP_DIR=`pwd`;cd -
RPMS_DIR=${TOP_DIR}/RPMS_CodeX
nonRPMS_DIR=${TOP_DIR}/nonRPMS_CodeX
CodeX_DIR=${TOP_DIR}/CodeX
TODO_FILE=/tmp/todo_codex.txt
CODEX_TOPDIRS="SF site-content documentation cgi-bin codex_tools"
INSTALL_DIR="/home/httpd"

# path to command line tools
GROUPADD='/usr/sbin/groupadd'
GROUPDEL='/usr/sbin/groupdel'
USERADD='/usr/sbin/useradd'
USERDEL='/usr/sbin/userdel'
USERMOD='/usr/sbin/usermod'
MV='/bin/mv'
CP='/bin/cp'
LN='/bin/ln'
LS='/bin/ls'
RM='/bin/rm'
TAR='/bin/tar'
MKDIR='/bin/mkdir'
RPM='/bin/rpm'
CHOWN='/bin/chown'
CHMOD='/bin/chmod'
FIND='/usr/bin/find'
MYSQL='/usr/bin/mysql'
TOUCH='/bin/touch'
CAT='/bin/cat'
MAKE='/usr/bin/make'
TAIL='/usr/bin/tail'
GREP='/bin/grep'
CHKCONFIG='/sbin/chkconfig'
SERVICE='/sbin/service'
PERL='/usr/bin/perl'

CMD_LIST="GROUPADD GROUDEL USERADD USERDEL USERMOD MV CP LN LS RM TAR \
MKDIR RPM CHOWN CHMOD FIND TOUCH CAT MAKE TAIL GREP CHKCONFIG \
SERVICE PERL"

# Functions
create_group() {
    # $1: groupname, $2: groupid
    $GROUPDEL "$1" 2>/dev/null
    $GROUPADD -g "$2" "$1"
}

build_dir() {
    # $1: dir path, $2: user, $3: group, $4: permission
    $MKDIR -p "$1" 2>/dev/null; $CHOWN "$2.$3" "$1";$CHMOD "$4" "$1";
}

make_backup() {
    # $1: file name, $2: extension for old file (optional)
    file="$1"
    ext="$2"
    if [ -z $ext ]; then
	ext="nocodex"
    fi
    backup_file="$1.$ext"
    [ -e "$file" -a ! -e "$backup_file" ] && $CP "$file" "$backup_file"
}

todo() {
    # $1: message to log in the todo file
    echo -e "- $1" >> $TODO_FILE
}

die() {
  # $1: message to prompt before exiting
  echo -e "**ERROR** $1"; exit 1
}

substitute() {
  # $1: filename, $2: string to match, $3: replacement string
  $PERL -pi -e "s/$2/$3/g" $1
}

##############################################
# CodeX 2.6 to 2.8 migration
##############################################
echo "Migration script from CodeX 2.6 to CodeX 2.8"
echo

##############################################
# Check the machine is running CodeX 2.6
#
OLD_CX_RELEASE='2.6'
yn="y"
$GREP -q "$OLD_CX_RELEASE" $INSTALL_DIR/SF/www/VERSION
if [ $? -ne 0 ]; then
    $CAT <<EOF
This machine does not have CodeX ${OLD_CX_RELEASE} installed. Executing this install
script may cause data loss or corruption.
EOF
read -p "Continue? [yn]: " yn
else
    echo "Found CodeX ${OLD_CX_RELEASE} installed... good!"
fi

if [ "$yn" = "n" ]; then
    echo "Bye now!"
    exit 1
fi

##############################################
# Check that all command line tools we need are available
#
for cmd in `echo ${CMD_LIST}`
do
    [ ! -x ${!cmd} ] && die "Command line tool '${!cmd}' not available. Stopping installation!"
done

##############################################
# Warn user about upgrade
#

yn="y"
$CAT <<EOF
This script will reinstall some configuration files:
/etc/httpd/conf/httpd.conf
/etc/httpd/conf.d/php.conf
If you have customized these files for your special needs, you should backup them before starting the migration.

EOF
read -p "Continue? [yn]: " yn

if [ "$yn" = "n" ]; then
    echo "Bye now!"
    exit 1
fi

##############################################
# Check we are running on RHEL 3
#
RH_RELEASE="3"
yn="y"
$RPM -q redhat-release-${RH_RELEASE}* 2>/dev/null 1>&2
if [ $? -eq 1 ]; then
    cat <<EOF
This machine is not running RedHat Enterprise Linux ${RH_RELEASE}. Executing this install
script may cause data loss or corruption.
EOF
read -p "Continue? [yn]: " yn
else
    echo "Running on RedHat Enterprise Linux ${RH_RELEASE}... good!"
fi

if [ "$yn" = "n" ]; then
    echo "Bye now!"
    exit 1
fi

$RM -f $TODO_FILE
todo "WHAT TO DO TO FINISH THE CODEX MIGRATION (see $TODO_FILE)"

##############################################
# Stop some services before upgrading
#
echo "Stopping crond, apache and httpd, sendmail, and postfix ..."
$SERVICE crond stop
$SERVICE apache stop
$SERVICE httpd stop
$SERVICE mysql stop
$SERVICE sendmail stop
$SERVICE postfix stop
$SERVICE mailman stop


##############################################
# Check Required Stock RedHat RPMs are installed
# perl libwww is required for log_accum (reference extraction...)

rpms_ok=1
for rpm in perl-URI perl-HTML-Tagset \
   perl-HTML-Parser perl-libwww-perl
do
    $RPM -q $rpm  2>/dev/null 1>&2
    if [ $? -eq 1 ]; then
	rpms_ok=0
	missing_rpms="$missing_rpms $rpm"
    fi
done
if [ $rpms_ok -eq 0 ]; then
    msg="The following Redhat Linux RPMs must be installed first:\n"
    msg="${msg}$missing_rpms\n"
    msg="${msg}Get them from your Redhat CDROM or FTP site, install them and re-run the migration script"
    die "$msg"
fi
echo "All requested RedHat RPMS installed... good!"

##############################################
# Ask for domain name and other installation parameters
#
sys_default_domain=`grep ServerName /etc/httpd/conf/httpd.conf | grep -v '#' | head -1 | cut -d " " -f 2 ;`
if [ -z $sys_default_domain ]; then
  read -p "CodeX Domain name: " sys_default_domain
fi
sys_ip_address=`grep NameVirtualHost /etc/httpd/conf/httpd.conf | grep -v '#' | cut -d " " -f 2 | cut -d ":" -f 1`
if [ -z $sys_ip_address ]; then
  read -p "Codex Server IP address: " sys_ip_address
fi

##############################################

##############################################
# Update local.inc  
#

make_backup /etc/codex/conf/local.inc codex26

$PERL -i'.orig' -p -e's:^(\$sys_show_project_type.*)://\1 DEPRECATED in CodeX 2.8:' /etc/codex/conf/local.inc

$GREP -q "sys_server_join" /etc/codex/conf/local.inc
if [ $? -ne 0 ]; then
   # Not a maintained 2.6 release...
   $PERL -i'.orig2' -p -e's:^(\$sys_server.*):\1\n\$sys_server_join="30";\n:' /etc/codex/conf/local.inc
fi

$PERL -i'.orig3' -p -e's:(sys_pluginsroot.*):\1\n\$sys_custompluginsroot ="/etc/codex/plugins/";\n\$sys_pluginspath="/plugins";\n\$sys_custompluginspath ="/customplugins";:' /etc/codex/conf/local.inc

$PERL -i'.orig4' -p -e's:(sys_email_contact.*):\1\n\n\/\/\n\/\/ Address from which emails are sent\n\$sys_noreply = @@"CodeX" <noreply@%sys_default_domain%>@@;\n:' /etc/codex/conf/local.inc
# This is just because we have pbs using quotes in the perl command
substitute '/etc/codex/conf/local.inc' '@@' "'" 
substitute '/etc/codex/conf/local.inc' '%sys_default_domain%' "$sys_default_domain" 

$PERL -i'.orig5' -p -e's:(sys_session_lifetime.*):\1\n\n\/\/\n\/\/ Is license approval mandatory when downloading a file from the FRS?\n\/\/ (1 is mandatory, 0 is optional)\n\$sys_frs_license_mandatory = 1;\n:' /etc/codex/conf/local.inc


##############################################
# Now install CodeX specific RPMS (and remove RedHat RPMs)
#

# Nothing new in CodeX 2.8



##############################################
# Update the CodeX software

echo "Installing the CodeX software..."
cd /home
$MV httpd httpd_26
$MKDIR httpd;
cd httpd
$TAR xfz ${CodeX_DIR}/codex*.tgz
$CHOWN -R sourceforge.sourceforge $INSTALL_DIR

# copy some configuration files 
make_backup /etc/httpd/conf/httpd.conf codex26
make_backup /etc/httpd/conf.d/php.conf codex26
$CP $INSTALL_DIR/SF/etc/httpd.conf.dist /etc/httpd/conf/httpd.conf
$CP $INSTALL_DIR/SF/etc/php.conf.dist /etc/httpd/conf.d/php.conf
$CP $INSTALL_DIR/SF/etc/ssl.conf.dist /etc/httpd/conf.d/ssl.conf
$CP $INSTALL_DIR/SF/etc/codex_aliases.conf.dist /etc/httpd/conf/codex_aliases.conf

# replace string patterns in httpd.conf
substitute '/etc/httpd/conf/httpd.conf' '%sys_default_domain%' "$sys_default_domain"
substitute '/etc/httpd/conf/httpd.conf' '%sys_ip_address%' "$sys_ip_address"
# replace string patterns in ssl.conf
substitute '/etc/httpd/conf.d/ssl.conf' '%sys_default_domain%' "$sys_default_domain"
substitute '/etc/httpd/conf.d/ssl.conf' '%sys_ip_address%' "$sys_ip_address"

todo "Edit the new /etc/httpd/conf/httpd.conf file and update it if needed"
todo "Edit the new /etc/httpd/conf.d/php.conf file and update it if needed"
todo "Edit the new /etc/httpd/conf.d/ssl.conf file and update it if needed"
todo "Edit the new /etc/httpd/conf/codex_aliases.conf file and update it if needed"

# Re-copy phpMyAdmin and viewcvs installations
$CP -af /home/httpd_26/phpMyAdmin* /home/httpd
$CP -af /home/httpd_26/cgi-bin/viewcvs.cgi /home/httpd/cgi-bin



##############################################
# French documentation
echo "Preparing directories for French documentation"
$CHOWN -R sourceforge.sourceforge /etc/codex/documentation
$MKDIR -p  $INSTALL_DIR/documentation/user_guide/html/fr_FR
$CHOWN -R sourceforge.sourceforge $INSTALL_DIR/documentation/user_guide/html/fr_FR
$MKDIR -p  $INSTALL_DIR/documentation/user_guide/pdf/fr_FR
$CHOWN -R sourceforge.sourceforge $INSTALL_DIR/documentation/user_guide/pdf/fr_FR

# Moved local documentation parameters
if [ -f "/etc/codex/documentation/user_guide/xml/en_US/ParametersLocal.dtd" ]; then
    $MV /etc/codex/documentation/user_guide/xml/en_US/ParametersLocal.dtd /etc/codex/documentation/user_guide/xml/ParametersLocal.dtd
else
    $CP $INSTALL_DIR/SF/etc/ParametersLocal.dtd.dist /etc/codex/documentation/user_guide/xml/ParametersLocal.dtd
    # replace string patterns in ParametersLocal.dtd
    substitute '/etc/codex/documentation/user_guide/xml/ParametersLocal.dtd' '%sys_default_domain%' "$sys_default_domain" 
    substitute '/etc/codex/documentation/user_guide/xml/ParametersLocal.dtd' '%sys_org_name%' "Xerox" 
    substitute '/etc/codex/documentation/user_guide/xml/ParametersLocal.dtd' '%sys_long_org_name%' "Xerox Corporation" 
    substitute '/etc/codex/documentation/user_guide/xml/ParametersLocal.dtd' '%sys_win_domain%' " "
    todo "Customize /etc/codex/documentation/user_guide/xml/ParametersLocal.dtd"
fi

##############################################
# Database Structure and initvalues upgrade
#
echo "Updating the CodeX database..."

$SERVICE mysql start
sleep 5

pass_opt=""
# See if MySQL root account is password protected
mysqlshow 2>&1 | grep password
while [ $? -eq 0 ]; do
    read -s -p "Existing CodeX DB is password protected. What is the Mysql root password?: " old_passwd
    echo
    mysqlshow --password=$old_passwd 2>&1 | grep password
done
[ "X$old_passwd" != "X" ] && pass_opt="--password=$old_passwd"

echo "Starting DB update for CodeX 2.8. This might take a few minutes."

################################################################################
echo " Upgrading 2.6 if needed"
$PERL <<'EOF'

use DBI;
use Sys::Hostname;
use Carp;

require "/home/httpd/SF/utils/include.pl";  # Include all the predefined functions

&load_local_config();

&db_connect;

$query = "SELECT ugroup_id FROM permissions_values WHERE permission_type = 'WIKI_READ' AND ugroup_id = 1";
$c = $dbh->prepare($query);
$c->execute();
if (my ($ugroup_id) = $c->fetchrow()) {
    #Do nothing
} else {
    $query = "INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('WIKI_READ',1)";
    $c = $dbh->prepare($query);
    $c->execute();
}

$query = "SELECT ugroup_id FROM permissions_values WHERE permission_type = 'WIKIPAGE_READ' AND ugroup_id = 1";
$c = $dbh->prepare($query);
$c->execute();
if (my ($ugroup_id) = $c->fetchrow()) {
    #Do nothing
} else {
    $query = "INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('WIKIPAGE_READ',1)";
    $c = $dbh->prepare($query);
    $c->execute();
}

$query = "SELECT ugroup_id FROM permissions_values WHERE permission_type = 'WIKIATTACHMENT_READ' AND ugroup_id = 1";
$c = $dbh->prepare($query);
$c->execute();
if (my ($ugroup_id) = $c->fetchrow()) {
    #Do nothing
} else {
    $query = "INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('WIKIATTACHMENT_READ',1)";
    $c = $dbh->prepare($query);
    $c->execute();
}

$query = "DELETE FROM permissions_values WHERE permission_type = 'TRACKER_ACCESS_SUBMITTER' AND ugroup_id = '2'";
$c = $dbh->prepare($query);
$c->execute();
$query = "DELETE FROM permissions_values WHERE permission_type = 'TRACKER_ACCESS_ASSIGNEE' AND ugroup_id = '2'";
$c = $dbh->prepare($query);
$c->execute();

$query = "SELECT ugroup_id FROM permissions WHERE ugroup_id = 2 AND permission_type = 'TRACKER_ACCESS_ASSIGNEE' OR permission_type = 'TRACKER_ACCESS_SUBMITTER'";
$c = $dbh->prepare($query);
$c->execute();
if (my ($ugroup_id) = $c->fetchrow()) {
    $query = "INSERT INTO permissions(object_id, ugroup_id, permission_type) SELECT DISTINCT object_id, 2, 'TRACKER_ACCESS_FULL' FROM permissions WHERE ugroup_id = 2 AND permission_type = 'TRACKER_ACCESS_ASSIGNEE' OR permission_type = 'TRACKER_ACCESS_SUBMITTER'";
    $c = $dbh->prepare($query);
    $c->execute();
    
    $query = "DELETE FROM permissions WHERE ugroup_id = 2 AND permission_type = 'TRACKER_ACCESS_ASSIGNEE' OR permission_type = 'TRACKER_ACCESS_SUBMITTER'";
    $c = $dbh->prepare($query);
    $c->execute();
}
EOF

echo " DB - plugin update"
$CAT <<EOF | $MYSQL $pass_opt sourceforge


###############################################################################
#
#
ALTER TABLE plugin CHANGE enabled available TINYINT( 4 ) DEFAULT '0' NOT NULL;

EOF


echo " DB - LDAP update"
$CAT <<EOF | $MYSQL $pass_opt sourceforge


###############################################################################
#
# LDAP

ALTER TABLE user ADD ldap_id TEXT AFTER ldap_name;
ALTER TABLE user DROP COLUMN ldap_name;

EOF

echo " DB - FRS update"
$CAT <<EOF | $MYSQL $pass_opt sourceforge


###############################################################################
# Add approve_licence row in frs_package table

ALTER TABLE frs_package ADD approve_license TINYINT(1) NOT NULL default '1';
EOF

echo " DB - Field Dependencies update"
$CAT <<EOF | $MYSQL $pass_opt sourceforge


###############################################################################
# DynamicFields: create tables
#
DROP TABLE IF EXISTS artifact_rule;
CREATE TABLE artifact_rule (
  id int(11) unsigned NOT NULL auto_increment,
  group_artifact_id int(11) unsigned NOT NULL default '0',
  source_field_id int(11) unsigned NOT NULL default '0',
  source_value_id int(11) unsigned NOT NULL default '0',
  target_field_id int(11) unsigned NOT NULL default '0',
  rule_type tinyint(4) unsigned NOT NULL default '0',
  target_value_id int(11) unsigned default NULL,
  PRIMARY KEY  (id),
  KEY group_artifact_id (group_artifact_id)
);


EOF
todo "  "
todo "                 *** IF YOU HAVE A CUSTOM THEME *** "
todo "Fields Dependencies use an optional css rule for highlighting. "
todo "  Default color is yellow. You can customize it for your theme by overiding "
todo "  this css rule in your stylesheets. Please have a look at the end of "
todo "  CodeX stylesheets for an example."
todo "Fields Dependencies need javascript files to be updated. Perhaps you have to "
todo "  modify your Theme.class in order to handle the inclusion of those files."
todo "  This can only happen if you have overiden the method generic_header_start() "
todo "  or if you have overiden the method header() and you don't call "
todo "  generic_header_start. Locates the line \$em->processEvent(\"javascript_file\", null);\" "
todo "  and add below this line the following snippet: "
todo "  "
todo "          foreach (\$this->javascript_files as \$file) { "
todo "              echo '<script type=\"text/javascript\" src=\"'. \$file .'\"></script>'.\"\\\n\"; "
todo "          } "
todo "  "


echo " DB - Reference patterns update"
$CAT <<EOF | $MYSQL $pass_opt sourceforge


###############################################################################
#
#
#
# Table structure for table 'reference'
#
# Notes: 
# - scope='S' means a reference available to all projects
# (defined by CodeX Site administrators, group_id =100)
# - scope='P' means a reference available to all project pages
#
CREATE TABLE reference (
  id int(11) NOT NULL auto_increment,
  keyword varchar(25) NOT NULL,
  description text NOT NULL,
  link text NOT NULL,
  scope char(1) NOT NULL default 'P',
  service_short_name TEXT,
  PRIMARY KEY  (id),
  INDEX keyword_idx (keyword),
  INDEX scope_idx (scope)
);

CREATE TABLE reference_group (
  id int(11) NOT NULL auto_increment,
  reference_id int(11) NOT NULL default '0',
  group_id int(11) NOT NULL default '0',
  is_active tinyint NOT NULL default '0',
  PRIMARY KEY  (id),
  INDEX group_id_idx (group_id,is_active)
);

INSERT INTO reference SET \
    id='1',        \
    keyword='art', \
    description='reference_art_desc_key', \
    link='/tracker/?func=detail&aid=\$1&group_id=\$group_id', \
    scope='S', \
    service_short_name='tracker';

INSERT INTO reference SET \
    id='2',        \
    keyword='artifact', \
    description='reference_art_desc_key', \
    link='/tracker/?func=detail&aid=\$1&group_id=\$group_id', \
    scope='S', \
    service_short_name='tracker';

INSERT INTO reference SET \
    id='3',        \
    keyword='commit', \
    description='reference_cvs_desc_key', \
    link='/cvs/?func=detailcommit&commit_id=\$1&group_id=\$group_id', \
    scope='S', \
    service_short_name='cvs';

INSERT INTO reference SET \
    id='4',        \
    keyword='cvs', \
    description='reference_cvs_desc_key', \
    link='/cvs/?func=detailcommit&commit_id=\$1&group_id=\$group_id', \
    scope='S', \
    service_short_name='cvs';

INSERT INTO reference SET \
    id='5',        \
    keyword='rev', \
    description='reference_svn_desc_key', \
    link='/svn/?func=detailrevision&rev_id=\$1&group_id=\$group_id', \
    scope='S', \
    service_short_name='svn';

INSERT INTO reference SET \
    id='6',        \
    keyword='revision', \
    description='reference_svn_desc_key', \
    link='/svn/?func=detailrevision&rev_id=\$1&group_id=\$group_id', \
    scope='S', \
    service_short_name='svn';

INSERT INTO reference SET \
    id='7',        \
    keyword='svn', \
    description='reference_svn_desc_key', \
    link='/svn/?func=detailrevision&rev_id=\$1&group_id=\$group_id', \
    scope='S', \
    service_short_name='svn';

INSERT INTO reference SET \
    id='8',        \
    keyword='wiki', \
    description='reference_wiki_desc_key', \
    link='/wiki/index.php?group_id=\$group_id&pagename=\$1', \
    scope='S', \
    service_short_name='wiki';

INSERT INTO reference SET \
    id='9',        \
    keyword='wiki', \
    description='reference_wikiversion_desc_key', \
    link='/wiki/index.php?group_id=\$group_id&pagename=\$1&version=\$2', \
    scope='S', \
    service_short_name='wiki';

INSERT INTO reference SET \
    id='10',        \
    keyword='doc', \
    description='reference_doc_desc_key', \
    link='/docman/display_doc.php?docid=\$1&group_id=\$group_id', \
    scope='S', \
    service_short_name='doc';

INSERT INTO reference SET \
    id='11',        \
    keyword='document', \
    description='reference_doc_desc_key', \
    link='/docman/display_doc.php?docid=\$1&group_id=\$group_id', \
    scope='S', \
    service_short_name='doc';

INSERT INTO reference SET \
    id='12',        \
    keyword='news', \
    description='reference_news_desc_key', \
    link='/forum/forum.php?forum_id=\$1', \
    scope='S', \
    service_short_name='news';

INSERT INTO reference SET \
    id='13',        \
    keyword='forum', \
    description='reference_forum_desc_key', \
    link='/forum/forum.php?forum_id=\$1', \
    scope='S', \
    service_short_name='forum';

INSERT INTO reference SET \
    id='14',        \
    keyword='msg', \
    description='reference_msg_desc_key', \
    link='/forum/message.php?msg_id=\$1', \
    scope='S', \
    service_short_name='forum';

INSERT INTO reference SET \
    id='15',        \
    keyword='file', \
    description='reference_file_desc_key', \
    link='/file/confirm_download.php?group_id=\$group_id&file_id=\$1', \
    scope='S', \
    service_short_name='file';

INSERT INTO reference SET \
    id='16',        \
    keyword='release', \
    description='reference_release_desc_key', \
    link='/file/showfiles.php?group_id=\$group_id6&release_id=\$1', \
    scope='S', \
    service_short_name='file';

# Legacy references

INSERT INTO reference SET \
    id='90',        \
    keyword='bug', \
    description='reference_bug_desc_key', \
    link='/tracker/?func=gotoid&group_id=\$group_id&aid=\$1&atn=bug', \
    scope='S', \
    service_short_name='bugs';
INSERT INTO reference SET \
    id='91',        \
    keyword='task', \
    description='reference_task_desc_key', \
    link='/tracker/?func=gotoid&group_id=\$group_id&aid=\$1&atn=task', \
    scope='S', \
    service_short_name='task';
INSERT INTO reference SET \
    id='92',        \
    keyword='sr', \
    description='reference_sr_desc_key', \
    link='/tracker/?func=gotoid&group_id=\$group_id&aid=\$1&atn=sr', \
    scope='S', \
    service_short_name='support';
INSERT INTO reference SET \
    id='93',        \
    keyword='patch', \
    description='reference_patch_desc_key', \
    link='/tracker/?func=gotoid&group_id=\$group_id&aid=\$1&atn=patch', \
    scope='S', \
    service_short_name='patch';

# empty reference
INSERT INTO reference SET \
    id='100',        \
    keyword='', \
    description='Empty reference', \
    link='', \
    scope='S', \
    service_short_name='';

#
# Initial reference values for template project:
#
INSERT INTO reference_group SET reference_id='1', group_id='100', is_active='1';
INSERT INTO reference_group SET reference_id='2', group_id='100', is_active='1';
INSERT INTO reference_group SET reference_id='3', group_id='100', is_active='1';
INSERT INTO reference_group SET reference_id='4', group_id='100', is_active='1';
INSERT INTO reference_group SET reference_id='5', group_id='100', is_active='1';
INSERT INTO reference_group SET reference_id='6', group_id='100', is_active='1';
INSERT INTO reference_group SET reference_id='7', group_id='100', is_active='1';
INSERT INTO reference_group SET reference_id='8', group_id='100', is_active='1';
INSERT INTO reference_group SET reference_id='9', group_id='100', is_active='1';
INSERT INTO reference_group SET reference_id='10', group_id='100', is_active='1';
INSERT INTO reference_group SET reference_id='11', group_id='100', is_active='1';
INSERT INTO reference_group SET reference_id='12', group_id='100', is_active='1';
INSERT INTO reference_group SET reference_id='13', group_id='100', is_active='1';
INSERT INTO reference_group SET reference_id='14', group_id='100', is_active='1';
INSERT INTO reference_group SET reference_id='15', group_id='100', is_active='1';
INSERT INTO reference_group SET reference_id='90', group_id='100', is_active='0';
INSERT INTO reference_group SET reference_id='91', group_id='100', is_active='0';
INSERT INTO reference_group SET reference_id='92', group_id='100', is_active='0';
INSERT INTO reference_group SET reference_id='93', group_id='100', is_active='0';

EOF

echo " DB - drop svn_tracks and cvs_tracks tables"
$CAT <<EOF | $MYSQL $pass_opt sourceforge

DROP TABLE svn_tracks;
DROP TABLE cvs_tracks;

EOF

echo " DB - drop project_type table and project_type field (in groups table)"
$CAT <<EOF | $MYSQL $pass_opt sourceforge

ALTER TABLE groups DROP project_type;
DROP TABLE project_type;

EOF

echo " DB - artifact status update"
$CAT <<EOF | $MYSQL $pass_opt sourceforge




###############################################################################
# SR #282 on partners: simplify status field
#
-- create stage field for Bug, Tasks, SR and Empty tracker templates (Patch tracker already has the field response)
-- Use ID=60 for this new field to (hopefully) avoid overlap with existing fields
-- for patch tracker (id=5), replace 'response' by 'stage'

INSERT INTO artifact_field VALUES (60,1,'stage',2,'SB','','Stage','Stage in the life cycle of the artifact','',0,0,1,0,NULL,'1');
INSERT INTO artifact_field VALUES (60,2,'stage',2,'SB','','Stage','Stage in the life cycle of the artifact','',0,0,1,0,NULL,'1');
INSERT INTO artifact_field VALUES (60,3,'stage',2,'SB','','Stage','Stage in the life cycle of the artifact','',0,0,1,0,NULL,'1');
INSERT INTO artifact_field VALUES (60,4,'stage',2,'SB','','Stage','Stage in the life cycle of the artifact','',0,0,1,0,NULL,'1');

DELETE FROM artifact_field WHERE group_artifact_id = 5 AND field_id = 12;
INSERT INTO artifact_field VALUES (12,5,'stage',2,'SB','','Stage','Stage in the life cycle of the artifact','P',0,1,1,0,NULL,'1');

INSERT INTO artifact_field_usage VALUES (60,1,0,0);
INSERT INTO artifact_field_usage VALUES (60,2,0,0);
INSERT INTO artifact_field_usage VALUES (60,3,0,0);
INSERT INTO artifact_field_usage VALUES (60,4,0,0);

-- delete values for status field

DELETE FROM artifact_field_value_list WHERE group_artifact_id = 1 AND field_id = 2 AND status != 'P' AND value_id > 3;
DELETE FROM artifact_field_value_list WHERE group_artifact_id = 2 AND field_id = 11 AND status != 'P' AND value_id > 3;
DELETE FROM artifact_field_value_list WHERE group_artifact_id = 3 AND field_id = 7 AND status != 'P' AND value_id > 3;
DELETE FROM artifact_field_value_list WHERE group_artifact_id = 4 AND field_id = 6 AND status != 'P' AND value_id > 3;
DELETE FROM artifact_field_value_list WHERE group_artifact_id = 5 AND field_id = 9 AND status != 'P' AND value_id > 3;

-- create field values for new stage field

INSERT INTO artifact_field_value_list VALUES (60,1,1,'New','The artifact has just been submitted',20,'A');
INSERT INTO artifact_field_value_list VALUES (60,1,2,'Analyzed','The cause of the artifact has been identified and documented',30,'A');
INSERT INTO artifact_field_value_list VALUES (60,1,3,'Accepted','The artifact will be worked on.',40,'A');
INSERT INTO artifact_field_value_list VALUES (60,1,4,'Under Implementation','The artifact is being worked on.',50,'A');

INSERT INTO artifact_field_value_list VALUES (60,1,5,'Ready for Review','Updated/Created non-software work product (e.g. documentation) is ready for review and approval.',60,'A');
INSERT INTO artifact_field_value_list VALUES (60,1,6,'Ready for Test','Updated/Created software is ready to be included in the next build',70,'A');
INSERT INTO artifact_field_value_list VALUES (60,1,7,'In Test','Updated/Created software is in the build and is ready to enter the test phase',80,'A');
INSERT INTO artifact_field_value_list VALUES (60,1,8,'Approved','The artifact fix has been succesfully tested. It is approved and awaiting release.',90,'A');
INSERT INTO artifact_field_value_list VALUES (60,1,9,'Declined','The artifact was not accepted.',100,'A');
INSERT INTO artifact_field_value_list VALUES (60,1,10,'Done','The artifact is closed.',110,'A');

INSERT INTO artifact_field_value_list VALUES (60,2,1,'New','The artifact has just been submitted',20,'A');
INSERT INTO artifact_field_value_list VALUES (60,2,2,'Analyzed','The cause of the artifact has been identified and documented',30,'A');
INSERT INTO artifact_field_value_list VALUES (60,2,3,'Accepted','The artifact will be worked on.',40,'A');
INSERT INTO artifact_field_value_list VALUES (60,2,4,'Under Implementation','The artifact is being worked on.',50,'A');

INSERT INTO artifact_field_value_list VALUES (60,2,5,'Ready for Review','Updated/Created non-software work product (e.g. documentation) is ready for review and approval.',60,'A');
INSERT INTO artifact_field_value_list VALUES (60,2,6,'Ready for Test','Updated/Created software is ready to be included in the next build',70,'A');
INSERT INTO artifact_field_value_list VALUES (60,2,7,'In Test','Updated/Created software is in the build and is ready to enter the test phase',80,'A');
INSERT INTO artifact_field_value_list VALUES (60,2,8,'Approved','The artifact fix has been succesfully tested. It is approved and awaiting release.',90,'A');
INSERT INTO artifact_field_value_list VALUES (60,2,9,'Declined','The artifact was not accepted.',100,'A');
INSERT INTO artifact_field_value_list VALUES (60,2,10,'Done','The artifact is closed.',110,'A');


INSERT INTO artifact_field_value_list VALUES (60,3,1,'New','The artifact has just been submitted',20,'A');
INSERT INTO artifact_field_value_list VALUES (60,3,2,'Analyzed','The cause of the artifact has been identified and documented',30,'A');
INSERT INTO artifact_field_value_list VALUES (60,3,3,'Accepted','The artifact will be worked on.',40,'A');
INSERT INTO artifact_field_value_list VALUES (60,3,4,'Under Implementation','The artifact is being worked on.',50,'A');

INSERT INTO artifact_field_value_list VALUES (60,3,5,'Ready for Review','Updated/Created non-software work product (e.g. documentation) is ready for review and approval.',60,'A');
INSERT INTO artifact_field_value_list VALUES (60,3,6,'Ready for Test','Updated/Created software is ready to be included in the next build',70,'A');
INSERT INTO artifact_field_value_list VALUES (60,3,7,'In Test','Updated/Created software is in the build and is ready to enter the test phase',80,'A');
INSERT INTO artifact_field_value_list VALUES (60,3,8,'Approved','The artifact fix has been succesfully tested. It is approved and awaiting release.',90,'A');
INSERT INTO artifact_field_value_list VALUES (60,3,9,'Declined','The artifact was not accepted.',100,'A');
INSERT INTO artifact_field_value_list VALUES (60,3,10,'Done','The artifact is closed.',110,'A');


INSERT INTO artifact_field_value_list VALUES (60,4,1,'New','The artifact has just been submitted',20,'A');
INSERT INTO artifact_field_value_list VALUES (60,4,2,'Analyzed','The cause of the artifact has been identified and documented',30,'A');
INSERT INTO artifact_field_value_list VALUES (60,4,3,'Accepted','The artifact will be worked on.',40,'A');
INSERT INTO artifact_field_value_list VALUES (60,4,4,'Under Implementation','The artifact is being worked on.',50,'A');

INSERT INTO artifact_field_value_list VALUES (60,4,5,'Ready for Review','Updated/Created non-software work product (e.g. documentation) is ready for review and approval.',60,'A');
INSERT INTO artifact_field_value_list VALUES (60,4,6,'Ready for Test','Updated/Created software is ready to be included in the next build',70,'A');
INSERT INTO artifact_field_value_list VALUES (60,4,7,'In Test','Updated/Created software is in the build and is ready to enter the test phase',80,'A');
INSERT INTO artifact_field_value_list VALUES (60,4,8,'Approved','The artifact fix has been succesfully tested. It is approved and awaiting release.',90,'A');
INSERT INTO artifact_field_value_list VALUES (60,4,9,'Declined','The artifact was not accepted.',100,'A');
INSERT INTO artifact_field_value_list VALUES (60,4,10,'Done','The artifact is closed.',110,'A');


DELETE FROM artifact_field_value_list WHERE group_artifact_id = 5 AND field_id = 12;

INSERT INTO artifact_field_value_list VALUES (12,5,1,'New','The artifact has just been submitted',20,'A');
INSERT INTO artifact_field_value_list VALUES (12,5,2,'Analyzed','The cause of the artifact has been identified and documented',30,'A');
INSERT INTO artifact_field_value_list VALUES (12,5,3,'Accepted','The artifact will be worked on.',40,'A');
INSERT INTO artifact_field_value_list VALUES (12,5,4,'Under Implementation','The artifact is being worked on.',50,'A');

INSERT INTO artifact_field_value_list VALUES (12,5,5,'Ready for Review','Updated/Created non-software work product (e.g. documentation) is ready for review and approval.',60,'A');
INSERT INTO artifact_field_value_list VALUES (12,5,6,'Ready for Test','Updated/Created software is ready to be included in the next build',70,'A');
INSERT INTO artifact_field_value_list VALUES (12,5,7,'In Test','Updated/Created software is in the build and is ready to enter the test phase',80,'A');
INSERT INTO artifact_field_value_list VALUES (12,5,8,'Approved','The artifact fix has been succesfully tested. It is approved and awaiting release.',90,'A');
INSERT INTO artifact_field_value_list VALUES (12,5,9,'Declined','The artifact was not accepted.',100,'A');
INSERT INTO artifact_field_value_list VALUES (12,5,10,'Done','The artifact is closed.',110,'A');



EOF

echo "End of main DB upgrade"

echo "Update: add ugroups in SVN Access file"

###############################################################################
# add project ugroup definition in CodeX default SVN Access file
#


$PERL <<'EOF'

use DBI;
use Sys::Hostname;
use Carp;

require "/home/httpd/SF/utils/include.pl";  # Include all the predefined functions

&load_local_config();

# reuse from database_dump.pl

my $group_array = ();

&db_connect;

# Dump the Groups Table information
$query = "select group_id,unix_group_name,status,is_public,cvs_tracker,svn_tracker from groups";
$c = $dbh->prepare($query);
$c->execute();

while(my ($group_id, $group_name, $status, $is_public, $cvs_tracker, $svn_tracker) = $c->fetchrow()) {

	my $new_query = "select user.user_name AS user_name FROM user,user_group WHERE user.user_id=user_group.user_id AND group_id=$group_id";
	my $d = $dbh->prepare($new_query);
	$d->execute();

	my $user_list = "";
	
	while($user_name = $d->fetchrow()) {
	   $user_list .= "$user_name,";
	}

	$user_list =~ s/,$//;

	my $ugroup_list = "";

	my $new1_query = "select name,ugroup_id from ugroup where group_id=$group_id ORDER BY ugroup_id";
	my $d1 = $dbh->prepare($new1_query);
	$d1->execute();

	while (my ($ug_name, $ug_id) = $d1->fetchrow()) {

	  $ugroup_list .= " $ug_name=";	  
	  my $new2_query = "select u.user_name from user u, ugroup_user ugu where ugu.ugroup_id=$ug_id AND ugu.user_id = u.user_id";
	  my $d2 = $dbh->prepare($new2_query);
	  $d2->execute();

	  while ($user_name = $d2->fetchrow()) {
	    $ugroup_list .= "$user_name,";
	  }

	  $ugroup_list =~ s/,$//;
	}

	$grouplist = "$group_name:$status:$is_public:$cvs_tracker:$svn_tracker:$group_id:$user_list:$ugroup_list\n";

	push @group_array, $grouplist;
}

# Now write out the files
write_array_file($file_dir."group_dump", @group_array);
    

# reuse from new_parse.pl

# Make sure umask is properly positioned for the
# entire session. Root has umask 022 by default
# causing all the mkdir xxx, 775 to actually 
# create dir with permission 755 !!
# So set umask to 002 for the entire script session 
umask 002;

my $group_file = $file_dir . "group_dump";
my ($uid, $status, $username, $shell, $passwd, $win_passwd, $winnt_passwd, $email, $realname);
my ($gname, $gstatus, $gid, $userlist, $ugrouplist);

# Open up all the files that we need.
@groupdump_array = open_array_file($group_file);


#
# Loop through @groupdump_array and deal w/ users.
#
print ("\n\n	Processing Groups\n\n");
while ($ln = pop(@groupdump_array)) {
    chop($ln);
    ($gname, $gstatus, $gis_public, $cvs_tracker, $svn_tracker, $gid, $userlist, $ugrouplist) = split(":", $ln);

    print ("	     ",$gname,"\n");
    $gid += $gid_add;

    # Add sourceforge user to the group if it is a private project
    # otherwise Apache won't be able to access the document Root
    # of the project web iste which is not world readable (see below)
    $public_grp = $gis_public && ! -e "$grpdir_prefix/$gname/.CODEX_PRIVATE";
    if ($userlist eq "") {
	$userlist = "sourceforge" unless $public_grp;
    } else {
	$userlist .= ",sourceforge" unless $public_grp;
    }

    # make all user names lower case.
    $userlist =~ tr/A-Z/a-z/;
    $ugrouplist =~ tr/A-Z/a-z/;

    # update Subversion DAV access control file if needed
    my $svnaccess_file = "$svn_prefix/$gname/.SVNAccessFile";
    
    my $custom_perm=0;
    my $custom_lines = "";
    my $public_svn = $gis_public && ! -e "$svn_prefix/$gname/.CODEX_PRIVATE";
		
    # Retrieve custom permissions, if any
    if (-e "$svnaccess_file") {
	open(SVNACCESS,"$svnaccess_file");
	while (<SVNACCESS>) {
	    if ($custom_perm) {
		$custom_lines.=$_;
	    } else {
		if (m/END CODEX DEFAULT SETTINGS/) {$custom_perm=1;}
	    }
	}
	close(SVNACCESS);
    }
    
    if (-d "$svn_prefix/$gname") {
	open(SVNACCESS,">$svnaccess_file")
	or croak "Can't open Subversion access file $svnaccess_file: $!";
	# if you change these block markers also change them in
	# SF/www/svn/svn_utils.php
	print SVNACCESS "# BEGIN CODEX DEFAULT SETTINGS - DO NOT REMOVE\n";
	print SVNACCESS "[groups]\n";
	print SVNACCESS "members = ",$userlist,"\n";

	$new_custom_lines = $custom_lines;
	@ugroup_array = split(" ",$ugrouplist);

	while ($ln = pop(@ugroup_array)) {
	    print SVNACCESS $ln,"\n";

	    ##parse custom rules to rename groups that have the same name as ugroup
	    ($ug_name,$ugulist) = split("=",$ln);
	    @custom_array = split("\n",$custom_lines);
	    foreach (@custom_array) {
		if (/^${ug_name}=/) {
		    $new_custom_lines =~ s/${ug_name}=/${ug_name}_svn=/g;
		}
	    }


	}
	print SVNACCESS "\n";

	print SVNACCESS "[/]\n";
	if ($sys_allow_restricted_users) {
	    print SVNACCESS "* = \n"; # deny all access by default
	    # we don't know yet how to enable read access to all active users,
	    # and deny it to all restricted users...
	} else {
	    if ($public_svn) { print SVNACCESS "* = r\n"; }
	    else { print SVNACCESS "* = \n";}
	}
	print SVNACCESS "\@members = rw\n";
	print SVNACCESS "# END CODEX DEFAULT SETTINGS\n";
	if ($custom_perm) { print SVNACCESS $new_custom_lines;}
	close(SVNACCESS);
            
	# set group ownership, codex user as owner so that
	# PHP scripts can write to it directly
	system("chown -R $cxname:$gid $svnaccess_file");
	system("chmod g+rw $svnaccess_file");
    }
}

exit;
EOF

###############################################################################
# create references for existing projects
#

echo "Update: add references"

$PERL <<'EOF'

use DBI;

require "/home/httpd/SF/utils/include.pl";  # Include all the predefined functions

&db_connect;

my $reference;
my $nb_ref=0;
my $nb_ref_tracker=0;


sub load_references {
  $query = "SELECT id,service_short_name FROM reference";
  $c = $dbh->prepare($query);
  $c->execute();
  while (my ($id, $service) = $c->fetchrow()) {
    $references{"$id"}=$service;
  }
}


sub insert_references {
  my ($query, $c, $q, $d, $correct_day);

  $query = "SELECT group_id FROM groups WHERE group_id!=100";

  $c = $dbh->prepare($query);
  $c->execute();
  while (my ($group_id) = $c->fetchrow()) {
    #Get all services
    $query2 = "SELECT short_name,is_used FROM service WHERE group_id=$group_id AND scope='system'";
    $c2 = $dbh->prepare($query2);
    $c2->execute();
    my %service_is_used;
    while (my ($short_name, $is_used) = $c2->fetchrow()) {
      $service_is_used{"$short_name"}=$is_used;
    }
    foreach $service (keys %service_is_used) {
      foreach $ref_id (keys %references) {
        if ($references{"$ref_id"} eq $service) {
          # Add reference in project
          $query3 = "INSERT INTO reference_group (reference_id,group_id,is_active) VALUES ('$ref_id','$group_id','".$service_is_used{"$service"}."')";
          $c3 = $dbh->prepare($query3);
          $c3->execute();
          $nb_ref++;
        }
      }
    }
  }
}


sub insert_tracker_references {
  my ($query, $c, $q, $d, $correct_day);

  $query = "SELECT group_id,item_name FROM artifact_group_list WHERE status='A'";
  $c = $dbh->prepare($query);
  $c->execute();
  while (my ($group_id,$short_name) = $c->fetchrow()) {
    next if ($group_id==100); # do not create tracker refrences for template group
    $query2 = "INSERT INTO reference (keyword,description,link,scope) VALUES ('".lc($short_name)."','Tracker Artifact','/tracker/?func=detail&aid=\$1&group_id=$group_id','P')";
    $c2 = $dbh->prepare($query2);
    $c2->execute();
    $ref_id = $c2->{'mysql_insertid'};

    # Add reference in project
    $query3 = "INSERT INTO reference_group (reference_id,group_id,is_active) VALUES ('$ref_id','$group_id','1')";
    $c3 = $dbh->prepare($query3);
    $c3->execute();
    $nb_ref_tracker++;
  }
}




print "** Creating references for each project\n";
&load_references();
print "*    References loaded\n";
&insert_references();
print "*    $nb_ref service references added\n";
&insert_tracker_references();
print "*    $nb_ref_tracker tracker references added\n";
print "** All references created\n";
1;
EOF

todo "If your server does not support legacy trackers, go in the site Reference Administration page (/project/admin/reference.php?group_id=100) and delete (by clicking on trash icon) entries for 'bug' 'patch' 'sr' and 'task'"

##############################################
# CVS lockdir moved from cvs root to /var/lock/cvs
# This is to prevent checking out the lock dir.
# We do not delete existing .lockdir
echo "Creating new CVS lock directories, and updating existing cvs configs"

build_dir /var/lock/cvs root root 751

for projconfig in `ls /cvsroot/*/CVSROOT/config`
do
   projname=`echo $projconfig | perl -e '$_=<>; s@/cvsroot/(.*)/CVSROOT.*@\1@; chomp; print'`
   # Create lockdir
   mkdir /var/lock/cvs/$projname
   chmod 0777 /var/lock/cvs/$projname
   # Update CVS config
   perl -pi -e "s@LockDir\=.*@LockDir=/var/lock/cvs/$projname@" "$projconfig"
   # commit changes to config file (directly with RCS)
   cd /cvsroot/$projname/CVSROOT
   rcs -q -l config
   ci -q -m"CodeX 2.8 modifications" config
   co -q config
done


##############################################
# Reinstall modified shell scripts
#
echo "Copying updated /usr/local/bin/commit-email.pl"
$CP $INSTALL_DIR/SF/utils/svn/commit-email.pl /usr/local/bin
$CHOWN sourceforge.sourceforge /usr/local/bin/commit-email.pl
$CHMOD 775 /usr/local/bin/commit-email.pl
$CHMOD u+s /usr/local/bin/commit-email.pl

echo "Copying updated /usr/local/bin/log_accum"
$CP $INSTALL_DIR/SF/utils/cvs1/log_accum /usr/local/bin
$CHOWN sourceforge.sourceforge /usr/local/bin/log_accum
$CHMOD 775 /usr/local/bin/log_accum
$CHMOD u+s /usr/local/bin/log_accum

echo "Copying updated /usr/local/bin/fileforge"
$CP -a ${nonRPMS_DIR}/utilities/fileforge /usr/local/bin
$CHOWN root.root /usr/local/bin/fileforge
$CHMOD u+s /usr/local/bin/fileforge


echo "Copying updated /home/tools/backup_subversion.sh"
$CP $INSTALL_DIR/SF/utils/svn/backup_subversion.sh /home/tools
$CHOWN root.root /home/tools/backup_subversion.sh
$CHMOD 740 /home/tools/backup_subversion.sh
todo "Customize backup directories in /home/tools/backup_subversion.sh."
todo "You may also want to add the '-noarchives' flag to backup_subversion when used in full backup (see root crontab). This will delete previous backups once the full backup is completed."



###############################################################################
# Run 'analyse' on all MySQL DB
echo "Analyzing and optimizing MySQL databases (this might take a few minutes)"
mysqlcheck -Aao $pass_opt



##############################################
# Restarting some services
#
echo "Starting crond and apache..."
$SERVICE crond start
$SERVICE httpd start
$SERVICE sendmail start
$SERVICE mailman start


##############################################
# Generate Documentation
#
echo "Updating the User Manual. This might take a few minutes."
/home/httpd/SF/utils/generate_doc.sh -f
/home/httpd/SF/utils/generate_programmer_doc.sh -f
$CHOWN -R sourceforge.sourceforge $INSTALL_DIR/documentation
todo "A french version of the Codex User Guide is now available."
todo "  To make it available to users, please add a new entry into the Document Manager : "
todo "  - PDF location is : /documentation/user_guide/pdf/fr_FR/CodeX_User_Guide.pdf ;"
todo "  - multi-page HTML location is : /documentation/user_guide/html/fr_FR/index.html ;"
todo "  - single-page HTML location is : /documentation/user_guide/html/fr_FR/CodeX_User_Guide.html ;"
todo "  For instance, an example of description could be :"
todo "   Un guide complet décrivant tous les services de CodeX et comment les utiliser de manière optimale. Fournit également de nombreuses astuces et explications pour gérer efficacement votre projet CodeX. Disponible au format <a href=\"/documentation/user_guide/pdf/fr_FR/CodeX_User_Guide.pdf\">PDF </a>, <a href=\"/documentation/user_guide/html/fr_FR/index.html\">HTML (multi-fichiers)</a> et <a href=\"/documentation/user_guide/html/fr_FR/CodeX_User_Guide.html\">HTML (une page, 4 Mo)</a>. [Version française]"
todo "The english version of the CodeX User Guide is supplemented by a single page HTML version."
todo "  To make it available to users, please update your entry into the Document Manager : "
todo "  - single-page HTML location is : /documentation/user_guide/html/en_US/CodeX_User_Guide.html ;"
todo "-----------------------------------------"
todo "This TODO list is available in $TODO_FILE"


##############################################
# Make sure all major services are on
#
$CHKCONFIG named on
$CHKCONFIG sshd on
$CHKCONFIG httpd on
$CHKCONFIG mysql on
$CHKCONFIG cvs on
$CHKCONFIG mailman on

##############################################
# More things to do



# End of it
echo "=============================================="
echo "Migration completed succesfully!"
$CAT $TODO_FILE

exit 1;


