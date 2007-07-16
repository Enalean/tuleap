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
#  This script migrates a site running CodeX 3.2 to CodeX 3.4
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
TODO_FILE=/root/todo_codex_upgrade_3.4.txt
export INSTALL_DIR="/usr/share/codex"
BACKUP_INSTALL_DIR="/usr/share/codex_32"
ETC_DIR="/etc/codex"

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
DIFF='/usr/bin/diff'

CMD_LIST="GROUPADD GROUDEL USERADD USERDEL USERMOD MV CP LN LS RM TAR \
MKDIR RPM CHOWN CHMOD FIND TOUCH CAT MAKE TAIL GREP CHKCONFIG \
SERVICE PERL DIFF"

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
# CodeX 3.2 to 3.4 migration
##############################################
echo "Migration script from CodeX 3.2 data to CodeX 3.4"
echo
yn="y"
read -p "Continue? [yn]: " yn
if [ "$yn" = "n" ]; then
    echo "Bye now!"
    exit 1
fi

##############################################
# Check the machine is running CodeX 3.2
#
OLD_CX_RELEASE='3.2'
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


##############################################
# Ask for domain name and other installation parameters
#
sys_default_domain=`grep ServerName /etc/httpd/conf/httpd.conf | grep -v '#' | head -1 | cut -d " " -f 2 ;`
if [ -z $sys_default_domain ]; then
  read -p "CodeX Domain name: " sys_default_domain
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
# Now install/update CodeX specific RPMS 
#


# -> subversion
# backup config file for apache
$MV /etc/httpd/conf.d/subversion.conf /etc/httpd/conf.d/subversion.conf.3.2.codex
echo "Installing Subversion 1.4 RPMs for CodeX...."
echo "Installing Subversion RPMs for CodeX...."
cd ${RPMS_DIR}/subversion
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
$RPM -Uvh ${newest_rpm}/apr-0*.i386.rpm
$RPM -Uvh ${newest_rpm}/apr-util-0*.i386.rpm
$RPM -Uvh ${newest_rpm}/subversion-1.*.i386.rpm 
$RPM -Uvh ${newest_rpm}/mod_dav_svn*.i386.rpm
$RPM -Uvh ${newest_rpm}/subversion-perl*.i386.rpm
$RPM -Uvh ${newest_rpm}/subversion-python*.i386.rpm
$RPM -Uvh ${newest_rpm}/subversion-tools*.i386.rpm

$CP -a /etc/httpd/conf.d/subversion.conf.3.2.codex /etc/httpd/conf.d/subversion.conf

todo "Subversion has been upgraded to version 1.4. There is some benefit (repository size reduction) in dumping/reloading your repositories, but it is absolutely not needed. See http://subversion.tigris.org/svn_1.4_releasenotes.html for more details."

##############################################
# Install the CodeX software 
#
echo "Installing the CodeX software..."
$MV $INSTALL_DIR $BACKUP_INSTALL_DIR
$MKDIR $INSTALL_DIR;
cd $INSTALL_DIR
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
# Analyze site-content 
#
echo "Analysing your site-content (in $ETC_DIR/site-content/)..."

#Only in etc => removed
removed=`$DIFF -q -r \
 $ETC_DIR/site-content/ \
 $INSTALL_DIR/site-content/        \
 | grep -v '.svn'  \
 | sed             \
 -e "s|^Only in $ETC_DIR/site-content/\([^:]*\): \(.*\)|@\1/\2|g" \
 -e "/^[^@]/ d"  \
 -e "s/@//g"     \
 -e '/^$/ d'`
if [ "$removed" != "" ]; then
  echo "The following files doesn't existing in the site-content of CodeX:"
  echo "$removed"
fi

modified=`$DIFF -q -r \
            $BACKUP_INSTALL_DIR/site-content/ \
            $INSTALL_DIR/site-content/        \
            | grep -v '.svn'  \
            | sed             \
            -e "s|^Files $BACKUP_INSTALL_DIR/site-content/\(.*\) and $INSTALL_DIR/site-content/\(.*\) differ|@\1|g" \
            -e "/^[^@]/ d"  \
            -e "s/@//g"     \
            -e '/^$/ d'`
            
