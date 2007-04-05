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
TODO_FILE=/root/todo_codex_upgrade_3.2.txt
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
$MV /etc/httpd/conf.d/subversion.conf /etc/httpd/conf.d/subversion.conf.3.0.1.codex
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

$CP -a /etc/httpd/conf.d/subversion.conf.3.0.1.codex /etc/httpd/conf.d/subversion.conf

todo "Subversion has been upgraded to version 1.4. There is some benefit (repository size reduction) in dumping/reloading your repositories, but it is absolutely not needed. See http://subversion.tigris.org/svn_1.4_releasenotes.html for more details."

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
  id INT( 11 ) UNSIGNED NOT NULL PRIMARY KEY ,
  name VARCHAR( 255 ) NOT NULL ,
  description TEXT NOT NULL ,
  http TEXT NOT NULL,
  https TEXT NOT NULL,
  is_master TINYINT(1) NOT NULL default 0
);
ALTER TABLE service ADD location ENUM( 'master', 'same', 'satellite' ) NOT NULL DEFAULT 'master';
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
  created_at DATETIME NOT NULL,
  PRIMARY KEY ( user_id, token )
);

###############################################################################
# Add a status for Files in FRS
ALTER TABLE frs_file ADD status CHAR( 1 ) NOT NULL DEFAULT 'A';

###############################################################################
# Processor types in FRS (possible to add custom processor types per project)
ALTER TABLE frs_processor ADD rank INT( 11 ) NOT NULL DEFAULT '0';
ALTER TABLE frs_processor ADD group_id INT( 11 ) NOT NULL DEFAULT '0';

UPDATE frs_processor SET rank = '10', group_id = '100' WHERE processor_id=1000;
UPDATE frs_processor SET rank = '20', group_id = '100' WHERE processor_id=2000;
UPDATE frs_processor SET rank = '30', group_id = '100' WHERE processor_id=3000;
UPDATE frs_processor SET rank = '40', group_id = '100' WHERE processor_id=4000;
UPDATE frs_processor SET rank = '50', group_id = '100' WHERE processor_id=5000;
UPDATE frs_processor SET rank = '60', group_id = '100' WHERE processor_id=6000;
UPDATE frs_processor SET rank = '70', group_id = '100' WHERE processor_id=7000;
UPDATE frs_processor SET rank = '80', group_id = '100' WHERE processor_id=8000;
UPDATE frs_processor SET rank = '90', group_id = '100' WHERE processor_id=9999;

###############################################################################
# Plugin / Project
ALTER TABLE plugin ADD COLUMN prj_restricted TINYINT(4) NOT NULL DEFAULT 0 AFTER available;

###############################################################################
# CVS administration (cvs watch mode)
ALTER TABLE groups ADD COLUMN cvs_watch_mode INT(11) NOT NULL DEFAULT 0 AFTER cvs_tracker;

###############################################################################
# CVS tracker and svn tarcker on template project (group_id = 100)
UPDATE groups SET cvs_tracker = '1', svn_tracker = '1' WHERE group_id='100';

###############################################################################
#Add forums on template project (group_id = 100)
INSERT INTO forum_group_list (group_id,forum_name,is_public,description) VALUES ('100','Open Discussion','1' ,'General Discussion');
INSERT INTO forum_group_list (group_id,forum_name,is_public,description) VALUES ('100','Help','1' ,'Get Help');
INSERT INTO forum_group_list (group_id,forum_name,is_public,description) VALUES ('100','Developers','0' ,'Project Developer Discussion');

###############################################################################
# Tracker global notification
CREATE TABLE artifact_global_notification (
  id                INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
  tracker_id        INT(11) NOT NULL ,
  addresses         TEXT NOT NULL ,
  all_updates       TINYINT(1) NOT NULL ,
  check_permissions TINYINT(1) NOT NULL ,
  INDEX (tracker_id)
);

INSERT INTO artifact_global_notification(tracker_id, addresses, all_updates, check_permissions) 
SELECT group_artifact_id, email_address, email_all_updates, 1 
FROM artifact_group_list;

ALTER TABLE artifact_group_list DROP email_address;
ALTER TABLE artifact_group_list DROP email_all_updates;

