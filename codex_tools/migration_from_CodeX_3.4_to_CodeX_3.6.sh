#!/bin/bash
#
# Copyright (c) Xerox Corporation, CodeX, Codendi 2007-2008.
# This file is licensed under the GNU General Public License version 2. See the file COPYING. 
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
PHP='/usr/bin/php'

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
  $RPM -q centos-release-${RH_RELEASE}* 2>/dev/null 1>&2
  if [ $? -eq 1 ]; then
    cat <<EOF
This machine is not running RedHat Enterprise Linux ${RH_RELEASE}. Executing this install
script may cause data loss or corruption.
EOF
read -p "Continue? [yn]: " yn
  else
    echo "Running on CentOS ${RH_RELEASE}... good!"
  fi
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
echo "Stopping crond, httpd, sendmail, mailman and smb ..."
$SERVICE crond stop
$SERVICE httpd stop
$SERVICE mysqld stop
$SERVICE sendmail stop
$SERVICE mailman stop
$SERVICE smb stop


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
   if [ $one_has_been_found -eq 0 ]; then
      echo "  The following files differ from the site-content of CodeX:"
      one_has_been_found=1
   fi
   echo "    $i"
done

if [ $one_has_been_found -eq 1 ]; then
   echo "  Please check those files"
fi

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
$CAT <<EOF | $PHP
<?php

require_once('$INSTALL_DIR/src/common/dao/DBTablesDao.class.php');
require_once('$INSTALL_DIR/src/common/dao/DBDatabasesDao.class.php');
require_once('$INSTALL_DIR/src/common/dao/include/DataAccess.class.php');

\$da = new DataAccess('', 'root', '$old_passwd', 'codex');
\$tables_dao = new DBTablesDao(\$da);

\$db_dao = new DBDatabasesDao(\$da);
foreach(\$db_dao->searchAll() as \$db) {
    \$db = \$db['Database'];
    if (\$db == 'codex' || preg_match('/^cx_/', \$db)) {
        echo " + ". \$db;
        \$tables_dao->update('USE '. \$db);
        foreach(\$tables_dao->searchAll() as \$row) {
            \$tables_dao->convertToUTF8(\$row['Tables_in_'. \$db]);
            echo ".";
            flush();
        }
        \$db_dao->setDefaultCharsetUTF8(\$db);
        echo " done\n";
    } else {
        echo ' ! Ignoring '. \$db ."\n";
    }
}
?>
EOF

#########
# story #15757 Project Description custom fields
echo "- Add Project Description custom fields. See revision #8610"
$CAT <<EOF | $MYSQL $pass_opt codex

INSERT INTO group_desc (
group_desc_id ,
desc_required ,
desc_name ,
desc_description ,
desc_rank ,
desc_type
)
VALUES (
'102' , '0', 'project_desc_name:int_prop', 'project_desc_desc:int_prop',
'20', 'text'
);

INSERT INTO group_desc (
group_desc_id ,
desc_required ,
desc_name ,
desc_description ,
desc_rank ,
desc_type
)
VALUES (
'103' , '0', 'project_desc_name:req_soft', 'project_desc_desc:req_soft',
'30', 'text'
);


INSERT INTO group_desc_value( group_desc_id, group_id, value ) 
(
SELECT group_desc_id, group_id,
REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(register_purpose, '&nbsp;', ' '), '&quot;', '"'), '&gt;', '>'), '&lt;', '<'), '&amp;', '&')
FROM group_desc, groups
WHERE group_desc.desc_name = 'project_desc_name:full_desc'
AND groups.register_purpose != ''
) ; 

INSERT INTO group_desc_value( group_desc_id, group_id, value ) (
SELECT group_desc_id, group_id,
REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(patents_ips, '&nbsp;', ' '), '&quot;', '"'), '&gt;', '>'), '&lt;', '<'), '&amp;', '&')
FROM group_desc, groups
WHERE group_desc.desc_name = 'project_desc_name:int_prop'
AND groups.patents_ips != ''
) ;

INSERT INTO group_desc_value( group_desc_id, group_id, value ) (
SELECT group_desc_id, group_id,
REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(required_software, '&nbsp;', ' '), '&quot;', '"'), '&gt;', '>'), '&lt;', '<'), '&amp;', '&')
FROM group_desc, groups
WHERE group_desc.desc_name = 'project_desc_name:req_soft'
AND groups.required_software != ''
) ;

INSERT INTO group_desc_value( group_desc_id, group_id, value ) (
SELECT group_desc_id, group_id,
REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(other_comments, '&nbsp;', ' '), '&quot;', '"'), '&gt;', '>'), '&lt;', '<'), '&amp;', '&')
FROM group_desc, groups
WHERE group_desc.desc_name = 'project_desc_name:other_comments'
AND groups.other_comments != ''
) ;

