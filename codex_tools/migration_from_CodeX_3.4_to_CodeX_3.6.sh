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
#  This script migrates a site running CodeX 3.4 to CodeX 3.6
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
TODO_FILE=/root/todo_codex_upgrade_3.6.txt
export INSTALL_DIR="/usr/share/codex"
BACKUP_INSTALL_DIR="/usr/share/codex_34"
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
MKDIR RPM CHOWN CHMOD FIND MYSQL TOUCH CAT MAKE TAIL GREP CHKCONFIG \
SERVICE PERL DIFF"

CHCON='/usr/bin/chcon'
SELINUX_CONTEXT="root:object_r:httpd_sys_content_t";
SELINUX_ENABLED=1
if [ ! -e $CHCON ] || [ ! -e "/etc/selinux/config" ] || `grep -i -q '^SELINUX=disabled' /etc/selinux/config`; then
   # SELinux not installed
   SELINUX_ENABLED=0
fi


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
# CodeX 3.4 to 3.6 migration
##############################################
echo "Migration script from CodeX 3.4 to CodeX 3.6"
echo
yn="y"
read -p "Continue? [yn]: " yn
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
# Check the machine is running CodeX 3.4
#
OLD_CX_RELEASE='3.4'
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
# Check we are running on RHEL 5
#
RH_RELEASE="5"
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

# SELinux CodeX-specific policy
if [ $SELINUX_ENABLED ]; then
    echo "Removing existing SELinux policy .."
    $RPM -e selinux-policy-targeted-sources 2>/dev/null
    $RPM -e selinux-policy-targeted 2>/dev/null
    echo "Installing New SELinux targeted policy for CodeX...."
    cd ${RPMS_DIR}/selinux-policy-targeted
    newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
    $RPM -Uvh ${newest_rpm}/selinux-policy-targeted-1*.noarch.rpm
fi


echo "Installing Highlight RPMs for CodeX...."
# -> highlight
$RPM -e highlight 2>/dev/null
echo "Installing highlight RPM for CodeX...."
cd ${RPMS_DIR}/highlight
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
$RPM -Uvh ${newest_rpm}/highlight-2*i?86.rpm

# -> HTML Purifier
echo "Removing installed htmlpurifier if any .."
$RPM -e htmlpurifier 2>/dev/null
$RPM -e htmlpurifier-docs 2>/dev/null
echo "Installing htmlpurifier RPM for CodeX...."
cd ${RPMS_DIR}/htmlpurifier
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
$RPM -Uvh ${newest_rpm}/htmlpurifier-2*.noarch.rpm
$RPM -Uvh ${newest_rpm}/htmlpurifier-docs*.noarch.rpm


# -> subversion
# backup config file for apache
$MV /etc/httpd/conf.d/subversion.conf /etc/httpd/conf.d/subversion.conf.3.6.codex
echo "Installing Subversion 1.4.4 RPMs for CodeX...."
cd ${RPMS_DIR}/subversion
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
$RPM -Uvh ${newest_rpm}/apr-0*.i386.rpm ${newest_rpm}/apr-util-0*.i386.rpm \
     ${newest_rpm}/subversion-1.*.i386.rpm  ${newest_rpm}/mod_dav_svn*.i386.rpm \
     ${newest_rpm}/subversion-perl*.i386.rpm ${newest_rpm}/subversion-python*.i386.rpm \
     ${newest_rpm}/subversion-tools*.i386.rpm

$CP -a /etc/httpd/conf.d/subversion.conf.3.6.codex /etc/httpd/conf.d/subversion.conf


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

# Add FollowSymLinks option to directory /usr/share/codex/downloads
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
          echo "  The following files differ from the site-content of CodeX:"
          one_has_been_found=1
       fi
       echo "    $j"
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


echo "Starting DB update for CodeX 3.6 This might take a few minutes."

##########
# Migrate all CodeX databases to UTF-8
echo "- Migrate all CodeX databases to UTF-8"
$CAT <<EOF | php
<?php

