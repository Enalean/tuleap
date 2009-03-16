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
#TODO_FILE=/root/todo_codex_upgrade_3.6.txt
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
echo "Please Make sure you read migration_from_CodeX_3.4_to_Codendi_3.6.README"
echo "*before* running this script!"
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
# Check the machine is running CodeX 3.6
#
OLD_CX_RELEASE='3.6'
yn="y"
$GREP -q "$OLD_CX_RELEASE" $INSTALL_DIR/src/www/VERSION
if [ $? -ne 0 ]; then
    $CAT <<EOF
This machine does not have CodeX ${OLD_CX_RELEASE} installed. Executing this
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
# Stop some services before upgrading
#
echo "Stopping crond, httpd, sendmail, mailman and smb ..."
$SERVICE crond stop
$SERVICE httpd stop
$SERVICE mysqld stop
$SERVICE sendmail stop
$SERVICE mailman stop
$SERVICE smb stop


#############################################
# Make codexadm a member of the apache group
# for phpMyAdmin (session, config files...)
$USERMOD -a -G apache codexadm

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
# Create new tables if needed
echo "- Create new tables in DB"
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


CREATE TABLE IF NOT EXISTS forum_monitored_threads (
  thread_monitor_id int(11) NOT NULL auto_increment,
  forum_id int(11) NOT NULL default '0',
  thread_id int(11) NOT NULL default '0',
  user_id int(11) NOT NULL default '0',
  PRIMARY KEY (thread_monitor_id)
) TYPE=MyISAM;