#Differ => modified
one_has_been_found=0
for i in `$DIFF -q -r \
            $ETC_DIR/site-content/ \
            $INSTALL_DIR/site-content/        \
            | grep -v '.svn'  \
            | sed             \
            -e "s|^Files $ETC_DIR/site-content/\(.*\) and $INSTALL_DIR/site-content/\(.*\) differ|@\1|g" \
            -e "/^[^@]/ d"  \
            -e "s/@//g"     \
            -e '/^$/ d'` 
do
    j=`echo "$modified" | grep $i`
    if [ "$j" != "" ]; then
       if [ $one_has_been_found -eq 0 ]; then
          echo "The following files differs from the site-content of CodeX:"
          one_has_been_found=1
       fi
       echo $j
    fi
done

echo "Analysis done."

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


echo "Starting DB update for CodeX 3.4 This might take a few minutes."

$CAT <<EOF | $MYSQL $pass_opt codex

###############################################################################
# SR #283 - Remove trove_treesums table 

DROP TABLE IF EXISTS trove_treesums;



# SR #772 - Rename 'release' field from legacy tracker to 'release_name' to avoid conflict in MySQL 5
ALTER TABLE bug CHANGE release release_name varchar(255) NOT NULL default '';
UPDATE bug_field SET field_name='release_name' where field_name='release';

# SR #894 - Project long name way too short
ALTER TABLE groups CHANGE group_name group_name VARCHAR( 255 ) DEFAULT NULL;

# Support for more than 4GB table in MySQL -> 1TB
ALTER TABLE artifact_file MAX_ROWS = 1000000 AVG_ROW_LENGTH = 1000000;


###############################################################################
# This should have been done earlier. Nevertheless, fix remaining shells if any

UPDATE user SET shell='/usr/lib/codex/bin/cvssh-restricted' WHERE shell='/usr/local/bin/cvssh-restricted';
UPDATE user SET shell='/usr/lib/codex/bin/cvssh' WHERE shell='/usr/local/bin/cvssh';

###############################################################################
# Personalizeable layout
-- 
-- Table structure for table 'layouts'
-- 

DROP TABLE IF EXISTS layouts;
CREATE TABLE IF NOT EXISTS layouts (
  id int(11) unsigned NOT NULL auto_increment,
  name varchar(255) NOT NULL default '',
  description text NOT NULL,
  scope varchar(1) NOT NULL default 'S',
  PRIMARY KEY  (id)
);

-- --------------------------------------------------------

-- 
-- Table structure for table 'layouts_rows'
-- 

DROP TABLE IF EXISTS layouts_rows;
CREATE TABLE IF NOT EXISTS layouts_rows (
  id int(11) unsigned NOT NULL auto_increment,
  layout_id int(11) unsigned NOT NULL default '0',
  rank int(11) NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY layout_id (layout_id)
);

-- --------------------------------------------------------

-- 
-- Table structure for table 'layouts_rows_columns'
-- 

DROP TABLE IF EXISTS layouts_rows_columns;
CREATE TABLE IF NOT EXISTS layouts_rows_columns (
  id int(11) unsigned NOT NULL auto_increment,
  layout_row_id int(11) unsigned NOT NULL default '0',
  width int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY layout_row_id (layout_row_id)
);

-- --------------------------------------------------------

-- 
-- Table structure for table 'user_layouts'
-- 

DROP TABLE IF EXISTS user_layouts;
CREATE TABLE IF NOT EXISTS user_layouts (
  user_id int(11) unsigned NOT NULL default '0',
  layout_id int(11) unsigned NOT NULL default '0',
  is_default tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (user_id, layout_id)
);

-- --------------------------------------------------------

-- 
-- Table structure for table 'user_layouts_contents'
-- 

