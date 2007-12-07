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
# CodeX 3.2 to 3.4 migration
##############################################
echo "Migration script from CodeX 3.2 to CodeX 3.4"
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
$MV /etc/httpd/conf.d/subversion.conf /etc/httpd/conf.d/subversion.conf.3.4.codex
echo "Installing Subversion 1.4.4 RPMs for CodeX...."
cd ${RPMS_DIR}/subversion
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
$RPM -Uvh ${newest_rpm}/apr-0*.i386.rpm ${newest_rpm}/apr-util-0*.i386.rpm \
     ${newest_rpm}/subversion-1.*.i386.rpm  ${newest_rpm}/mod_dav_svn*.i386.rpm \
     ${newest_rpm}/subversion-perl*.i386.rpm ${newest_rpm}/subversion-python*.i386.rpm \
     ${newest_rpm}/subversion-tools*.i386.rpm

$CP -a /etc/httpd/conf.d/subversion.conf.3.4.codex /etc/httpd/conf.d/subversion.conf


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


echo "Starting DB update for CodeX 3.4 This might take a few minutes."


###############################################################################
# SR #283 - Remove trove_treesums table 

echo "- removing trove_treesums table"
$CAT <<EOF | $MYSQL $pass_opt codex
DROP TABLE IF EXISTS trove_treesums;
EOF


#
echo "- CodeX 3.2 updates"

# fix for SR #923
# Check if column already exists
$CAT <<EOF | $MYSQL $pass_opt codex | grep -q last_update_date
SHOW COLUMNS FROM artifact LIKE 'last_update_date';
EOF
if [ $? -ne 0 ]; then
  $CAT <<EOF | $MYSQL $pass_opt codex
ALTER TABLE artifact ADD COLUMN last_update_date INT(11) UNSIGNED NOT NULL default '0' AFTER close_date
EOF
fi


# see rev #6417
# Check if column already exists
$CAT <<EOF | $MYSQL $pass_opt codex | grep -q last_pwd_update
SHOW COLUMNS FROM user LIKE 'last_pwd_update';
EOF
if [ $? -ne 0 ]; then
  timestamp=`date +%s`
  $CAT <<EOF | $MYSQL $pass_opt codex
ALTER TABLE user ADD COLUMN last_pwd_update INT(11) UNSIGNED NOT NULL default '0'
UPDATE user SET last_pwd_update = $timestamp
EOF
fi
$CAT <<EOF | $MYSQL $pass_opt codex | grep -q last_access_date
SHOW COLUMNS FROM user LIKE 'last_access_date';
EOF
if [ $? -ne 0 ]; then
  $CAT <<EOF | $MYSQL $pass_opt codex
ALTER TABLE user ADD COLUMN last_access_date INT(11) UNSIGNED NOT NULL default '0'
EOF
fi

###############################################################################
# This should have been done earlier. Nevertheless, fix remaining shells if any

echo "- fix shell paths"
$CAT <<EOF | $MYSQL $pass_opt codex
UPDATE user SET shell='/usr/lib/codex/bin/cvssh-restricted' WHERE shell='/usr/local/bin/cvssh-restricted';
UPDATE user SET shell='/usr/lib/codex/bin/cvssh' WHERE shell='/usr/local/bin/cvssh';
EOF

###############################################################################
# This was forgotten in CodeX 3.2 migration script (see rev #5671 and SR #941)
echo "- fix typo in file reference link"
$CAT <<EOF | $MYSQL $pass_opt codex
UPDATE reference SET link='/file/showfiles.php?group_id=$group_id&release_id=$1' WHERE keyword='release' AND id='16';
EOF


# SR #772 - Rename 'release' field from legacy tracker to 'release_name' to avoid conflict in MySQL 5
echo "- rename 'release' legacy field"
$CAT <<EOF | $MYSQL $pass_opt codex

ALTER TABLE bug CHANGE release release_name varchar(255) NOT NULL default '';
UPDATE bug_field SET field_name='release_name' where field_name='release';
EOF


# SR #894 - Project long name way too short
echo "- increase group_name size"
$CAT <<EOF | $MYSQL $pass_opt codex
ALTER TABLE groups CHANGE group_name group_name VARCHAR( 255 ) DEFAULT NULL;
EOF


