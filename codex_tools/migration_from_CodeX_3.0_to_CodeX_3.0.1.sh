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
#  This script migrates a site running CodeX 3.0 to CodeX 3.0.1
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
# CodeX 3.0 to 3.0.1 migration
##############################################
echo "Migration script from CodeX 3.0 data to CodeX 3.0.1"
echo "This script must be run AFTER a clean CodeX 3.0.1 installation, and copy of CodeX 3.0 data."
echo "Read migration_30.README for details"
echo
yn="y"
read -p "Continue? [yn]: " yn
if [ "$yn" = "n" ]; then
    echo "Bye now!"
    exit 1
fi

##############################################
# Check the machine is running CodeX 3.0
#
OLD_CX_RELEASE='3.0'
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


##############################################
# Check the machine is running CodeX 3.0 and that the migration script has been applied
# If the table 'artifact_field_set' exists, then it means that the migration script migration_30.sh has been applied.
#
mysql -u root $pass_opt codex -e "show tables like 'artifact_field_set'" | grep -q artifact_field_set
if [ $? -eq 1 ]; then
    echo "This machine is running CodeX 3.0, but the migration script seems to not have been applied. You should probably run migration_from_CodeX_2.8_to_CodeX_3.0.1.sh instead"
    exit 1
fi


echo "Starting DB update for CodeX 3.0.1. This might take a few minutes."

$CAT <<EOF | $MYSQL $pass_opt codex

###############################################################################
# Phpwiki 1.3.12
ALTER TABLE wiki_page ADD cached_html MEDIUMBLOB;
ALTER TABLE wiki_page ADD index group_id (group_id); 

###############################################################################
# Survey enhancement: new question type (SR #590)
INSERT INTO survey_question_types (id, type, rank) VALUES (7,'select_box', '23');

###############################################################################
# SVN admin new role (SR #602)
ALTER TABLE user_group ADD COLUMN svn_flags int(11) NOT NULL default '0' AFTER wiki_flags;


CREATE TABLE ugroup_mapping (
  to_group_id int(11) NOT NULL,
  src_ugroup_id int(11) NOT NULL,
  dst_ugroup_id int(11) NOT NULL,
  PRIMARY KEY (to_group_id, src_ugroup_id, dst_ugroup_id)
);

EOF



################################################################################
# Notifications: create tables
# 
$CAT <<EOF | $MYSQL $pass_opt codex

DROP TABLE IF EXISTS notifications;
CREATE TABLE notifications(
  user_id int( 11 ) NOT NULL ,
  object_id int( 11 ) NOT NULL ,
  type varchar( 100 ) NOT NULL default '',
  PRIMARY KEY  (user_id, object_id, type)
);
EOF

################################################################################
# Upgrade docman
#
cd $INSTALL_DIR/plugins/docman/db/ ; ./upgrade_v2_001.pl ; cd - > /dev/null
$CAT $INSTALL_DIR/plugins/docman/db/upgrade_v2_002.sql | $MYSQL $pass_opt codex 

###############################################################################
# Remove sticky bit from /var/run/log_accum. See SR #594
$CHMOD 0777 /var/run/log_accum

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


todo "If you have custom themes, please :"
todo " - copy the rules for feedback from the end of CodeXTab/css/style.css in your stylesheets"
todo " - copy the icons for feedback:"
todo "        cp /usr/share/codex/src/www/themes/CodeXTab/images/ic/info.png /path/to/your/theme/images/ic/"
todo "        cp /usr/share/codex/src/www/themes/CodeXTab/images/ic/error.png /path/to/your/theme/images/ic/"
todo "        cp /usr/share/codex/src/www/themes/CodeXTab/images/ic/warning.png /path/to/your/theme/images/ic/"
todo "Have a look at plugins/docman/etc/docman.inc and configure yours (in /etc/codex/plugins/docman/etc/)."
todo "-----------------------------------------"
todo "This TODO list is available in $TODO_FILE"


# End of it
echo "=============================================="
echo "Migration completed succesfully!"
$CAT $TODO_FILE

exit 1;