ALTER TABLE groups 
    DROP register_purpose,
    DROP required_software,
    DROP patents_ips,
    DROP other_comments;
EOF
##########
# SR #147
echo "- SR #147"
$CAT <<EOF | $MYSQL $pass_opt codex
CREATE TABLE IF NOT EXISTS forum_monitored_threads (
  thread_monitor_id int(11) NOT NULL auto_increment,
  forum_id int(11) NOT NULL default '0',
  thread_id int(11) NOT NULL default '0',
  user_id int(11) NOT NULL default '0',
  PRIMARY KEY (thread_monitor_id)
);
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
# Add column is_default in artifact_report table
echo "- Add column is_default in artifact_report table. See SR #1160 and revision #8009 "
$CAT <<EOF | $MYSQL $pass_opt codex

ALTER TABLE artifact_report ADD COLUMN is_default INT(11) NOT NULL DEFAULT 0 AFTER scope

EOF

##########
# Add fields in user table (already in 3.4 security)
echo "- Add fields in user table (already in 3.4 security)"
$CAT <<EOF | $MYSQL $pass_opt codex | grep -q prev_auth_success
SHOW COLUMNS FROM user LIKE 'prev_auth_success';
EOF
if [ $? -ne 0 ]; then
  $CAT <<EOF | $MYSQL $pass_opt codex
ALTER TABLE user ADD COLUMN prev_auth_success INT(11) NOT NULL DEFAULT 0;
EOF
fi

$CAT <<EOF | $MYSQL $pass_opt codex | grep -q last_auth_success
SHOW COLUMNS FROM user LIKE 'last_auth_success';
EOF
if [ $? -ne 0 ]; then
  $CAT <<EOF | $MYSQL $pass_opt codex
ALTER TABLE user ADD COLUMN last_auth_success INT(11) NOT NULL DEFAULT 0;
EOF
fi

$CAT <<EOF | $MYSQL $pass_opt codex | grep -q last_auth_failure
SHOW COLUMNS FROM user LIKE 'last_auth_failure';
EOF
if [ $? -ne 0 ]; then
  $CAT <<EOF | $MYSQL $pass_opt codex
ALTER TABLE user ADD COLUMN last_auth_failure INT(11) NOT NULL DEFAULT 0;
EOF
fi

$CAT <<EOF | $MYSQL $pass_opt codex | grep -q nb_auth_failure
SHOW COLUMNS FROM user LIKE 'nb_auth_failure';
EOF
if [ $? -ne 0 ]; then
  $CAT <<EOF | $MYSQL $pass_opt codex
ALTER TABLE user ADD COLUMN nb_auth_failure INT(11) NOT NULL DEFAULT 0;
EOF
fi

##########
# add expiry_date field in user table
echo "- Add expiry_date field in user table"
$CAT <<EOF | $MYSQL $pass_opt codex

ALTER TABLE user ADD COLUMN expiry_date int(11)

EOF

##########
# Install GraphOnTrackers plugin
echo "- Add GraphonTrackers plugin schema"
$CAT $INSTALL_DIR/plugins/graphontrackers/db/install.sql | $MYSQL $pass_opt codex

echo "- Install GraphonTrackers plugin"
$CAT <<EOF | $MYSQL $pass_opt codex

INSERT INTO plugin (name, available) VALUES ('graphontrackers', '1');

EOF

##########
# Install Salomé plugin
echo "- Add Salomé plugin schema"
$CAT $INSTALL_DIR/plugins/salome/db/install.sql | $MYSQL $pass_opt codex

echo "- Install Salomé plugin"
$CAT <<EOF | $MYSQL $pass_opt codex

INSERT INTO plugin (name, available) VALUES ('salome', '1');

EOF

##########
# Install IM plugin
echo "- Add IM plugin schema"
$CAT $INSTALL_DIR/plugins/IM/db/install.sql | $MYSQL $pass_opt codex

echo "- Install IM plugin"
$CAT <<EOF | $MYSQL $pass_opt codex

INSERT INTO plugin (name, available) VALUES ('IM', '1');

EOF

##########
# Create table for Cross references 
echo "- Create Table in DB for Cross References "
$CAT <<EOF | $MYSQL $pass_opt codex