# Support for more than 4GB table in MySQL -> 1TB
echo "- increase size of artifact_file table"
$CAT <<EOF | $MYSQL $pass_opt codex
ALTER TABLE artifact_file MAX_ROWS = 1000000 AVG_ROW_LENGTH = 1000000;
EOF


# Found in SR #904
echo "- add index on svn_commits table (ignore errors)"
$CAT <<EOF | $MYSQL $pass_opt codex
ALTER TABLE svn_commits ADD INDEX idx_search (group_id, whoid, id);
EOF

# Small speedup in history export. See SR #837
echo "- modify index on artifact_field table (ignore errors)"
$CAT <<EOF | $MYSQL $pass_opt codex
ALTER TABLE artifact_field DROP INDEX idx_grp_name;
ALTER TABLE artifact_field ADD INDEX idx_fname_grp(field_name(20), group_artifact_id);
EOF


###############################################################################
# Update file release system, delete frs_status table
echo "- drop frs_status table"
$CAT <<EOF | $MYSQL $pass_opt codex
DROP TABLE IF EXISTS frs_status;
EOF


###############################################################################
# Allow to temporarily disable tracker notifications. See SR #890
echo "- add stop_notification column in artifact_group_list"

# Check if column already exists
$CAT <<EOF | $MYSQL $pass_opt codex | grep -q stop_notification
SHOW COLUMNS FROM artifact_group_list LIKE 'stop_notification';
EOF
if [ $? -ne 0 ]; then
  $CAT <<EOF | $MYSQL $pass_opt codex
ALTER TABLE artifact_group_list ADD stop_notification INT(11) NOT NULL DEFAULT '0' AFTER instantiate_for_new_projects;
EOF
fi



###############################################################################
# Update docman plugin
# Check if docman is installed
$CAT <<EOF | $MYSQL $pass_opt codex | grep -q docman
SELECT * FROM plugin WHERE name = 'docman';
EOF
if [ $? -eq 0 ]; then

echo "- document manager update"
$CAT <<EOF | $MYSQL $pass_opt codex

DROP TABLE IF EXISTS plugin_docman_report;
CREATE TABLE plugin_docman_report (
  report_id       int(11) NOT NULL auto_increment,
  name            varchar(255) NULL,
  title           varchar(255) NULL,
  group_id        int(11) NOT NULL,
  user_id         int(11) NOT NULL DEFAULT 100,
  item_id         int(11) NULL,
  scope           char(1) NOT NULL default 'I',
  is_default      tinyint(1) NOT NULL default 0,
  advanced_search tinyint(1) NOT NULL default 0,
  description     text NULL,
  image           int(11)NULL, 
  PRIMARY KEY (report_id),
  INDEX group_idx (group_id),
  INDEX user_idx (user_id)
);

DROP TABLE IF EXISTS plugin_docman_report_filter;
CREATE TABLE plugin_docman_report_filter (
  report_id     INT(11) NOT NULL,
  label         VARCHAR(255) NOT NULL,
  value_love    INT(11) NULL,
  value_string  VARCHAR(255) NULL,
  value_date1   VARCHAR(32) NULL,
  value_date2   VARCHAR(32) NULL,
  value_date_op tinyint(2) NULL,
  INDEX report_label_idx(report_id, label(10))
);

alter table plugin_docman_metadata_value add FULLTEXT fltxt (valueText, valueString);
alter table plugin_docman_metadata_value add FULLTEXT fltxt_txt (valueText);
alter table plugin_docman_metadata_value add FULLTEXT fltxt_str (valueString);

alter table plugin_docman_item add FULLTEXT fltxt_title (title);
alter table plugin_docman_item add FULLTEXT fltxt_description (description);
alter table plugin_docman_item add FULLTEXT fltxt (title, description);

alter table plugin_docman_version add FULLTEXT fltxt (label, changelog, filename);

update plugin_docman_item set status = status + 100;
alter table plugin_docman_item change column status status TINYINT(4) DEFAULT 100 NOT NULL;

ALTER TABLE plugin_docman_metadata DROP PRIMARY KEY, ADD PRIMARY KEY  (field_id);
ALTER TABLE plugin_docman_metadata DROP INDEX idx_name;

EOF