CREATE TABLE IF NOT EXISTS group_desc (
  group_desc_id INT( 11 ) NOT NULL AUTO_INCREMENT ,
  desc_required BOOL NOT NULL DEFAULT FALSE,
  desc_name VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
  desc_description text CHARACTER SET utf8 COLLATE utf8_general_ci NULL ,
  desc_rank INT( 11 ) NOT NULL DEFAULT '0',
  desc_type ENUM( 'line', 'text' ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'text',
  PRIMARY KEY (group_desc_id),
  UNIQUE (desc_name)
) TYPE=MyISAM;

CREATE TABLE IF NOT EXISTS group_desc_value (
  desc_value_id INT( 11 ) NOT NULL AUTO_INCREMENT ,
  group_id INT( 11 ) NOT NULL ,
  group_desc_id INT( 11 ) NOT NULL ,
  value text CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
  PRIMARY KEY (desc_value_id)
) TYPE=MyISAM;

EOF


##########
# Delete foundry tables
echo "- Delete obsolete tables (foundries)"
$CAT <<EOF | $MYSQL $pass_opt codex

DROP TABLE foundry_data;
DROP TABLE foundry_news;
DROP TABLE foundry_preferred_projects;
DROP TABLE foundry_projects;

EOF

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
'101' , '1', 'project_desc_name:full_desc', 'project_desc_desc:full_desc',
'10', 'text'
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

INSERT INTO group_desc (
group_desc_id ,
desc_required ,
desc_name ,
desc_description ,
desc_rank ,
desc_type
)
VALUES (
'104' , '0', 'project_desc_name:other_comments', 'project_desc_desc:other_comments',
'100', 'text'
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
# Create a Salome Bug Tracker in Default Template Project
echo "- Add a Salome Bug Tracker in Default Template Project"

$PERL <<'EOF'
use DBI;
use Sys::Hostname;
use Carp;

require $ENV{INSTALL_DIR}."/src/utils/include.pl";  # Include all the predefined functions

&load_local_config();

&db_connect;

# Create the tracker 'Salome Bug', and retrieve the tracker ID
$query_insert_tracker = "INSERT INTO artifact_group_list 
                        (group_id, name, description, item_name, allow_copy, submit_instructions, browse_instructions, instantiate_for_new_projects, stop_notification) 
                        VALUES (100, 'Salome Bug', 'Salome Bug Tracker', 'slmbug', 1, NULL, NULL, 1, 0)";
$result_insert_tracker = $dbh->prepare($query_insert_tracker);
$result_insert_tracker->execute();
$tracker_id = $result_insert_tracker->{'mysql_insertid'};

# Create the 3 fieldset, and retrieve the fieldset IDs
$query_insert_fieldset_default = "INSERT INTO artifact_field_set 
                                 (group_artifact_id, name, description, rank) 
                                 VALUES ($tracker_id, 'fieldset_default_slmbugs_lbl_key', 'fieldset_default_slmbugs_desc_key', 10)";
$result_insert_fieldset_default = $dbh->prepare($query_insert_fieldset_default);
$result_insert_fieldset_default->execute();
$default_fieldset_id = $result_insert_fieldset_default->{'mysql_insertid'};

$query_insert_fieldset_status = "INSERT INTO artifact_field_set 
                                (group_artifact_id, name, description, rank) 
                                VALUES ($tracker_id, 'fieldset_status_slmbugs_lbl_key', 'fieldset_status_slmbugs_desc_key', 50)";
$result_insert_fieldset_status = $dbh->prepare($query_insert_fieldset_status);
$result_insert_fieldset_status->execute();
$status_fieldset_id = $result_insert_fieldset_status->{'mysql_insertid'};

$query_insert_fieldset_salome = "INSERT INTO artifact_field_set 
                                (group_artifact_id, name, description, rank) 
                                VALUES ($tracker_id, 'fieldset_salome_slmbugs_lbl_key', 'fieldset_salome_slmbugs_desc_key', 30)";
$result_insert_fieldset_salome = $dbh->prepare($query_insert_fieldset_salome);
$result_insert_fieldset_salome->execute();
$salome_fieldset_id = $result_insert_fieldset_salome->{'mysql_insertid'};

# Create the fields for Salome Bug Tracker
$sql = "INSERT INTO artifact_field VALUES (1, $tracker_id , $default_fieldset_id ,'artifact_id',2,'TF','6/10','Artifact ID','Unique artifact identifier','',0,0,0,1,NULL,'')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field VALUES (2, $tracker_id , $status_fieldset_id ,'status_id',2,'SB','','Status','Artifact Status','',0,0,1,0,NULL,'1')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field VALUES (3, $tracker_id , $default_fieldset_id ,'category_id',2,'SB','','Category','Generally correspond to high level modules or functionalities of your software (e.g. User interface, Configuration Manager, Scheduler, Memory Manager...)','',0,1,1,0,NULL,'100')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field VALUES (4, $tracker_id , $status_fieldset_id ,'assigned_to',5,'SB','','Assigned to','Who is in charge of solving the artifact','',0,1,1,0,'group_members','100')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field VALUES (5, $tracker_id , $default_fieldset_id ,'summary',1,'TF','60/150','Summary','One line description of the artifact','',0,0,1,0,NULL,'')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field VALUES (6, $tracker_id , $default_fieldset_id ,'open_date',4,'DF','','Submitted on','Date and time for the initial artifact submission','',0,0,0,1,'','')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field VALUES (7, $tracker_id , $default_fieldset_id ,'submitted_by',5,'SB','','Submitted by','User who originally submitted the
 artifact','',0,1,0,1,'artifact_submitters','')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field VALUES (8, $tracker_id , $default_fieldset_id ,'severity',2,'SB','','Severity','Impact of the artifact on the system (Critical, Major,...)','',0,0,1,0,NULL,'5')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field VALUES (9, $tracker_id , $default_fieldset_id ,'details',1,'TA','60/7','Original Submission','A full description of the artifact','',0,1,1,0,NULL,'')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field VALUES (10, $tracker_id , $default_fieldset_id ,'comment_type_id',2,'SB','','Comment Type','Specify the nature of the  follow up comment attached to this artifact (Workaround, Test Case, Impacted Files,...)','',0,1,0,1,NULL,'100')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field VALUES (11, $tracker_id , $default_fieldset_id ,'category_version_id',2,'SB','','Component Version','The version of the System Component (aka Category) impacted by the artifact','P',0,1,1,0,NULL,'100')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field VALUES (12, $tracker_id , $default_fieldset_id ,'platform_version_id',2,'SB','','Platform Version','The name and version of the platform your software was running on when the artifact occured (e.g. Solaris 2.8, Linux 2.4, Windows NT4 SP2,...)','P',0,1,1,0,NULL,'100')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field VALUES (13, $tracker_id , $status_fieldset_id ,'reproducibility_id',2,'SB','','Reproducibility','How easy is it to reproduce the artifact','S',0,0,1,0,NULL,'100')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field VALUES (14, $tracker_id , $status_fieldset_id ,'size_id',2,'SB','','Size (loc)','The size of the code you need to develop or rework in order to fix the artifact','S',0,1,1,0,NULL,'100')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field VALUES (15, $tracker_id , $status_fieldset_id ,'fix_release_id',2,'SB','','Fixed Release','The release in which the artifact was actually fixed','P',0,1,1,0,NULL,'100')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field VALUES (16, $tracker_id , $status_fieldset_id ,'resolution_id',2,'SB','','Resolution','How you have decided to fix the artifact (Fixed, Work for me, Duplicate,..)','',0,1,1,0,NULL,'100')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field VALUES (17, $tracker_id , $status_fieldset_id ,'hours',3,'TF','5/5','Effort','Number of hours of work needed to fix the artifact (including testing)','S',0,1,1,0,NULL,'')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field VALUES (18, $tracker_id , $status_fieldset_id ,'plan_release_id',2,'SB','','Planned Release','The release in which you initially planned the artifact to be fixed','P',0,1,1,0,NULL,'100')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field VALUES (19, $tracker_id , $default_fieldset_id ,'component_version',1,'TF','10/40','Component Version','Version of the system component (or work product) impacted by the artifact. Same as the other Component Version field <u>except</u> this one is free text.','S',0,1,1,0,NULL,'')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field VALUES (20, $tracker_id , $default_fieldset_id ,'bug_group_id',2,'SB','','Group','Characterizes the nature of the artifact (e.g. Feature Request, Action Request, Crash Error, Documentation Typo, Installation Problem,...','',0,1,1,0,NULL,'100')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field VALUES (22, $tracker_id , $default_fieldset_id ,'priority',2,'SB','','Priority','How quickly the artifact must be fixed (Immediate, Normal, Low, Later,...)','S',0,1,1,0,NULL,'100')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field VALUES (23, $tracker_id , $default_fieldset_id ,'keywords',1,'TF','60/120','Keywords','A list of comma separated keywords associated with a artifact','S',0,1,1,0,NULL,'')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field VALUES (24, $tracker_id , $default_fieldset_id ,'release_id',2,'SB','','Release','The release (global version number) impacted by the artifact','P',0,1,1,0,NULL,'100')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field VALUES (26, $tracker_id , $default_fieldset_id ,'originator_name',1,'TF','20/40','Originator Name','The name of the person who reported the artifact (if different from the submitter field)','S',0,1,1,0,NULL,'')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field VALUES (27, $tracker_id , $default_fieldset_id ,'originator_email',1,'TF','20/40','Originator Email','Email address of the person who reported the artifact. Automatically included in the artifact email notification process.','S',0,1,1,0,NULL,'')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field VALUES (28, $tracker_id , $default_fieldset_id ,'originator_phone',1,'TF','10/40','Originator Phone','Phone number of the person who reported the artifact','S',0,1,1,0,NULL,'')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field VALUES (29, $tracker_id , $status_fieldset_id ,'close_date',4,'DF','','End Date','End Date','',0,1,0,0,NULL,'')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field VALUES (30, $tracker_id , $status_fieldset_id ,'stage',2,'SB','','Stage','Stage in the life cycle of the artifact','',0,0,1,0,NULL,'1')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field VALUES (31, $tracker_id , $salome_fieldset_id ,'slm_environment',1,'TF','60/150','Environment','Associated Salomé TMF environment','',0,1,1,0,NULL,'')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field VALUES (32, $tracker_id , $salome_fieldset_id ,'slm_campaign',1,'TF','60/150','Campaign','Associated Salomé TMF campaign','',0,1,1,0,NULL,'')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field VALUES (33, $tracker_id , $salome_fieldset_id ,'slm_family',1,'TF','60/150','Family','Associated Salomé TMF family','',0,1,1,0,NULL,'')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field VALUES (34, $tracker_id , $salome_fieldset_id ,'slm_suite',1,'TF','60/150','Suite','Associated Salomé TMF suite','',0,1,1,0,NULL,'')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field VALUES (35, $tracker_id , $salome_fieldset_id ,'slm_test',1,'TF','60/150','Test','Associated Salomé TMF test','',0,1,1,0,NULL,'')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field VALUES (36, $tracker_id , $salome_fieldset_id ,'slm_action',1,'TF','60/150','Action','Associated Salomé TMF action','',0,1,1,0,NULL,'')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field VALUES (37, $tracker_id , $salome_fieldset_id ,'slm_execution',1,'TF','60/150','Execution','Associated Salomé TMF execution','',0,1,1,0,NULL,'')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field VALUES (38, $tracker_id , $salome_fieldset_id ,'slm_dataset',1,'TF','60/150','Data Set','Associated Salomé TMF data set','',0,1,1,0,NULL,'')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field VALUES (39, $tracker_id , $default_fieldset_id ,'slm_priority',2,'SB','','Salome Priority','Salome Priority involved in QSScore calculation. Please do not modify it.','',0,1,1,0,NULL,'2')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field VALUES (40, $tracker_id , $default_fieldset_id ,'last_update_date',4,'DF','','Last Modified On','Date and time of the latest modification in an artifact','',0,0,0,1,'','')";
$result = $dbh->prepare($sql);
$result->execute();

# Field usage for Salome Bug Tracker
$sql = "INSERT INTO artifact_field_usage VALUES (7, $tracker_id ,1,0)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_usage VALUES (6, $tracker_id ,1,0)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_usage VALUES (5, $tracker_id ,1,900)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_usage VALUES (1, $tracker_id ,1,0)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_usage VALUES (4, $tracker_id ,1,50)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_usage VALUES (3, $tracker_id ,1,10)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_usage VALUES (2, $tracker_id ,1,60)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_usage VALUES (30, $tracker_id ,0,0)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_usage VALUES (8, $tracker_id ,1,20)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_usage VALUES (10, $tracker_id ,1,NULL)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_usage VALUES (9, $tracker_id ,1,1000)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_usage VALUES (16, $tracker_id ,1,40)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_usage VALUES (20, $tracker_id ,1,30)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_usage VALUES (11, $tracker_id ,0,0)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_usage VALUES (12, $tracker_id ,0,0)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_usage VALUES (13, $tracker_id ,0,0)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_usage VALUES (14, $tracker_id ,0,0)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_usage VALUES (15, $tracker_id ,0,0)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_usage VALUES (17, $tracker_id ,0,0)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_usage VALUES (18, $tracker_id ,0,0)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_usage VALUES (19, $tracker_id ,0,0)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_usage VALUES (22, $tracker_id ,0,0)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_usage VALUES (23, $tracker_id ,0,0)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_usage VALUES (24, $tracker_id ,0,0)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_usage VALUES (26, $tracker_id ,0,0)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_usage VALUES (27, $tracker_id ,0,0)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_usage VALUES (28, $tracker_id ,0,0)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_usage VALUES (29, $tracker_id ,0,0)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_usage VALUES (31, $tracker_id ,1,20)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_usage VALUES (32, $tracker_id ,1,10)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_usage VALUES (33, $tracker_id ,1,30)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_usage VALUES (34, $tracker_id ,1,50)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_usage VALUES (35, $tracker_id ,1,70)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_usage VALUES (36, $tracker_id ,1,80)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_usage VALUES (37, $tracker_id ,1,40)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_usage VALUES (38, $tracker_id ,1,60)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_usage VALUES (39, $tracker_id ,0,40)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_usage VALUES (40, $tracker_id ,1,5)";
$result = $dbh->prepare($sql);
$result->execute();

# Field value list for Salome Bug Tracker
$sql = "INSERT INTO artifact_field_value_list VALUES (2, $tracker_id ,1,'Open','The artifact has been submitted',20,'P')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_value_list VALUES (2, $tracker_id ,3,'Closed','The artifact is no longer active. See the Resolution field for details on how it was resolved.',400,'P')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_value_list VALUES (30, $tracker_id ,1,'New','The artifact has just been submitted',20,'A')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_value_list VALUES (30, $tracker_id ,2,'Analyzed','The cause of the artifact has been identified and documented',30,'A')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_value_list VALUES (30, $tracker_id ,3,'Accepted','The artifact will be worked on.',40,'A')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_value_list VALUES (30, $tracker_id ,4,'Under Implementation','The artifact is being worked on.',50,'A')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_value_list VALUES (30, $tracker_id ,5,'Ready for Review','Updated/Created non-software work product (e.g. documentation) is ready for review and approval.',60,'A')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_value_list VALUES (30, $tracker_id ,6,'Ready for Test','Updated/Created software is ready to be included in the next build',70,'A')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_value_list VALUES (30, $tracker_id ,7,'In Test','Updated/Created software is in the build and is ready to enter the test phase',80,'A')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_value_list VALUES (30, $tracker_id ,8,'Approved','The artifact fix has been succesfully tested. It is approved and awaiting release.',90,'A')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_value_list VALUES (30, $tracker_id ,9,'Declined','The artifact was not accepted.',100,'A')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_value_list VALUES (30, $tracker_id ,10,'Done','The artifact is closed.',110,'A')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_value_list VALUES (3, $tracker_id ,100,'None','',10,'P')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_value_list VALUES (8, $tracker_id ,1,'1 - Ordinary','',10,'P')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_value_list VALUES (8, $tracker_id ,2,'2','',20,'P')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_value_list VALUES (8, $tracker_id ,3,'3','',30,'P')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_value_list VALUES (8, $tracker_id ,4,'4','',40,'P')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_value_list VALUES (8, $tracker_id ,5,'5 - Major','',50,'P')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_value_list VALUES (8, $tracker_id ,6,'6','',60,'P')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_value_list VALUES (8, $tracker_id ,7,'7','',70,'P')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_value_list VALUES (8, $tracker_id ,8,'8','',80,'P')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_value_list VALUES (8, $tracker_id ,9,'9 - Critical','',90,'P')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_value_list VALUES (10, $tracker_id ,100,'None','',10,'P')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_value_list VALUES (16, $tracker_id ,100,'None','',10,'P')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_value_list VALUES (16, $tracker_id ,1,'Fixed','The bug was resolved',20,'A')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_value_list VALUES (16, $tracker_id ,2,'Invalid','The submitted bug is not valid for some reason (wrong description, using incorrect software version,...)',30,'A')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_value_list VALUES (16, $tracker_id ,3,'Wont Fix','The bug won''t be fixed (probably because it is very minor)',40,'A')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_value_list VALUES (16, $tracker_id ,4,'Later','The bug will be fixed later (no date given)',50,'A')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_value_list VALUES (16, $tracker_id ,5,'Remind','The bug will be fixed later but keep in the remind state for easy identification',60,'A')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_value_list VALUES (16, $tracker_id ,6,'Works for me','The project team was unable to reproduce the bug',70,'A')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_value_list VALUES (16, $tracker_id ,7,'Duplicate','This bug is already covered by another bug description (see related bugs list)',80,'A')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_value_list VALUES (11, $tracker_id ,100,'None','',10,'P')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_value_list VALUES (12, $tracker_id ,100,'None','',10,'P')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_value_list VALUES (13, $tracker_id ,100,'None','',10,'P')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_value_list VALUES (14, $tracker_id ,100,'None','',10,'P')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_value_list VALUES (15, $tracker_id ,100,'None','',10,'P')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_value_list VALUES (18, $tracker_id ,100,'None','',10,'P')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_value_list VALUES (20, $tracker_id ,100,'None','',10,'P')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_value_list VALUES (22, $tracker_id ,100,'None','',10,'P')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_value_list VALUES (24, $tracker_id ,100,'None','',10,'P')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_value_list VALUES (39, $tracker_id ,100,'None','',10,'P')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_value_list VALUES (39, $tracker_id ,1,'Low','',20,'P')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_value_list VALUES (39, $tracker_id ,2,'Normal','',30,'P')";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_field_value_list VALUES (39, $tracker_id ,3,'High','',40,'P')";
$result = $dbh->prepare($sql);
$result->execute();

# Report for Salome Bug Tracker and retrieve the report ID
$query_insert_report = "INSERT INTO artifact_report(group_artifact_id, user_id, name, description, scope, is_default) VALUES ($tracker_id ,100,'Salome Bugs','Salome Bugs Report','P',1)";
$result_insert_report = $dbh->prepare($query_insert_report);
$result_insert_report->execute();
$report_id = $result_insert_report->{'mysql_insertid'};

# Field report field for Salome Bug Tracker
$sql = "INSERT INTO artifact_report_field VALUES ( $report_id ,'assigned_to',1,1,30,40,NULL)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_report_field VALUES ( $report_id ,'status_id',1,0,40,NULL,NULL)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_report_field VALUES ( $report_id ,'artifact_id',0,1,NULL,10,NULL)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_report_field VALUES ( $report_id ,'summary',0,1,NULL,20,NULL)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_report_field VALUES ( $report_id ,'open_date',0,1,NULL,30,NULL)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_report_field VALUES ( $report_id ,'submitted_by',0,1,NULL,50,NULL)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_report_field VALUES ( $report_id ,'slm_environment',1,0,60,NULL,NULL)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_report_field VALUES ( $report_id ,'slm_campaign',1,0,70,NULL,NULL)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_report_field VALUES ( $report_id ,'slm_family',1,0,80,NULL,NULL)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_report_field VALUES ( $report_id ,'slm_suite',1,0,90,NULL,NULL)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_report_field VALUES ( $report_id ,'slm_action',1,0,100,NULL,NULL)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_report_field VALUES ( $report_id ,'slm_test',1,0,110,NULL,NULL)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_report_field VALUES ( $report_id ,'slm_execution',1,0,120,NULL,NULL)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO artifact_report_field VALUES ( $report_id ,'slm_dataset',1,0,130,NULL,NULL)";
$result = $dbh->prepare($sql);
$result->execute();


# Permissions for Salome Bug Tracker
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_ACCESS_FULL','$tracker_id',1)";
$result = $dbh->prepare($sql);
$result->execute();

$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','$tracker_id#3',2)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','$tracker_id#4',2)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','$tracker_id#5',2)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','$tracker_id#8',2)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','$tracker_id#9',2)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','$tracker_id#20',2)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','$tracker_id#31',2)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','$tracker_id#32',2)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','$tracker_id#33',2)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','$tracker_id#34',2)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','$tracker_id#35',2)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','$tracker_id#36',2)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','$tracker_id#37',2)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','$tracker_id#38',2)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','$tracker_id#39',2)";
$result = $dbh->prepare($sql);
$result->execute();

$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','$tracker_id#1',1)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','$tracker_id#2',1)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','$tracker_id#3',1)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','$tracker_id#4',1)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','$tracker_id#5',1)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','$tracker_id#6',1)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','$tracker_id#7',1)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','$tracker_id#8',1)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','$tracker_id#9',1)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','$tracker_id#10',1)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','$tracker_id#11',1)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','$tracker_id#12',1)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','$tracker_id#13',1)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','$tracker_id#14',1)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','$tracker_id#15',1)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','$tracker_id#16',1)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','$tracker_id#17',1)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','$tracker_id#18',1)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','$tracker_id#19',1)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','$tracker_id#20',1)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','$tracker_id#22',1)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','$tracker_id#23',1)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','$tracker_id#24',1)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','$tracker_id#26',1)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','$tracker_id#27',1)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','$tracker_id#28',1)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','$tracker_id#29',1)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','$tracker_id#30',1)";
$result = $dbh->prepare($sql);
$result->execute();

$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','$tracker_id#31',1)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','$tracker_id#32',1)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','$tracker_id#33',1)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','$tracker_id#34',1)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','$tracker_id#35',1)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','$tracker_id#36',1)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','$tracker_id#37',1)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','$tracker_id#38',1)";
$result = $dbh->prepare($sql);
$result->execute();

$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','$tracker_id#39',1)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','$tracker_id#40',1)";
$result = $dbh->prepare($sql);
$result->execute();

$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','$tracker_id#2',3)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','$tracker_id#3',3)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','$tracker_id#4',3)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','$tracker_id#5',3)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','$tracker_id#8',3)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','$tracker_id#9',3)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','$tracker_id#10',3)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','$tracker_id#11',3)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','$tracker_id#12',3)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','$tracker_id#13',3)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','$tracker_id#14',3)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','$tracker_id#15',3)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','$tracker_id#16',3)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','$tracker_id#17',3)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','$tracker_id#18',3)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','$tracker_id#19',3)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','$tracker_id#20',3)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','$tracker_id#22',3)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','$tracker_id#23',3)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','$tracker_id#24',3)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','$tracker_id#26',3)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','$tracker_id#27',3)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','$tracker_id#28',3)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','$tracker_id#29',3)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','$tracker_id#30',3)";
$result = $dbh->prepare($sql);
$result->execute();

$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','$tracker_id#31',3)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','$tracker_id#32',3)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','$tracker_id#33',3)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','$tracker_id#34',3)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','$tracker_id#35',3)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','$tracker_id#36',3)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','$tracker_id#37',3)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','$tracker_id#38',3)";
$result = $dbh->prepare($sql);
$result->execute();
$sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','$tracker_id#39',3)";
$result = $dbh->prepare($sql);
$result->execute();


$sql = "UPDATE salome.CONFIG SET VALEUR = '$tracker_id' WHERE CONFIG.CLE = 'cx.trk.grp_art_id' AND CONFIG.id_projet =0000000100 AND CONFIG.id_personne =0000000000";
$result = $dbh->prepare($sql);
$result->execute();

$sql = "UPDATE salome.CONFIG SET VALEUR = '$report_id' WHERE CONFIG.CLE = 'cx.trk.env.report_id' AND CONFIG.id_projet =0000000100 AND CONFIG.id_personne =0000000000";
$result = $dbh->prepare($sql);
$result->execute();

EOF

##########
# Install IM plugin
echo "- Add IM plugin schema"
$CAT $INSTALL_DIR/plugins/IM/db/install.sql | $MYSQL $pass_opt codex
# Don't need to initialize Jabbex: it should have been done during 3.6 install.

echo "- Install IM plugin"
$CAT <<EOF | $MYSQL $pass_opt codex

INSERT INTO plugin (name, available) VALUES ('IM', '1');

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
    echo "Scrum Backlog tracker's installation skipped !"
else
    echo "Installing Scrum Backlog tracker ..."
    $CAT <<EOF | $PHP
    <?php
    require_once('$INSTALL_DIR/codex_tools/tracker_migration_from_CodeX_34_to_36.php');
    ?>
EOF
    echo "Scrum Backlog tracker installation completed !"
fi

##############################################
# Create default graphic reports


read -p "Create default graphic reports for existing trackers ? [yn]: " yn
if [ "$yn" = "n" ]; then
    echo "No graphic report created !"
else
    echo "Installing Default graphic reports for existing trackers ..."

$PERL <<'EOF'
use DBI;
use Sys::Hostname;
use Carp;

require $ENV{INSTALL_DIR}."/src/utils/include.pl";  # Include all the predefined functions

&load_local_config();

&db_connect;

sub create_chart {
    my ($report_graphic_id,$rank,$type,$title,$desc) = @_;
    $chart_qy="INSERT INTO plugin_graphontrackers_chart (report_graphic_id, rank, chart_type, title, description, width, height)
                                                 VALUES ($report_graphic_id, $rank, '$type', '$title', '$desc', 600, 400)";
    $chart_c = $dbh->prepare($chart_qy);
    $chart_c->execute();
    # get last inserted ID
    return $chart_c->{'mysql_insertid'};
}

sub create_pie {
    my ($report_graphic_id,$rank,$field_name,$field_label) = @_;
    $chart_id=create_chart($report_graphic_id,$rank,"pie",$field_label,"");
    $pie_qy="INSERT INTO plugin_graphontrackers_pie_chart (id, field_base)
                                                   VALUES ($chart_id, '$field_name')";
    $pie_c = $dbh->prepare($pie_qy);
    $pie_c->execute();
}

sub create_bar {
    my ($report_graphic_id,$rank,$field_name1,$field_label1,$field_name2,$field_label2) = @_;
    $chart_id=create_chart($report_graphic_id,$rank,"bar","$field_label1 / $field_label2","");
    $bar_qy="INSERT INTO plugin_graphontrackers_bar_chart (id, field_base, field_group)
                                                   VALUES ($chart_id, '$field_name1', '$field_name2')";
    $bar_c = $dbh->prepare($bar_qy);
    $bar_c->execute();
}


sub create_charts {
    my ($group_artifact_id, $group_id) = @_;
    # create chart report
    $rep_qy="INSERT INTO plugin_graphontrackers_report_graphic (group_artifact_id, user_id, name, description, scope)
                                                        VALUES ($group_artifact_id, 100, \"Default\", \"Default Graphic Report\", 'P')";
    $rep_c = $dbh->prepare($rep_qy);
    $rep_c->execute();
    # get last inserted ID
    $report_graphic_id = $rep_c->{'mysql_insertid'};

    # severity
    $field_qy="SELECT af.label FROM artifact_field_usage afu, artifact_field af WHERE af.group_artifact_id=$group_artifact_id and af.field_name='severity' AND af.data_type=2 AND afu.group_artifact_id=$group_artifact_id AND afu.field_id=af.field_id AND use_it=1";
    $field_c = $dbh->prepare($field_qy);
    $field_c->execute();
    my ($label) = $field_c->fetchrow();
    if ($label ne "") {
            create_pie($report_graphic_id ,1,"severity",$label);
    }
    # Category by status
    $field_qy="SELECT af.label FROM artifact_field_usage afu, artifact_field af WHERE af.group_artifact_id=$group_artifact_id and af.field_name='category_id' AND af.data_type=2 AND afu.group_artifact_id=$group_artifact_id AND afu.field_id=af.field_id AND use_it=1";
    $field_c = $dbh->prepare($field_qy);
    $field_c->execute();
    my ($label) = $field_c->fetchrow();
    if ($label ne "") {
            create_bar($report_graphic_id ,2,"category_id",$label,"status_id","Status");
    }

    # Assigned to by status
    $field_qy="SELECT af.label FROM artifact_field_usage afu, artifact_field af WHERE af.group_artifact_id=$group_artifact_id and af.field_name='assigned_to' AND af.data_type=5 AND afu.group_artifact_id=$group_artifact_id AND afu.field_id=af.field_id AND use_it=1";
    $field_c = $dbh->prepare($field_qy);
    $field_c->execute();
    my ($label) = $field_c->fetchrow();
    if ($label ne "") {
            create_bar($report_graphic_id ,3,"assigned_to",$label,"status_id","Status");
    }

    # Multi-Assigned to by status
    $field_qy="SELECT af.label FROM artifact_field_usage afu, artifact_field af WHERE af.group_artifact_id=$group_artifact_id and af.field_name='multi_assigned_to' AND af.data_type=5 AND afu.group_artifact_id=$group_artifact_id AND afu.field_id=af.field_id AND use_it=1";
    $field_c = $dbh->prepare($field_qy);
    $field_c->execute();
    my ($label) = $field_c->fetchrow();
    if ($label ne "") {
            create_bar($report_graphic_id ,4,"multi_assigned_to",$label,"status_id","Status");
    }
}


# For each tracker, create default charts for some fields
$tracker_qy = "SELECT group_artifact_id, group_id from artifact_group_list WHERE status='A'";
$tracker_c = $dbh->prepare($tracker_qy);
$tracker_c->execute();
while (my ($group_artifact_id, $group_id) = $tracker_c->fetchrow()) {
        create_charts($group_artifact_id, $group_id);
}


EOF
    echo "Graphic reports created !"
fi

###############################################################################
# call the graphic report migration scripts
current_dir=`pwd`
cd /usr/share/codex/src/updates
php update.php 010_graphontrackers_integration
cd $current_dir

###############################################################################
# Run 'analyse' on all MySQL DB
echo "Analyzing and optimizing MySQL databases (this might take a few minutes)"
mysqlcheck -Aaos $pass_opt

###############################################################################
echo "Updating local.inc"

# jpgraph
$GREP -q ^\$jpgraph_dir  $ETC_DIR/conf/local.inc
if [ $? -ne 0 ]; then
  # Remove end PHP marker
  substitute '/etc/codex/conf/local.inc' '\?\>' ''

  $CAT <<EOF >> /etc/codex/conf/local.inc
// 3rd Party libraries
\$jpgraph_dir = "/usr/share/jpgraph";

?>
EOF
fi

# $sys_use_trove
$GREP -q ^\$sys_use_trove  $ETC_DIR/conf/local.inc
if [ $? -ne 0 ]; then
  # Remove end PHP marker
  substitute '/etc/codex/conf/local.inc' '\?\>' ''

  $CAT <<EOF >> /etc/codex/conf/local.inc
// Enable trove categorization (project tree/software map)
// (1 to enable, 0 to disable)
\$sys_use_trove = 1;

?>
EOF
fi

# $sys_use_snippet
$GREP -q ^\$sys_use_snippet  $ETC_DIR/conf/local.inc
if [ $? -ne 0 ]; then
  # Remove end PHP marker
  substitute '/etc/codex/conf/local.inc' '\?\>' ''

  $CAT <<EOF >> /etc/codex/conf/local.inc
// Enable code snippet library
// (1 to enable, 0 to disable)
\$sys_use_snippet = 1;

?>
EOF
fi

# Comment obsolete variables
$GREP -q ^\$sys_cvs_host  $ETC_DIR/conf/local.inc
if [ $? -eq 0 ]; then
  substitute '/etc/codex/conf/local.inc' '\$sys_cvs_host' '//\$sys_cvs_host'
fi
$GREP -q ^\$sys_svn_host  $ETC_DIR/conf/local.inc
if [ $? -eq 0 ]; then
  substitute '/etc/codex/conf/local.inc' '\$sys_svn_host' '//\$sys_svn_host'
fi
$GREP -q ^\$sys_download_host  $ETC_DIR/conf/local.inc
if [ $? -eq 0 ]; then
  substitute '/etc/codex/conf/local.inc' '\$sys_download_host' '//\$sys_download_host'
fi
$GREP -q ^\$sys_shell_host  $ETC_DIR/conf/local.inc
if [ $? -eq 0 ]; then
  substitute '/etc/codex/conf/local.inc' '\$sys_shell_host' '//\$sys_shell_host'
fi
$GREP -q ^\$sys_users_host  $ETC_DIR/conf/local.inc
if [ $? -eq 0 ]; then
  substitute '/etc/codex/conf/local.inc' '\$sys_users_host' '//\$sys_users_host'
fi
$GREP -q ^\$sys_stay_in_ssl  $ETC_DIR/conf/local.inc
if [ $? -eq 0 ]; then
  substitute '/etc/codex/conf/local.inc' '\$sys_stay_in_ssl' '//\$sys_stay_in_ssl'
fi


# database.inc
$GREP -q ^\$sys_enablessl  $ETC_DIR/conf/database.inc
if [ $? -ne 0 ]; then
  # Remove end PHP marker
  substitute '/etc/codex/conf/database.inc' '\?\>' ''

  $CAT <<EOF >> /etc/codex/conf/local.inc

// If set to 1 (one) connexions to DB are made through SSL.
// Note: Mysql server must be properly configured to accept SSL
// connexions (GRANT ... REQUIRE SSL). This is the only way to
// guaranty that communications are encrypted.
\$sys_enablessl="0";


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
echo "SELECT v.path FROM plugin_docman_item i INNER JOIN plugin_docman_version v USING(item_id) WHERE i.item_type = 4" | \
  $MYSQL $pass_opt codex | \
  sed -e "/^path$/d" | \
  awk '{ system("/usr/share/codex/codex_tools/utils/iso-8859-1_to_utf-8.sh "$0) }'

echo "Convert your site-content to utf-8"
find /etc/codex/ -type f  \
                 -wholename "*/site-content/*" \
                 -not -wholename "*/.svn/*" \
                 -exec /usr/share/codex/codex_tools/utils/iso-8859-1_to_utf-8.sh {} \;

##############################################
# Upgrade to SVN 1.5
#
echo "Upgrade repositories to SVN 1.5"
find /svnroot/ -maxdepth 1 -name "*" -exec sudo -u codexadm svnadmin upgrade {} \; >/dev/null

##############################################
# Restarting some services
#
echo "Starting services..."
$SERVICE crond start
$SERVICE httpd start
$SERVICE sendmail start
$SERVICE mailman start
$SERVICE smb start



# End of it
echo "=============================================="
echo "Migration completed succesfully!"

exit 1;


# DNS
# Add wildcard at the end of codex_full.zone and
# ask to cleanup all the entries.