CREATE TABLE IF NOT EXISTS cross_references (
  id int(11) unsigned NOT NULL AUTO_INCREMENT, 
  created_at INT(11) NOT NULL DEFAULT '0',
  user_id INT(11) unsigned NOT NULL DEFAULT '0',
  source_type VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
  source_id INT(11) unsigned NOT NULL DEFAULT '0',
  source_gid INT(11) unsigned NOT NULL DEFAULT '0',
  target_type VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
  target_id INT(11) unsigned NOT NULL DEFAULT '0',
  target_gid INT(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (id)
  
) TYPE=MyISAM;



EOF


#########
# Clear phpwiki cache (To force regeneration in utf8)
echo "- Clear phpwiki cache"
$CAT <<EOF | $MYSQL $pass_opt codex

UPDATE wiki_page SET cached_html = '';

EOF

##############################################
# Scrum Backlog tracker install

read -p "Install the Scrum Backlog tracker ? [yn]: " yn
if [ "$yn" = "n" ]; then
    echo "Scrum Backlog tracker's installation skiped !"
else
    echo "Installing Scrum Backlog tracker ..."
    $CAT <<EOF | $PHP
    <?php
    require_once('$INSTALL_DIR/codex_tools/tracker_migration_from_CodeX_34_to_36.php');
    ?>
EOF
    echo "Scrum Backlog tracker installation completed !"
fi

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
# Fix SELinux contexts if needed
#
echo "Update SELinux contexts if needed"
cd $INSTALL_DIR/src/utils
./fix_selinux_contexts.pl

##############################################
# Convert to utf8 existing content
#
echo "Convert embedded files to utf8"
mysql -u codexadm --password=g7loi9re codex \
  -e "SELECT v.path FROM plugin_docman_item i INNER JOIN plugin_docman_version v USING(item_id) WHERE i.item_type = 4" | \
  sed -e "/^path$/d" | \
  awk '{ system("/usr/share/codex/codex_tools/utils/iso-8859-1_to_utf-8.sh "$0) }'

echo "Convert your site-content to utf-8"
find /etc/codex/ -type f 
                 -wholename "*/site-content/*" 
                 -not -wholename "*/.svn/*" 
                 -exec /usr/share/codex/codex_tools/utils/iso-8859-1_to_utf-8.sh {} \;


##############################################
# Restarting some services
#
echo "Starting services..."
$SERVICE crond start
$SERVICE httpd start
$SERVICE sendmail start
$SERVICE mailman start
$SERVICE smb start

todo "The new Graphontrackers Plugin is available, no graphical reports for your site has presently been created"
todo "You can create your own reports for each (template) tracker via the trackers administration menu."
todo "To use the Gannt graph with the task tracker, you will have to :"
todo "  - rename the old 'end date' field into 'close date' or so on."
todo "  - create an 'end date' and a 'due date' field for the task tracker"
todo "  - create a 'progress' field, type INT and display TextField for the task tracker, with value between 0-100 (percentage of completion)" 
todo ""
todo "CodeX is now UTF-8. Please check that your iso-8859-1 files have been properly converted to utf-8 (site-content, themes, docman embedded files)."
todo ""
todo "Salomé and Instant Messaging have been installed. If you don't want to use them, please uninstall corresponding plugins through the PluginsAdministration. Or with the following statement:"
todo "  - DELETE FROM plugin WHERE name = 'IM'"
todo "Don't forget to remove also the rpms (openfire, salome)"
todo ""
todo "Groups has not been synchronized for Instant Messaging. Please go to admin > Instant Messaging and synchronize groups."
todo ""
todo "If you have custom themes:"
todo "  -New icons: add.png, monitor_forum.png, monitor_thread.png, right_arrow.png, left_arrow.png, both_arrows.png, cal.png, delete.png. You may copy them from /usr/share/codex/src/www/themes/CodeXTab/images/ic"
todo "  -New image: backstripes.gif. You may copy them from /usr/share/codex/src/www/themes/CodeXTab/images"
todo "  -Updated CSS: Everything below the line '/* {{{ Date Picker */' in /usr/share/codex/src/www/themes/CodeXTab/css/style.css should be added to your style.css."
todo "  -Please update your theme layout class according to the modifications done in Layout.class.php and TabbedLayout.class.php"
todo "    > https://partners.xrce.xerox.com/svn/viewvc.php/dev/trunk/src/www/include/Layout.class.php?r1=9106&r2=7209&roottype=svn&root=codex&diff_format=l"
todo "    > https://partners.xrce.xerox.com/svn/viewvc.php/dev/trunk/src/www/include/TabbedLayout.class.php?r1=9068&r2=7209&roottype=svn&root=codex&diff_format=l"
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

# DNS
# Add wildcard at the end of codex_full.zone and
# ask to cleanup all the entries.

# SVN 1.5