$PERL <<'EOF'
use DBI;
require $ENV{INSTALL_DIR}."/src/utils/include.pl";  # Include all the predefined functions
&db_connect;

sub add_index {
    my ($query, $c, $type);
    my ($tablename) = $_[0];
    my ($indexname) = $_[1];
    my ($columns)   = $_[2];
    $type = ('', $_[3])[defined($_[3])];
    drop_index($tablename, $indexname);

    $query = "CREATE ". $type ." INDEX ". $indexname ." ON ". $tablename ."(". $columns .")";
    # print $query ."\n";
    $c = $dbh->prepare($query);
    $c->execute();
}
sub drop_index {
    my ($query, $c);
    my ($tablename) = $_[0];
    my ($indexname) = $_[1];

    if (index_exists($tablename, $indexname) eq 1) {
        $query = "DROP INDEX ". $indexname ." ON ". $tablename;
        # print $query ."\n";
        $c = $dbh->prepare($query);
        $c->execute();
    }
}
sub index_exists {
    my ($query, $c);
    my ($found)     = 0;
    my ($tablename) = $_[0];
    my ($indexname) = $_[1];

    $query = "SHOW INDEX FROM ".$tablename;
    $c = $dbh->prepare($query);
    $c->execute();
    my($table, $non_unique, $key_name, $seq_in_index, $column_name, $collation, $cardinality, $sub_part, $packed, $null, $index_type, $comment);
    $c->bind_columns( undef, \$table, \$non_unique, \$key_name, \$seq_in_index, \$column_name, \$collation, \$cardinality, \$sub_part, \$packed, \$null, \$index_type, \$comment);
    while($found eq 0 and $c->fetch()) {
        if ($key_name eq $indexname) {
            $found = 1;
        }
    }
    return $found;
}

drop_index('plugin_docman_metadata_love', 'idx_fv_value_id');
drop_index('plugin_docman_metadata_value', 'idx_field_id');
drop_index('plugin_docman_metadata_value', 'idx_artifact_id');

add_index('plugin_docman_metadata_value', 'idx_field_item_id', 'field_id, item_id');
add_index('plugin_docman_item', 'idx_group_id', 'group_id');
add_index('plugin_docman_item', 'parent_id', 'parent_id');
add_index('plugin_docman_item', 'rank', 'rank');
add_index('plugin_docman_version', 'idx_item_id', 'item_id');
add_index('plugin_docman_metadata_love', 'rank', 'rank');
add_index('plugin_docman_metadata_love', 'name', 'name (10)');
add_index('plugin_docman_metadata', 'idx_name', 'name (10)');

EOF

/usr/share/codex/src/utils/php-launcher.sh /usr/share/codex/plugins/docman/db/upgrade_v2.6.php

# Upgrade templates projects with a default docman if none are existing.
$PERL <<'EOF'
use DBI;

require $ENV{INSTALL_DIR}."/src/utils/include.pl";  # Include all the predefined functions
&db_connect;

sub create_item {
    my ($group_id) = @_;

    $qry = "INSERT INTO plugin_docman_item (parent_id, group_id, title, description, create_date, update_date, delete_date, user_id, status, obsolescence_date, rank, item_type, link_url, wiki_page, file_is_embedded) VALUES (0, $group_id, 'roottitle_lbl_key', '', UNIX_TIMESTAMP(NOW()), UNIX_TIMESTAMP(NOW()), NULL, 101, 0, 0, 0, 1, NULL, NULL, NULL)";

    $create_item = $dbh->prepare($qry);
    $create_item->execute();
}

sub create_permission {
    my ($group_id, $perm, $ugroup_id) = @_;

    $qry_insert_perms = "INSERT INTO permissions(permission_type, ugroup_id, object_id)".
	" SELECT '$perm', $ugroup_id, item_id".
	" FROM plugin_docman_item".
	" WHERE group_id = $group_id";
    
    $insert_perms = $dbh->prepare($qry_insert_perms);
    $insert_perms->execute();
}