require_once('src/common/dao/DBTablesDao.class.php');
require_once('src/common/dao/DBDatabasesDao.class.php');
require_once('src/common/dao/include/DataAccess.class.php');

$da = new DataAccess('', 'root', '$old_passwd', 'codex');
$tables_dao = new DBTablesDao($da);

$db_dao = new DBDatabasesDao($da);
foreach($db_dao->searchAll() as $db) {
    $db = $db['Database'];
    if ($db == 'codex' || preg_match('/^cx_/', $db)) {
        echo " + ". $db;
        $tables_dao->update('USE '. $db);
        foreach($tables_dao->searchAll() as $row) {
            $tables_dao->convertToUTF8($row['Tables_in_'. $db]);
            echo ".";
            flush();
        }
        $db_dao->setDefaultCharsetUTF8($db);
        echo " done\n";
    } else {
        echo ' ! Ignoring '. $db ."\n";
    }
}
?>
EOF

##########
# SR #820
echo "- SR #820"
# The order of the three statements below is important!!!
$CAT <<EOF | $MYSQL $pass_opt codex

INSERT INTO permissions (permission_type , object_id , ugroup_id) 
SELECT 'TRACKER_FIELD_READ' , CONCAT(agl.group_artifact_id, '#', MAX(field_id) + 1) , 1
FROM artifact_field AS af INNER JOIN artifact_group_list AS agl USING(group_artifact_id) 
WHERE agl.status = 'A' AND agl.group_artifact_id <> 100
GROUP BY agl.group_artifact_id;

INSERT INTO artifact_field_usage (group_artifact_id , field_id , use_it , place) 
SELECT agl.group_artifact_id, MAX(field_id) + 1 AS field_id, 1 , 0
FROM artifact_field AS af INNER JOIN artifact_group_list AS agl USING(group_artifact_id) 
WHERE agl.status = 'A' AND agl.group_artifact_id <> 100
GROUP BY agl.group_artifact_id;

INSERT INTO artifact_field (field_id , group_artifact_id , field_set_id , field_name, data_type , display_type , label , description , required , empty_ok , keep_history , special) 
SELECT MAX(field_id) + 1 , agl.group_artifact_id , MIN(afs.field_set_id) , 'last_update_date' , 4 , 'DF' , 'Last Modified On' , 'Date and time of the latest modification in an artifact' , 0 , 0 , 0 , 1
FROM artifact_field_set AS afs INNER JOIN artifact_field AS af USING(group_artifact_id)
     INNER JOIN artifact_group_list AS agl USING(group_artifact_id) 
WHERE agl.status = 'A' AND agl.group_artifact_id <> 100
GROUP BY agl.group_artifact_id;

EOF

##########
# Add info about user login
echo "- Add info about user login. See revision #7319"
$CAT <<EOF | $MYSQL $pass_opt codex

ALTER TABLE user ADD COLUMN prev_auth_success INT(11) NOT NULL DEFAULT 0 AFTER last_access_date
ALTER TABLE user ADD COLUMN last_auth_success INT(11) NOT NULL DEFAULT 0 AFTER prev_auth_success
ALTER TABLE user ADD COLUMN last_auth_failure INT(11) NOT NULL DEFAULT 0 AFTER last_auth_success
ALTER TABLE user ADD COLUMN nb_auth_failure INT(11) NOT NULL DEFAULT 0 AFTER last_auth_failure

EOF

###############################################################################
# Run 'analyse' on all MySQL DB
echo "Analyzing and optimizing MySQL databases (this might take a few minutes)"
mysqlcheck -Aaos $pass_opt

###############################################################################
echo "Updating local.inc"

# jpgraph
$GREP -q ^\$htmlpurifier_dir  $ETC_DIR/conf/local.inc
if [ $? -ne 0 ]; then
  # Remove end PHP marker
  substitute '/etc/codex/conf/local.inc' '\?\>' ''

  $CAT <<EOF >> /etc/codex/conf/local.inc
// 3rd Party libraries
\$jpgraph_dir = "/usr/share/jpgraph";