DROP TABLE IF EXISTS user_layouts_contents;
CREATE TABLE IF NOT EXISTS user_layouts_contents (
  user_id int(11) unsigned NOT NULL default '0',
  layout_id int(11) unsigned NOT NULL default '0',
  column_id int(11) unsigned NOT NULL default '0',
  name varchar(255) NOT NULL,
  content_id int(11) unsigned NOT NULL default '0',
  rank int(11) NOT NULL default '0',
  is_minimzed tinyint(1) NOT NULL default '0',
  KEY (user_id, layout_id, name, content_id),
  KEY (name)
);


DROP TABLE IF EXISTS user_rss;
CREATE TABLE IF NOT EXISTS user_rss (
  id int(11) unsigned NOT NULL auto_increment PRIMARY KEY,
  user_id int(11) unsigned NOT NULL default '0',
  title varchar(255) NOT NULL,
  url TEXT NOT NULL,
  KEY (user_id)
);




INSERT INTO layouts (id, name, description, scope) VALUES (1, '2 columns', 'Standard layout', 'S');
INSERT INTO layouts_rows (id, layout_id, rank) VALUES (1, 1, 0);
INSERT INTO layouts_rows_columns (id, layout_rows_id, width) VALUES (1, 1, 50), (2, 1, 50);

INSERT INTO user_layouts (user_id, layout_id, is_default) 
SELECT user_id, 1, 1 
FROM user;

INSERT INTO user_layouts_contents (user_id, layout_id, column_id, name, rank) 
SELECT user_id, 1, 1, 'mysurveys', 4
FROM user;

INSERT INTO user_layouts_contents (user_id, layout_id, column_id, name, rank) 
SELECT user_id, 1, 1, 'mymonitoredforums', 2
FROM user;

INSERT INTO user_layouts_contents (user_id, layout_id, column_id, name, rank) 
SELECT user_id, 1, 1, 'mybookmarks', 1
FROM user;

INSERT INTO user_layouts_contents (user_id, layout_id, column_id, name, rank) 
SELECT user_id, 1, 2, 'myartifacts', 0
FROM user;

INSERT INTO user_layouts_contents (user_id, layout_id, column_id, name, rank) 
SELECT user_id, 1, 2, 'mymonitoredfp', 1
FROM user;

INSERT INTO user_layouts_contents (user_id, layout_id, column_id, name, rank) 
SELECT user_id, 1, 1, 'myprojects', 0
FROM user;


TODO plugins

###############################################################################
# 


EOF

###############################################################################
# Run 'analyse' on all MySQL DB
echo "Analyzing and optimizing MySQL databases (this might take a few minutes)"
mysqlcheck -Aao $pass_opt

###############################################################################
echo "Updating local.inc"

# Remove end PHP marker
substitute '/etc/codex/conf/local.inc' '\?\>' ''

$CAT <<EOF >> /etc/codex/conf/local.inc

?>
EOF

##############################################
# Fix SELinux contexts if needed
#
echo "Update SELinux contexts if needed"
cd $INSTALL_DIR/src/utils
./fix_selinux_contexts.pl

##############################################
# Restarting some services
#
echo "Starting services..."
$SERVICE crond start
$SERVICE httpd start
$SERVICE sendmail start
$SERVICE mailman start
$SERVICE smb start

##############################################
# Generate Documentation
#
echo "Generating the CodeX Manuals. This will take a few minutes."
$INSTALL_DIR/src/utils/generate_doc.sh -f
$INSTALL_DIR/src/utils/generate_programmer_doc.sh -f
$INSTALL_DIR/src/utils/generate_cli_package.sh -f
$CHOWN -R codexadm.codexadm $INSTALL_DIR/documentation


TODO revision #6417 #6479 : themes, db, local.inc
TODO revision #6419 (follow-up comments) : themes

TODO "Add the SOAP API change in release notes (about function getArtifacts, about getAttachedFiles that does not return the content of the files anymore for performance reasons)"

todo "If you have custom themes:"
todo "  - add a call to warning_for_services_which_configuration_is_not_inherited() if needed"
todo "-----------------------------------------"
todo "This TODO list is available in $TODO_FILE"


# End of it
echo "=============================================="
echo "Migration completed succesfully!"
$CAT $TODO_FILE

exit 1;

