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
#  $Id$
#
#      Originally written by Laurent Julliard 2004-2006, CodeX Team, Xerox
#
#  This file is part of the CodeX software and must be placed at the same
#  level as the CodeX, RPMS_CodeX and nonRPMS_CodeX directory when
#  delivered on a CD or by other means
#
#  This script migrates a site running CodeX 3.0.1 to CodeX 3.2
#


progname=$0
#scriptdir=/mnt/cdrom
if [ -z "$scriptdir" ]; then 
    scriptdir=`dirname $progname`
fi
cd ${scriptdir};TOP_DIR=`pwd`;cd - > /dev/null # redirect to /dev/null to remove display of folder (RHEL4 only)
RPMS_DIR=${TOP_DIR}/RPMS_CodeX
nonRPMS_DIR=${TOP_DIR}/nonRPMS_CodeX
CodeX_DIR=${TOP_DIR}/CodeX
TODO_FILE=/root/todo_codex_upgrade.txt
export INSTALL_DIR="/usr/share/codex"

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
export MYSQL='/usr/bin/mysql'
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
  # Allow '/' is $3, so we need to double-escape the string
  replacement=`echo $3 | sed "s|/|\\\\\/|g"`
  $PERL -pi -e "s/$2/$replacement/g" $1
}

##############################################
# CodeX 3.0.1 to 3.2 migration
##############################################
echo "Migration script from CodeX 3.0.1 data to CodeX 3.2"
echo
yn="y"
read -p "Continue? [yn]: " yn
if [ "$yn" = "n" ]; then
    echo "Bye now!"
    exit 1
fi

##############################################
# Check the machine is running CodeX 3.0.1
#
OLD_CX_RELEASE='3.0.1'
yn="y"
$GREP -q "$OLD_CX_RELEASE" $INSTALL_DIR/src/www/VERSION
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
# Check we are running on RHEL 4
#
RH_RELEASE="4"
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
$SERVICE mysqld stop
$SERVICE sendmail stop
$SERVICE postfix stop
$SERVICE mailman stop
$SERVICE smb stop


##############################################
# Install the CodeX software 
#
echo "Installing the CodeX software..."
cd $INSTALL_DIR
cd ..
$MV codex codex_30
$MKDIR codex;
cd codex
$TAR xfz ${CodeX_DIR}/codex*.tgz
$CHOWN -R codexadm.codexadm $INSTALL_DIR
$FIND $INSTALL_DIR -type f -exec $CHMOD u+rw,g+rw,o-w+r, {} \;
$FIND $INSTALL_DIR -type d -exec $CHMOD 775 {} \;

for f in /etc/httpd/conf.d/codex_aliases.conf; do
    yn="0"
    fn=`basename $f`
    [ -f "$f" ] && read -p "$f already exist. Overwrite? [y|n]:" yn

    if [ "$yn" = "y" ]; then
	$CP -f $f $f.orig
    fi

    if [ "$yn" != "n" ]; then
	$CP -f $INSTALL_DIR/src/etc/$fn.dist $f
    fi

    $CHOWN codexadm.codexadm $f
    $CHMOD 640 $f
done

##############################################
# Database Structure and initvalues upgrade
#
echo "Updating the CodeX database..."

$SERVICE mysqld start
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


echo "Starting DB update for CodeX 3.2 This might take a few minutes."

$CAT <<EOF | $MYSQL $pass_opt codex
###############################################################################
# Servers
CREATE TABLE server (
  id INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
  name VARCHAR( 255 ) NOT NULL ,
  description TEXT NOT NULL ,
  url TEXT NOT NULL
);
ALTER TABLE service ADD server_id INT( 11 ) UNSIGNED NULL ;

###############################################################################
# Feedback
CREATE TABLE feedback (
  session_hash CHAR( 32 ) NOT NULL ,
  feedback TEXT NOT NULL ,
  created_at DATETIME NOT NULL ,
  PRIMARY KEY ( session_hash )
);

###############################################################################
# Plugin docman
CREATE TABLE plugin_docman_tokens(
  user_id INT( 11 ) NOT NULL ,
  token CHAR( 32 ) NOT NULL ,
  url text NOT NULL default '',
  PRIMARY KEY ( user_id, token )
);

###############################################################################
# Add a status for Files in FRS
ALTER TABLE `frs_file` ADD `status` CHAR( 1 ) NOT NULL DEFAULT 'A';

###############################################################################
# Processor types in FRS (possible to add custom processor types per project)
DROP TABLE frs_processor;

CREATE TABLE frs_processor (
  processor_id int(11) NOT NULL auto_increment,
  name text,
  rank int(11) NOT NULL default '0',
  group_id int(11) NOT NULL default '0',
  PRIMARY KEY (processor_id)
);