?>
EOF
fi


##############################################
# Update codexadm crontab: add codex_daily.php at 00:15
#

echo "Backing up codexadm crontab in /tmp/crontab.codexadm.bak"
crontab -u codexadm -l > /tmp/crontab.codexadm.bak
echo "Installing new codexadm user crontab..."
$CAT <<'EOF' >/tmp/cronfile
# Daily CodeX PHP cron (obsolete documents...)
10 0 * * * /usr/share/codex/src/utils/php-launcher.sh /usr/share/codex/src/utils/codex_daily.php
# Re-generate the CodeX User Guides on a daily basis
00 03 * * * /usr/share/codex/src/utils/generate_doc.sh
30 03 * * * /usr/share/codex/src/utils/generate_programmer_doc.sh
45 03 * * * /usr/share/codex/src/utils/generate_cli_package.sh
EOF
crontab -u codexadm /tmp/cronfile


##############################################
# Fix SELinux contexts if needed
#
echo "Update SELinux contexts if needed"
cd $INSTALL_DIR/src/utils
./fix_selinux_contexts.pl



##############################################
# Update ParametersLocal.dtd
#
$CAT <<'EOF' >>/etc/codex/documentation/user_guide/xml/ParametersLocal.dtd
<!ENTITY SYS_UPDATE_SITE "http://$sys_default_domain/plugins/eclipse/updatesite/">
EOF
todo "If only HTTPS is enabled on the CodeX server:"
todo " * update ENTITY SYS_UPDATE_SITE in /etc/codex/documentation/user_guide/xml/ParametersLocal.dtd (replace 'http' by 'https')"
todo " * WARNING: The Eclipse plugin *requires* a *valid* SSL certificate (from a certified authority). Self-signed certificates *won't* work."
	
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


todo "Add proxy setting in /etc/codex/conf/local.inc if the CodeX server needs to use a proxy to access the Internet. This is used for external RSS feeds."
todo "Add Eclipse plugin documentation in the site documentation (links available from /plugins/eclipse/). Documentation is available in French and English."
todo "Update the site-content/<language>/homepage.tab to promote the Eclipse plugin and CodeX JRI (Java Runtime Environnement)"
todo "SSL certificate has changed on Partners. In order to enable the subversion update, you need to type the following commands (as codexadm):"
todo "     cd /usr/share/codex/"
todo "     svn status -u --username <your_login_on_partners>"
todo "   Accept the new certificate permanently, and type in your password."
todo "Warn your users who use exported DB that the project database name is now prefixed by 'cx_' (SR #948)"
todo "Rename, copy or regenerate projects DB to take into account the new 'cx_' prefix (e.g. use phpMyAdmin)"
todo "Warn your users of the SOAP API changes in functions getArtifacts, getAttachedFiles (that does not return the content of the files anymore for performance reasons), and of the new docman API function getRootFolder)"
todo "If you have custom themes:"
todo "  -New icons: add.png, close.png, comment.png, cross.png, group.png, quote.png, tick.png. You may copy them from /usr/share/codex/src/www/themes/CodeXTab/images/ic"
todo "  -New image: widget-header.png. You may copy them from /usr/share/codex/src/www/themes/CodeXTab/images"
todo "  -Updated CSS: Everything below the line '/* {{{ Widgets */' in /usr/share/codex/src/www/themes/CodeXTab/css/style.css should be added to your style.css (except the 'password validator' section if it is already present)."
todo "  -If you redefined generic_header_start() in your theme layout class, you should add a call to warning_for_services_which_configuration_is_not_inherited() (see Layout.class.php)"
todo "-----------------------------------------"
todo "This TODO list is available in $TODO_FILE"


# End of it
echo "=============================================="
echo "Migration completed succesfully!"
$CAT $TODO_FILE

exit 1;

# TODO:
# Delete or rename: /etc/httpd/conf/codex_vhosts.conf
# Delete or rename: /etc/httpd/conf/codex_svnhosts.conf
# Warn that project web site CGI are no longer supported.