sub create_all_perms {
    my ($group_id) = @_;

    create_permission($group_id, "PLUGIN_DOCMAN_READ", 2);
    create_permission($group_id, "PLUGIN_DOCMAN_WRITE", 3);
    create_permission($group_id, "PLUGIN_DOCMAN_MANAGE", 4);

    $qry_admin_perms = "INSERT INTO permissions(permission_type, ugroup_id, object_id)".
	"VALUES ('PLUGIN_DOCMAN_ADMIN', 4, $group_id)";
    $admin_perms = $dbh->prepare($qry_admin_perms);
    $admin_perms->execute();
}


sub create_settings {
    my ($group_id) = @_;

    $qry_settings = "INSERT INTO  plugin_docman_project_settings (group_id, view, use_obsolescence_date, use_status)".
	"VALUES ($group_id, 'Tree', 0, 0)";
    $settings = $dbh->prepare($qry_settings);
    $settings->execute();
}

sub create_docman {
    my ($group_id) = @_;

    create_item($group_id);
    create_all_perms($group_id);
    create_settings($group_id)
}

sub docman_exist {
    my ($group_id) = @_;

    $qry = "SELECT count(*) as nb FROM plugin_docman_item WHERE group_id = $group_id AND delete_date IS NULL";
    $c2 = $dbh->prepare($qry);
    $c2->execute();
    my ($nb) = $c2->fetchrow();
    if($nb gt 0) {
	return 1;
    }
    else {
	return 0;
    }
}

sub create_docman_for_template_projects {
    $qry = "SELECT group_id FROM groups WHERE type='2' and status IN ('A','s')";
    $c = $dbh->prepare($qry);
    $c->execute();
    while (my ($group_id) = $c->fetchrow()) {
	if(docman_exist($group_id)) {
	    #print "Docman exist for group $group_id\n";
	}
	else {
	    print "Create docman for group $group_id\n";
	    create_docman($group_id);
	}
    }
}

create_docman_for_template_projects;
EOF
fi

###############################################################################
# Personalizeable layout

echo "- personalizeable layout update"
$CAT <<EOF | $MYSQL $pass_opt codex

-- --------------------------------------------------------

-- 
-- Structure de la table 'layouts'
-- 

DROP TABLE IF EXISTS layouts;
CREATE TABLE IF NOT EXISTS layouts (
  id int(11) unsigned NOT NULL auto_increment,
  name varchar(255) NOT NULL default '',
  description text NOT NULL,
  scope char(1) NOT NULL default 'S',
  PRIMARY KEY  (id)
);

-- --------------------------------------------------------

-- 
-- Structure de la table 'layouts_rows'
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
-- Structure de la table 'layouts_rows_columns'
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
-- Structure de la table 'owner_layouts'
-- 

DROP TABLE IF EXISTS owner_layouts;
CREATE TABLE IF NOT EXISTS owner_layouts (
  owner_id int(11) unsigned NOT NULL default '0',
  owner_type varchar(1) NOT NULL default 'u',
  layout_id int(11) unsigned NOT NULL default '0',
  is_default tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (owner_id, owner_type, layout_id)
);

-- --------------------------------------------------------

-- 
-- Structure de la table 'layouts_contents'
-- 

DROP TABLE IF EXISTS layouts_contents;
CREATE TABLE IF NOT EXISTS layouts_contents (
  owner_id int(11) unsigned NOT NULL default '0',
  owner_type varchar(1) NOT NULL default 'u',
  layout_id int(11) unsigned NOT NULL default '0',
  column_id int(11) unsigned NOT NULL default '0',
  name varchar(255) NOT NULL default '',
  rank int(11) NOT NULL default '0',
  is_minimized tinyint(1) NOT NULL default '0',
  is_removed tinyint(1) NOT NULL default '0',
  display_preferences tinyint(1) NOT NULL default '0',
  content_id int(11) unsigned NOT NULL default '0',
  KEY user_id (owner_id,owner_type,layout_id,name,content_id)
);


DROP TABLE IF EXISTS widget_rss;
CREATE TABLE IF NOT EXISTS widget_rss (
  id int(11) unsigned NOT NULL auto_increment PRIMARY KEY,
  owner_id int(11) unsigned NOT NULL default '0',
  owner_type varchar(1) NOT NULL default 'u',
  title varchar(255) NOT NULL,
  url TEXT NOT NULL,
  KEY (owner_id, owner_type)
);




INSERT INTO layouts (id, name, description, scope) VALUES 
(1, '2 columns', 'Simple layout made of 2 columns', 'S');