INSERT INTO frs_processor (name,rank,group_id) VALUES ('i386','10','100');
INSERT INTO frs_processor (name,rank,group_id) VALUES ('PPC','20','100');
INSERT INTO frs_processor (name,rank,group_id) VALUES ('MIPS','30','100');
INSERT INTO frs_processor (name,rank,group_id) VALUES ('Sparc','40','100');
INSERT INTO frs_processor (name,rank,group_id) VALUES ('UltraSparc','50','100');
INSERT INTO frs_processor (name,rank,group_id) VALUES ('IA64','60','100');
INSERT INTO frs_processor (name,rank,group_id) VALUES ('Alpha','70','100');
INSERT INTO frs_processor (name,rank,group_id) VALUES ('Any','80','100');

EOF

echo "Optimizing database structure."
$CAT <<EOF | $MYSQL $pass_opt codex

# SR #636
ALTER TABLE artifact_history CHANGE field_name field_name VARCHAR(255) NOT NULL default '';
ALTER TABLE artifact_history ADD INDEX field_name (field_name (10));

# SR #637 - fixes issue with large tracker reports
ALTER TABLE artifact_field_value ADD INDEX idx_art_field_id (artifact_id, field_id);

# SR #635 - Permission table
ALTER TABLE permissions CHANGE permission_type permission_type VARCHAR(255) NOT NULL;
ALTER TABLE permissions CHANGE object_id object_id VARCHAR(255) NOT NULL;
ALTER TABLE permissions ADD INDEX object_id (object_id (10));

# SR #634
ALTER TABLE svn_commits ADD INDEX revision (revision);

# SR #633
ALTER TABLE artifact_field_value ADD INDEX valueInt (valueInt);
EOF

################################################################################
echo " DB - Permissions update"

$PERL <<'EOF'

# add permissions to all packages, if they have no permissions set, we add register_user permission
# add permissions to all releases, if they have no permissions set, we add the same permission as their parents

use DBI;

require $ENV{INSTALL_DIR}."/src/utils/include.pl";  # Include all the predefined functions

&db_connect;


sub add_packages_permissions {
	my ($query, $c, $q, $d);

	$query = "SELECT package_id, ugroup_id FROM frs_package LEFT OUTER JOIN permissions ON package_id=object_id AND permission_type='PACKAGE_READ'";

	$c = $dbh->prepare($query);
    	$c->execute();
    	while (my ($package_id, $ugroup_id) = $c->fetchrow()) {
		
			if($ugroup_id=='NULL'){
				$q="INSERT INTO permissions (object_id, permission_type, ugroup_id) VALUES ('".$package_id."', 'PACKAGE_READ', '2')";
				#print $q."\n";
				$d = $dbh->prepare($q);
  				$d->execute();
			}

		}
}

sub add_releases_permissions {
	my ($query, $c, $q, $d);

	$query = "SELECT release_id,rp.ugroup_id as r_ugroup_id, pp.ugroup_id as p_ugroup_id FROM frs_release".
			 " LEFT OUTER JOIN  permissions rp ON release_id = rp.object_id and rp.permission_type = 'RELEASE_READ'".
			 " LEFT OUTER JOIN  permissions pp ON package_id = pp.object_id and pp.permission_type = 'PACKAGE_READ'";

	$c = $dbh->prepare($query);
    	$c->execute();
    	while (my ($release_id, $r_ugroup_id, $p_ugroup_id) = $c->fetchrow()) {
		
			if($r_ugroup_id=='NULL'){
				$q="INSERT INTO permissions (object_id, permission_type, ugroup_id) VALUES ('".$release_id."', 'RELEASE_READ',".$p_ugroup_id.")";
				#print $q."\n";
				$d = $dbh->prepare($q);
  				$d->execute();
			}

		}
}

add_packages_permissions();
add_releases_permissions();

1;
EOF

###############################################################################
# Run 'analyse' on all MySQL DB
echo "Analyzing and optimizing MySQL databases (this might take a few minutes)"
mysqlcheck -Aao $pass_opt


##############################################
# Fix SELinux
#
cd $INSTALL_DIR
cd src/utils
./fix_selinux_contexts.pl

##############################################
# Restarting some services
#
echo "Starting crond and apache..."
$SERVICE crond start
$SERVICE httpd start
$SERVICE sendmail start
$SERVICE mailman start
$SERVICE smb start

!!! Déplacer le site-content/register des clients vers site-content/project
todo "register has been moved !!!"
todo "If you have custom themes, please :"
??? todo " - update usage of feedback (maybe need to display it at the end of header(). See rev #4756 for details"
??? todo " - copy the rules for textfield_small/textfield_medium from CodeXTab/css/style.css in your stylesheets"
todo "-----------------------------------------"
todo "This TODO list is available in $TODO_FILE"


# End of it
echo "=============================================="
echo "Migration completed succesfully!"
$CAT $TODO_FILE

exit 1;