###############################################################################
# Plugin Docman
ALTER TABLE plugin_docman_metadata ADD COLUMN mul_val_ok TINYINT(4) NOT NULL DEFAULT '0' AFTER empty_ok;
DROP TABLE IF EXISTS plugin_docman_approval;
CREATE TABLE plugin_docman_approval (
  item_id INT(11) UNSIGNED NOT NULL,
  table_owner INT(11) UNSIGNED NOT NULL,
  date INT(11) UNSIGNED NULL,
  description TEXT NULL,
  status TINYINT(4) DEFAULT 0 NOT NULL,
  notification TINYINT(4) DEFAULT 0 NOT NULL,
  INDEX item_id (item_id),
  UNIQUE(item_id)
);
DROP TABLE IF EXISTS plugin_docman_approval_user;
CREATE TABLE plugin_docman_approval_user (
  item_id INT(11) UNSIGNED NOT NULL,
  reviewer_id INT(11) UNSIGNED NOT NULL,
  rank INT(11) DEFAULT 0 NOT NULL,
  date INT(11) UNSIGNED NULL,
  state TINYINT(4) DEFAULT 0 NOT NULL,
  comment TEXT NULL,
  version INT(11) UNSIGNED NULL,
  PRIMARY KEY(item_id, reviewer_id),
  INDEX rank (rank)
);

###############################################################################
# Drop tables 'themes' and 'theme_prefs' that are no longer used
DROP TABLE IF EXISTS themes;
DROP TABLE IF EXISTS theme_prefs;

###############################################################################
# Service in iframe
ALTER TABLE service ADD is_in_iframe TINYINT(1) NOT NULL DEFAULT '0';

EOF


###############################################################################
echo "Adding snippet reference"
$CAT <<EOF | $MYSQL $pass_opt codex
INSERT INTO reference SET \
    id='70',        \
    keyword='snippet', \
    description='reference_snippet_desc_key', \
    link='/snippet/detail.php?type=snippet&id=$1', \
    scope='S';

INSERT INTO reference_group SET reference_id='70', group_id='100', is_active='1';

EOF

#
# create snippet reference for existing projects
#

$PERL <<'EOF'

use DBI;
require "/usr/share/codex/src/utils/include.pl";  # Include all the predefined functions
&db_connect;

sub insert_references {
    my ($query, $query3, $c, $c3);
    $query = "SELECT group_id FROM groups WHERE group_id!=100";
    $c = $dbh->prepare($query);
    $c->execute();
    while (my ($group_id) = $c->fetchrow()) {
          # Add reference in project
            $query3 = "INSERT INTO reference_group (reference_id,group_id,is_active) VALUES ('70','$group_id','1')";
            $c3 = $dbh->prepare($query3);
            $c3->execute();
    }
}



&insert_references();
print "** All snippet references created\n";
1;
EOF


###############################################################################
echo "Optimizing database structure."
$CAT <<EOF | $MYSQL $pass_opt codex

# SR #636
ALTER TABLE artifact_history CHANGE field_name field_name VARCHAR(255) NOT NULL default '';

# SR #635 - Permission table
ALTER TABLE permissions CHANGE permission_type permission_type VARCHAR(255) NOT NULL;
ALTER TABLE permissions CHANGE object_id object_id VARCHAR(255) NOT NULL;

# SR #625
ALTER TABLE user_group ADD news_flags INT(11) NOT NULL DEFAULT '0';