INSERT INTO layouts_rows (id, layout_id, rank) VALUES (1, 1, 0);
INSERT INTO layouts_rows_columns (id, layout_row_id, width) VALUES (1, 1, 50), (2, 1, 50);

########## Users

INSERT INTO owner_layouts (owner_id, owner_type, layout_id, is_default) 
SELECT user_id, 'u', 1, 1 
FROM user;

INSERT INTO layouts_contents (owner_id, owner_type, layout_id, column_id, name, rank) 
SELECT user_id, 'u', 1, 1, 'myprojects', 0
FROM user;

INSERT INTO layouts_contents (owner_id, owner_type, layout_id, column_id, name, rank) 
SELECT user_id, 'u', 1, 1, 'mybookmarks', 1
FROM user;

# Add mydocman only if docman is installed
INSERT INTO layouts_contents (owner_id, owner_type, layout_id, column_id, name, rank) 
SELECT user_id, 'u', 1, 1, 'mydocman', 2
FROM user, plugin
WHERE plugin.name = 'docman';

INSERT INTO layouts_contents (owner_id, owner_type, layout_id, column_id, name, rank) 
SELECT user_id, 'u', 1, 1, 'mymonitoredforums', 3
FROM user;

INSERT INTO layouts_contents (owner_id, owner_type, layout_id, column_id, name, rank) 
SELECT user_id, 'u', 1, 1, 'mysurveys', 4
FROM user;

# only if user have project with legacy bugs
INSERT INTO layouts_contents (owner_id, owner_type, layout_id, column_id, name, rank) 
SELECT DISTINCT ug.user_id, 'u', 1, 2, 'mybugs', 5
FROM groups g, user_group ug, service s
WHERE g.group_id = ug.group_id
  AND g.group_id = s.group_id
  AND g.status = 'A'
  AND s.short_name = 'bugs'
  AND s.is_used = 1
  AND s.is_active = 1;

# only if user have project with legacy tasks
INSERT INTO layouts_contents (owner_id, owner_type, layout_id, column_id, name, rank) 
SELECT DISTINCT ug.user_id, 'u', 1, 2, 'mytasks', 5
FROM groups g, user_group ug, service s
WHERE g.group_id = ug.group_id
  AND g.group_id = s.group_id
  AND g.status = 'A'
  AND s.short_name = 'task'
  AND s.is_used = 1
  AND s.is_active = 1;

# only if user have project with legacy support requests
INSERT INTO layouts_contents (owner_id, owner_type, layout_id, column_id, name, rank) 
SELECT DISTINCT ug.user_id, 'u', 1, 2, 'mysrs', 5
FROM groups g, user_group ug, service s
WHERE g.group_id = ug.group_id
  AND g.group_id = s.group_id
  AND g.status = 'A'
  AND s.short_name = 'support'
  AND s.is_used = 1
  AND s.is_active = 1;


# Add myadmin only to current admins
INSERT INTO layouts_contents (owner_id, owner_type, layout_id, column_id, name, rank) 
SELECT DISTINCT user_id, 'u', 1, 2, 'myadmin', -2
FROM user_group
WHERE group_id = 1
  AND admin_flags = 'A';

# Add myserverupdate only to current admins
# and only if serverupdate is installed
INSERT INTO layouts_contents (owner_id, owner_type, layout_id, column_id, name, rank) 
SELECT DISTINCT user_id, 'u', 1, 2, 'myserverupdate', -1
FROM user_group, plugin
WHERE group_id = 1
  AND admin_flags = 'A'
  AND plugin.name = 'serverupdate';

INSERT INTO layouts_contents (owner_id, owner_type, layout_id, column_id, name, rank) 
SELECT user_id, 'u', 1, 2, 'myartifacts', 0
FROM user;

INSERT INTO layouts_contents (owner_id, owner_type, layout_id, column_id, name, rank) 
SELECT user_id, 'u', 1, 2, 'mymonitoredfp', 1
FROM user;



########## Projects

INSERT INTO owner_layouts (owner_id, owner_type, layout_id, is_default) 
SELECT group_id, 'g', 1, 1 
FROM groups;

# only if FRS is used
INSERT INTO layouts_contents (owner_id, owner_type, layout_id, column_id, name, rank) 
SELECT group_id, 'g', 1, 1, 'projectlatestfilereleases', 0
FROM service
WHERE short_name = 'file' AND is_active = 1 AND is_used = 1;

INSERT INTO layouts_contents (owner_id, owner_type, layout_id, column_id, name, rank) 
SELECT group_id, 'g', 1, 1, 'projectpublicareas', 1
FROM groups;



# only if News is used
INSERT INTO layouts_contents (owner_id, owner_type, layout_id, column_id, name, rank) 
SELECT group_id, 'g', 1, 2, 'projectlatestnews', 0
FROM service
WHERE short_name = 'news' AND is_active = 1 AND is_used = 1;

# only if SVN is used
INSERT INTO layouts_contents (owner_id, owner_type, layout_id, column_id, name, rank) 
SELECT group_id, 'g', 1, 2, 'projectlatestsvncommits', 1
FROM service
WHERE short_name = 'svn' AND is_active = 1 AND is_used = 1;

# only if CVS is used
INSERT INTO layouts_contents (owner_id, owner_type, layout_id, column_id, name, rank) 
SELECT group_id, 'g', 1, 2, 'projectlatestcvscommits', 2
FROM service
WHERE short_name = 'cvs' AND is_active = 1 AND is_used = 1;


EOF


###############################################################################
# SR #886
echo "- artifact follow-up modification"
$CAT <<EOF | $MYSQL $pass_opt codex
INSERT INTO artifact_notification_event_default (event_id,event_label,rank,short_description_msg,description_msg) VALUES (10,"COMMENT_CHANGE",100,"event_COMMENT_CHANGE_short_desc","event_COMMENT_CHANGE_desc");
INSERT INTO artifact_notification_event (event_id,group_artifact_id,event_label,rank,short_description_msg,description_msg) SELECT 10,group_artifact_id,"COMMENT_CHANGE",100,"event_COMMENT_CHANGE_short_desc","event_COMMENT_CHANGE_desc" FROM artifact_group_list WHERE group_artifact_id > 100;
# 'AND old_value != ""' -> to avoid erasing data if statement was already executed
UPDATE artifact_history SET new_value = old_value , old_value = "" WHERE field_name = "comment" AND old_value != "";

EOF

###############################################################################
# Install and enable two plugins (eclipse and codexjri)
echo "- add eclipse and codexjri plugins"
$CAT <<EOF | $MYSQL $pass_opt codex
INSERT INTO plugin (name, available) VALUES ('eclipse', '1');
INSERT INTO plugin (name, available) VALUES ('codexjri', '1');

EOF

###############################################################################
# Run 'analyse' on all MySQL DB
echo "Analyzing and optimizing MySQL databases (this might take a few minutes)"
mysqlcheck -Aaos $pass_opt

###############################################################################
echo "Updating local.inc"

# sys_proxy
$GREP -q ^\$sys_proxy  $ETC_DIR/conf/local.inc
if [ $? -ne 0 ]; then
  # Remove end PHP marker
  substitute '/etc/codex/conf/local.inc' '\?\>' ''

  $CAT <<EOF >> /etc/codex/conf/local.inc
//Proxy used to access to Internet. "host:port"
\$sys_proxy = "";

?>
EOF
fi

# htmlpurifier
$GREP -q ^\$htmlpurifier_dir  $ETC_DIR/conf/local.inc
if [ $? -ne 0 ]; then
  # Remove end PHP marker
  substitute '/etc/codex/conf/local.inc' '\?\>' ''

  $CAT <<EOF >> /etc/codex/conf/local.inc
// 3rd Party libraries
\$htmlpurifier_dir = "/usr/share/htmlpurifier";

?>
EOF
fi

# sys_password_lifetime (normally done in 3.2 branch)
$GREP -q ^\$sys_password_lifetime  $ETC_DIR/conf/local.inc
if [ $? -ne 0 ]; then
  # Remove end PHP marker
  substitute '/etc/codex/conf/local.inc' '\?\>' ''

  $CAT <<EOF >> /etc/codex/conf/local.inc
// Default password duration. User will be asked to change its password
// after 'sys_password_lifetime' days.
// 0 = no duration
\$sys_password_lifetime = 0;

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