EOF
################################################################################
# Add/remove indexes if needed
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
    print $query ."\n";
    $c = $dbh->prepare($query);
    $c->execute();
}
sub drop_index {
    my ($query, $c);
    my ($tablename) = $_[0];
    my ($indexname) = $_[1];

    if (index_exists($tablename, $indexname) eq 1) {
        $query = "DROP INDEX ". $indexname ." ON ". $tablename;
        print $query ."\n";
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

# SR #676
drop_index('wiki_page', 'group_id');
add_index('wiki_page', 'idx_page_group', 'group_id, pagename(10)');

# SR #675
add_index('artifact', 'idx_fk_submitted_by', 'submitted_by');

# SR #634
add_index('svn_commits', 'revision', 'revision');

# SR #633
add_index('artifact_field_value', 'valueInt', 'valueInt');

# SR #683
add_index('cvs_checkins', 'commitid', 'commitid');

# SR #636
add_index('artifact_field', 'idx_grp_name', 'group_artifact_id, field_name (20)');
add_index('artifact_history', 'field_name', 'field_name (10)');

# SR #637 - fixes issue with large tracker reports
add_index('artifact_field_value', 'idx_art_field_id', 'artifact_id, field_id');

# SR #635 - Permission table
add_index('permissions', 'object_id', 'object_id (10)');

# Plugin / Project
add_index('project_plugin', 'project_plugin', 'project_id, plugin_id', 'UNIQUE');
add_index('project_plugin', 'project_id_idx', 'project_id');
add_index('project_plugin', 'plugin_id_idx', 'plugin_id');


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

##############################################################################
# Upgrade phpwiki pages
todo "If you want to upgrade the phpwiki pages of all your projects, please follow the link : yourmachine/wiki/admin/index.php?group_id=1&view=upgrade "
todo " then follow the link of 'next project' "
todo "Before doing this, make sure that the wiki of project 1 (codex) is instantiated."

###############################################################################
echo "Updating ssl.conf"
#
# Subversion roots are now in conf.d and available to both http and https vhosts.

substitute '/etc/httpd/conf/ssl.conf' '^Include .*codex_svnhosts_ssl.conf' '#Include $1codex_svnhosts_ssl.conf'

###############################################################################
echo "Updating codex_aliases.conf"

# backup
$CP /etc/httpd/conf.d/codex_aliases.conf /etc/httpd/conf.d/codex_aliases.conf.CX3
# copy newer
$CP $INSTALL_DIR/src/etc/codex_aliases.conf.dist /etc/httpd/conf.d/codex_aliases.conf

###############################################################################
echo "Updating local.inc"

# Remove end PHP marker
substitute '/etc/codex/conf/local.inc' '\?\>' ''

$CAT <<EOF >> /etc/codex/conf/local.inc

//This is used for cookie authentication. If you have distributed servers, 
//please use a generic domain to allow single sign on.
//Examples:
//- you have 1 server (s1.codex.com) 
//   => cookie domain is "s1.codex.com"
//- you have 2 servers (s1.codex.com & s2.codex.com) 
//   => cookie domain should be "codex.com" for SSO
\$sys_cookie_domain = "$sys_default_domain";

//This is used for cookie authentication. If you have distributed servers, 
//please use same cookie prefix for a "cluster"
\$sys_cookie_prefix = "CODEX";

// The id of the server.
// If the server belong to a distributed architecture, make sure that all servers have a different server_id.
// Otherwise, use '0'.
\$sys_server_id = 0;

// Disable sub-domains (like cvs.proj.codex.xerox.com)
// Should be disabled if no DNS delegation
\$sys_disable_subdomains = 0;
?>
EOF


###############################################################################
echo "Copy updated commit-email.pl"

cd $INSTALL_DIR/src/utils/svn
$CP commit-email.pl /usr/lib/codex/bin
cd /usr/lib/codex/bin
$CHOWN codexadm.codexadm commit-email.pl
$CHMOD 755 commit-email.pl


###############################################################################
echo "Move /etc/codex/site-content/LANG/register to site-content/project"

if [ -e /etc/codex/site-content/en_US/register/* ]; then
   $MKDIR -p /etc/codex/site-content/en_US/project
   $MV /etc/codex/site-content/en_US/register/* /etc/codex/site-content/en_US/project
fi

if [ -e /etc/codex/site-content/fr_FR/register/* ]; then
   $MKDIR -p /etc/codex/site-content/fr_FR/project
   $MV /etc/codex/site-content/fr_FR/register/* /etc/codex/site-content/fr_FR/project
fi


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

todo "The project registering process has been updated. If you customized the messages for project creation, please verify that the new process messages are still correct"
todo "The configuration file /etc/codex/conf/local.inc has been updated. If you use several configuration files or non-standard files, please make sure to update them correctly."
todo "If you have custom themes, please :"
todo " - copy the rules from CodeX/css/normal.css in your stylesheets :"
todo "   - .textfield_small and .textfield_medium "
todo "   - .iframe_service and .iframe_showonly "
todo "   - related to the File Release System "
todo " - copy and modify CodeX/images/ic/plain-arrow-down.png"
todo "Last, log in as 'admin' on web server, and update the server to the latest available version (with Server Update plugin)"
todo "-----------------------------------------"
todo "This TODO list is available in $TODO_FILE"


# End of it
echo "=============================================="
echo "Migration completed succesfully!"
$CAT $TODO_FILE

exit 1;

