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
#  
#
#      Originally written by Laurent Julliard 2004, CodeX Team, Xerox
#
#  This file is part of the CodeX software and must be placed at the same
#  level as the CodeX, RPMS_CodeX and nonRPMS_CodeX directory when
#  delivered on a CD or by other means
#
#  This script migrates a site running CodeX 2.2 to CodeX 2.4
#


progname=$0
if [ -z "$scriptdir" ]; then 
    scriptdir=`dirname $progname`
fi
cd ${scriptdir};TOP_DIR=`pwd`;cd -
RPMS_DIR=${TOP_DIR}/RPMS_CodeX
nonRPMS_DIR=${TOP_DIR}/nonRPMS_CodeX
CodeX_DIR=${TOP_DIR}/CodeX
TODO_FILE=/tmp/todo_codex_migration.txt
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
# CodeX 2.2 to 2.4 migration
##############################################


##############################################
# Check the machine is running CodeX 2.2
#
OLD_CX_RELEASE='2.2'
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

The document manager has improved in CodeX 2.4.
Before upgrading, please note that:
- all existing documents stored with the 'deleted' status will *really* be deleted from the database
- all documents stored with the 'pending' status will become active
- other statuses will properly be converted to the new permission schema.

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
# (note: gcc is required to recompile mailman)
#

# Removed: see install script for required RPMs. No new RPM needed for upgrade

##############################################
# Ask for domain name and other installation parameters
#
read -p "CodeX Domain name: " sys_default_domain
read -p "Codex Server IP address: " sys_ip_address

##############################################

##############################################
# Update local.inc
#
make_backup /etc/codex/conf/local.inc codex22

# Remove $sys_activate_tracker setting (now useless)
$PERL -i'.orig3' -p -e's:(.sys_activate_tracker)://\1 // parameter now deprecated:' /etc/codex/conf/local.inc
# Update LDAP settings
perl -i'.orig4' -p -e's/.*LDAP.*\n//g; s/.*substituted.*//g; s:^(.*sys_ldap_server.*)\n$:// Authentication scheme\:\n// Should be either \"ldap\" or \"codex\"\n// WARNING\: this is still experimental\n\$sys_auth_type = \"codex\";\n\n//\n// LDAP server(s) to query for more information on CodeX users and \n// for authentication.\n// You may use a comma-separated list if there are several servers available\n// (leave blank to disable LDAP lookup). \n// To specify secure LDAP servers, use \"ldaps\://servername\"\n\1:; s:^(.*sys_ldap_dn.*)$:\n\n\n// To enable LDAP information on CodeX users, also define the DN\n// (distinguised name) to use in LDAP queries.\n// The ldap filter is the filter to use to query the LDAP directory\n// (\%name\% are substituted with the value from the user table)\n\n\1:; s:^(.*sys_ldap_filter.*)$:\1\n\n// For LDAP systems that do not accept anonymous binding, define here\n// a valid DN and password\:\n//\$sys_ldap_bind_dn=\"ldapsearch\";\n//\$sys_ldap_bind_passwd=\"xxxxxxxxx\";\n\n// LDAP authentication\:\n// sys_ldap_auth_dn is used for binding to the LDAP server with the\n// ldap name and password.\n// sys_ldap_auth_filter is needed to retrieve user information when\n// creating the account, and during two-step authentication\n//\n// You should use \"\%ldap_name\%\" wherever the ldap login would appear.\n//\n// NOTE\: if you need a two-step authentication (search, then bind),\n// don t specify any \"\%ldap_name\%\" in \$sys_ldap_auth_dn\n// (in this case, you should probably have\:\n//  \$sys_ldap_auth_dn == \$sys_ldap_dn )\n//\n// 1- Direct authentication\:\n//\$sys_ldap_auth_dn=\"dc=xerox, dc=com, cn=\%ldap_name\%\";\n// 2- Two-step authentication (search, then bind)\:\n\$sys_ldap_auth_dn=\"o=XEROX, c=US\";\n\$sys_ldap_auth_filter=\"uid=\%ldap_name\%\";\n:' /etc/codex/conf/local.inc
todo "If you plan to use LDAP, customize the corresponding parameters in /etc/codex/conf/local.inc"


##############################################
# Now install CodeX specific RPMS (and remove RedHat RPMs)
#


# -> subversion
# backup config file for apache
$MV /etc/httpd/conf.d/subversion.conf /etc/httpd/conf.d/subversion.conf.codex
echo "Removing existing subversion .."
$RPM -e --nodeps `rpm -qa 'subversion*' 'swig*' 'neon*' 'rapidsvn*'` 2>/dev/null
echo "Installing Subversion RPMs for CodeX...."
cd ${RPMS_DIR}/subversion
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
$RPM -Uvh --force ${newest_rpm}/neon-0*.i386.rpm
$RPM -Uvh --force ${newest_rpm}/swig-1*.i386.rpm
$RPM -Uvh --force ${newest_rpm}/subversion-1.*.i386.rpm
$RPM -Uvh --force ${newest_rpm}/subversion-server*.i386.rpm
$RPM -Uvh --force ${newest_rpm}/subversion-python*.i386.rpm
$RPM -Uvh --force ${newest_rpm}/subversion-tools*.i386.rpm
$RPM -Uvh --force ${newest_rpm}/subversion-perl*.i386.rpm

$CP -a /etc/httpd/conf.d/subversion.conf.codex /etc/httpd/conf.d/subversion.conf


##############################################
# Update the CodeX software

echo "Installing the CodeX software..."
cd /home
$MV httpd httpd_22
$MKDIR httpd;
cd httpd
$TAR xfz ${CodeX_DIR}/codex*.tgz
$CHOWN -R sourceforge.sourceforge $INSTALL_DIR

# copy some configuration files 
make_backup /etc/httpd/conf/httpd.conf codex22
$CP $INSTALL_DIR/SF/etc/httpd.conf.dist /etc/httpd/conf/httpd.conf
$CP $INSTALL_DIR/SF/etc/php.conf.dist /etc/httpd/conf.d/php.conf

# replace string patterns in httpd.conf
substitute '/etc/httpd/conf/httpd.conf' '%sys_default_domain%' "$sys_default_domain"
substitute '/etc/httpd/conf/httpd.conf' '%sys_ip_address%' "$sys_ip_address"

todo "Edit the new /etc/httpd/conf/httpd.conf file and update it if needed"
todo "Edit the new /etc/httpd/conf.d/php.conf file and update it if needed"

# backup_job has a new licence
$CP $INSTALL_DIR/SF/utils/backup_job /home/tools
$CHOWN root.root /home/tools/backup_job
$CHMOD 740 /home/tools/backup_job

# New directory
build_dir /etc/codex/themes/messages sourceforge sourceforge 755

# Re-copy phpMyAdmin and viewcvs installations
$CP -af /home/httpd_22/phpMyAdmin* /home/httpd
$CP -af /home/httpd_22/cgi-bin/viewcvs.cgi /home/httpd/cgi-bin

#############################################
# Copy new icons in all custom themes
$CP  $INSTALL_DIR/SF/www/images/codex.theme/ic/wiki.png /etc/codex/themes/images/*/ic/ 2> /dev/null


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
    read -p "Existing CodeX DB is password protected. What is the Mysql root password?: " old_passwd
    mysqlshow --password=$old_passwd 2>&1 | grep password
done
[ "X$old_passwd" != "X" ] && pass_opt="--password=$old_passwd"

$CAT <<EOF | $MYSQL $pass_opt sourceforge


---
--- Table structure for user interface supported languages
---
CREATE TABLE supported_languages (
   language_id int(11) NOT NULL auto_increment,
   name text,
   filename text,
   language_code varchar(15),
   language_charset varchar(32),
   active int(11) NOT NULL default '1',
   PRIMARY KEY  (language_id),
   KEY idx_supported_languages_language_code (language_code)
);

ALTER TABLE user ADD language_id int(11) NOT NULL DEFAULT 1 ;


--- ==============================
--- supported_languages table
--- ==============================
--- Create the list of supported languages for that site

INSERT INTO supported_languages VALUES \
(1,'English','English_US.tab','en_US','ISO-8859-1',1);
INSERT INTO supported_languages VALUES \
(2,'Fran�ais','French_FR.tab','fr_FR','ISO-8859-1',1);


---
--- LDAP support
---
ALTER TABLE user ADD ldap_name TEXT AFTER unix_box;



---
--- DocManager update
---

--- DocManager roles
DELETE FROM ugroup WHERE ugroup_id='10';
INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (12, "document_tech", "Document Manager Technicians", 100);
INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (13, "document_admin", "Document Manager Administrators", 100);

--- Update existing roles
UPDATE user_group SET doc_flags = '2' WHERE doc_flags = '1';


--- default possible permissions
DELETE FROM permissions_values WHERE permission_type='DOCUMENT_READ';

INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('DOCGROUP_READ',100);
INSERT INTO permissions_values (permission_type,ugroup_id,is_default) VALUES ('DOCGROUP_READ',1,1);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('DOCGROUP_READ',2);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('DOCGROUP_READ',3);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('DOCGROUP_READ',4);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('DOCGROUP_READ',12);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('DOCGROUP_READ',13);
-- No default value for DOCUMENT_READ -> use parent permissions
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('DOCUMENT_READ',100);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('DOCUMENT_READ',1);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('DOCUMENT_READ',2);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('DOCUMENT_READ',3);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('DOCUMENT_READ',4);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('DOCUMENT_READ',12);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('DOCUMENT_READ',13);


--- Delete doc_states table (now useless since we have ugroups permissions)
DROP TABLE doc_states;




---
--- add a new tracker template to replace legacy patch tracker
---

UPDATE service SET is_active=0, is_used=0 WHERE ( group_id='100' and short_name='patch' );

INSERT INTO artifact_group_list (group_artifact_id, group_id, name, description, item_name, is_public, allow_anon, email_all_updates, email_address, submit_instructions, browse_instructions, instantiate_for_new_projects) VALUES (5, 100, 'Patches', 'Patch Tracker', 'patch', 1, 0, 0, '', NULL, NULL, 1);

INSERT INTO artifact_field VALUES (1,5,'submitted_by',5,'SB','','Submitted by','User who originally submitted the artifact','',0,1,0,1,'artifact_submitters','');
INSERT INTO artifact_field VALUES (2,5,'open_date',4,'DF','','Submitted on','Date and time for the initial artifact submission','',0,0,0,1,'','');
INSERT INTO artifact_field VALUES (3,5,'summary',1,'TF','60/150','Summary','One line description of the artifact','',0,0,1,0,NULL,'');
INSERT INTO artifact_field VALUES (4,5,'artifact_id',2,'TF','6/10','Artifact ID','Unique artifact identifier','',0,0,0,1,NULL,'');
INSERT INTO artifact_field VALUES (5,5,'plain_text',1,'TA','60/7','Paste the patch here (text only),<br> OR attach it as a file <br> in the \'Attachments\' section','Plain-text version of the patch','',0,1,0,0,NULL,'');
INSERT INTO artifact_field VALUES (6,5,'assigned_to',5,'SB','','Assigned to','Who is in charge of solving the artifact','',0,1,1,0,'artifact_technicians','100');
INSERT INTO artifact_field VALUES (7,5,'category_id',2,'SB','','Category','Patch categories (e.g. mail module,gant chart module,interface, etc)','',0,1,1,0,NULL,'100');
INSERT INTO artifact_field VALUES (8,5,'details',1,'TA','60/7','Description','Description of functionality and application of the patch','',0,1,1,0,NULL,'');
INSERT INTO artifact_field VALUES (9,5,'status_id',2,'SB','','Status','Artifact Status','',0,0,1,0,NULL,'1');
INSERT INTO artifact_field VALUES (10,5,'severity',2,'SB','','Severity','Impact of the artifact on the system (Critical, Major,...)','',0,0,1,0,NULL,'5');
INSERT INTO artifact_field VALUES (11,5,'release_id',2,'SB','','Release','The release (global version number) impacted by the artifact','P',0,1,1,0,NULL,'100');
INSERT INTO artifact_field VALUES (12,5,'response_id',2,'SB','','Response','The project team\'s response to the artifact (typically Accepted, Declined, etc.)','P',0,1,1,0,NULL,'100');

INSERT INTO artifact_field_usage VALUES (1,5,1,0,0,0);
INSERT INTO artifact_field_usage VALUES (2,5,1,0,0,0);
INSERT INTO artifact_field_usage VALUES (3,5,1,1,1,30);
INSERT INTO artifact_field_usage VALUES (4,5,1,0,0,0);
INSERT INTO artifact_field_usage VALUES (5,5,1,1,1,50);
INSERT INTO artifact_field_usage VALUES (6,5,1,0,0,0);
INSERT INTO artifact_field_usage VALUES (7,5,1,1,1,10);
INSERT INTO artifact_field_usage VALUES (8,5,1,1,1,50);
INSERT INTO artifact_field_usage VALUES (9,5,1,0,0,0);
INSERT INTO artifact_field_usage VALUES (10,5,1,1,1,0);
INSERT INTO artifact_field_usage VALUES (11,5,0,0,0,0);
INSERT INTO artifact_field_usage VALUES (12,5,1,0,0,0);

INSERT INTO artifact_field_value_list VALUES (7,5,100,'None','',10,'P');
INSERT INTO artifact_field_value_list VALUES (9,5,1,'Open','The artifact has been submitted',20,'P');
INSERT INTO artifact_field_value_list VALUES (9,5,3,'Closed','The artifact is no longer active. See the Resolution field for details on how it was resolved.',400,'P');
INSERT INTO artifact_field_value_list VALUES (9,5,4,'Analyzed','The cause of the artifact has been identified and documented',30,'H');
INSERT INTO artifact_field_value_list VALUES (9,5,5,'Accepted','The artifact will be worked on. If it won\'t be worked on, indicate why in the Resolution field and close it',50,'H');
INSERT INTO artifact_field_value_list VALUES (9,5,6,'Ready for Review','Updated/Created non-software work product (e.g. documentation) is ready for review and approval.',70,'H');
INSERT INTO artifact_field_value_list VALUES (9,5,7,'Ready for Test','Updated/Created software is ready to be included in the next build',90,'H');
INSERT INTO artifact_field_value_list VALUES (9,5,8,'In Test','Updated/Created software is in the build and is ready to enter the test phase',110,'H');
INSERT INTO artifact_field_value_list VALUES (9,5,9,'Approved','The artifact fix has been succesfully tested. It is approved and awaiting release.',130,'H');
INSERT INTO artifact_field_value_list VALUES (9,5,10,'Declined','The artifact was not accepted. Alternatively, you can also Set the status to \"Closed\" and use the Resolution field to explain why it was declined',150,'H');

INSERT INTO artifact_field_value_list VALUES (10,5,1,'1 - Ordinary','',10,'P');
INSERT INTO artifact_field_value_list VALUES (10,5,2,'2','',20,'P');
INSERT INTO artifact_field_value_list VALUES (10,5,3,'3','',30,'P');
INSERT INTO artifact_field_value_list VALUES (10,5,4,'4','',40,'P');
INSERT INTO artifact_field_value_list VALUES (10,5,5,'5 - Major','',50,'P');
INSERT INTO artifact_field_value_list VALUES (10,5,6,'6','',60,'P');
INSERT INTO artifact_field_value_list VALUES (10,5,7,'7','',70,'P');
INSERT INTO artifact_field_value_list VALUES (10,5,8,'8','',80,'P');
INSERT INTO artifact_field_value_list VALUES (10,5,9,'9 - Critical','',90,'P');

INSERT INTO artifact_field_value_list VALUES (12,5,1,'Accepted','The artifact will be worked on. If it won\'t be worked on, indicate why and close it',10,'A');
INSERT INTO artifact_field_value_list VALUES (12,5,2,'Declined','The artifact was not accepted. Alternatively, you can also set the status to \"Closed\" and explain why it was declined',50,'A');



---
--- Wiki Service
---

INSERT INTO service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (17, 100, 'service_wiki_lbl_key', 'service_wiki_desc_key', 'wiki', '/wiki/?group_id=\$group_id', 1, 1, 'system', 105);

INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (14, "wiki_admin", "Wiki Administrators", 100);

CREATE TABLE wiki_group_list (
	id int(11) NOT NULL auto_increment,
	group_id int(11) NOT NULL default '0',
	wiki_name varchar(255) NOT NULL default '',
	wiki_link varchar(255) NOT NULL default '',
	description varchar(255) NOT NULL default '',
	rank int(11) NOT NULL default '0',
        language_id int(11) NOT NULL default '1',
	PRIMARY KEY (id)	
) TYPE=MyISAM;

-- Table for Wiki access logs
CREATE TABLE wiki_log (
  user_id int(11) NOT NULL default '0',
  group_id int(11) NOT NULL default '0',
  pagename varchar(255) NOT NULL default '',
  time int(11) NOT NULL default '0',
  KEY all_idx (user_id,group_id),
  KEY time_idx (time),
  KEY group_id_idx (group_id)
) TYPE=MyISAM;


INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('WIKI_READ',100);
INSERT INTO permissions_values (permission_type,ugroup_id,is_default) VALUES ('WIKI_READ',2,1);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('WIKI_READ',3);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('WIKI_READ',4);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('WIKI_READ',14);

INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('WIKIPAGE_READ',100);
INSERT INTO permissions_values (permission_type,ugroup_id,is_default) VALUES ('WIKIPAGE_READ',2,1);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('WIKIPAGE_READ',3);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('WIKIPAGE_READ',4);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('WIKIPAGE_READ',14);

--
-- Info for Wiki Admin 
--

ALTER TABLE user_group ADD wiki_flags INT(11) NOT NULL DEFAULT 0;

--
-- PHP Wiki tables
--
CREATE TABLE wiki_page (
	id              INT NOT NULL AUTO_INCREMENT,
        pagename        VARCHAR(100) BINARY NOT NULL,
	hits            INT NOT NULL DEFAULT 0,
        pagedata        MEDIUMTEXT NOT NULL DEFAULT '',
	group_id        INT NOT NULL DEFAULT 0,
        PRIMARY KEY (id)
);

CREATE TABLE wiki_version (
	id              INT NOT NULL,
        version         INT NOT NULL,
	mtime           INT NOT NULL,
	minor_edit      TINYINT DEFAULT 0,
        content         MEDIUMTEXT NOT NULL DEFAULT '',
        versiondata     MEDIUMTEXT NOT NULL DEFAULT '',
        PRIMARY KEY (id,version),
	INDEX (mtime)
);


CREATE TABLE wiki_recent (
	id              INT NOT NULL,
	latestversion   INT,
	latestmajor     INT,
	latestminor     INT,
        PRIMARY KEY (id)
);


CREATE TABLE wiki_nonempty (
	id              INT NOT NULL,
	PRIMARY KEY (id)
);


CREATE TABLE wiki_link (
	linkfrom        INT NOT NULL,
        linkto          INT NOT NULL,
	INDEX (linkfrom),
        INDEX (linkto)
);


--
-- localising the DB 
--


-- artifact_notification_event table
--
ALTER TABLE artifact_notification_event ADD short_description_msg VARCHAR(255);
ALTER TABLE artifact_notification_event ADD description_msg VARCHAR(255);
UPDATE artifact_notification_event SET short_description_msg='event_ROLE_CHANGE_short_desc', description_msg='event_ROLE_CHANGE_desc' where event_label='ROLE_CHANGE';
UPDATE artifact_notification_event SET short_description_msg='event_NEW_COMMENT_short_desc', description_msg='event_NEW_COMMENT_desc' where event_label='NEW_COMMENT';
UPDATE artifact_notification_event SET short_description_msg='event_NEW_FILE_short_desc', description_msg='event_NEW_FILE_desc' where event_label='NEW_FILE';
UPDATE artifact_notification_event SET short_description_msg='event_CC_CHANGE_short_desc', description_msg='event_CC_CHANGE_desc' where event_label='CC_CHANGE';
UPDATE artifact_notification_event SET short_description_msg='event_CLOSED_short_desc', description_msg='event_CLOSED_desc' where event_label='CLOSED';
UPDATE artifact_notification_event SET short_description_msg='event_PSS_CHANGE_short_desc', description_msg='event_PSS_CHANGE_desc' where event_label='PSS_CHANGE';
UPDATE artifact_notification_event SET short_description_msg='event_ANY_OTHER_CHANGE_short_desc', description_msg='event_ANY_OTHER_CHANGE_desc' where event_label='ANY_OTHER_CHANGE';
UPDATE artifact_notification_event SET short_description_msg='event_I_MADE_IT_short_desc', description_msg='event_I_MADE_IT_desc' where event_label='I_MADE_IT';
UPDATE artifact_notification_event SET short_description_msg='event_NEW_ARTIFACT_short_desc', description_msg='event_NEW_ARTIFACT_desc' where event_label='NEW_ARTIFACT';

ALTER TABLE artifact_notification_event DROP short_description;
ALTER TABLE artifact_notification_event DROP description;


--  artifact_notification_event_default table
--
ALTER TABLE artifact_notification_event_default ADD short_description_msg VARCHAR(255);
ALTER TABLE artifact_notification_event_default ADD description_msg VARCHAR(255);
UPDATE artifact_notification_event_default SET short_description_msg='event_ROLE_CHANGE_short_desc', description_msg='event_ROLE_CHANGE_desc' where event_label='ROLE_CHANGE';
UPDATE artifact_notification_event_default SET short_description_msg='event_NEW_COMMENT_short_desc', description_msg='event_NEW_COMMENT_desc' where event_label='NEW_COMMENT';
UPDATE artifact_notification_event_default SET short_description_msg='event_NEW_FILE_short_desc', description_msg='event_NEW_FILE_desc' where event_label='NEW_FILE';
UPDATE artifact_notification_event_default SET short_description_msg='event_CC_CHANGE_short_desc', description_msg='event_CC_CHANGE_desc' where event_label='CC_CHANGE';
UPDATE artifact_notification_event_default SET short_description_msg='event_CLOSED_short_desc', description_msg='event_CLOSED_desc' where event_label='CLOSED';
UPDATE artifact_notification_event_default SET short_description_msg='event_PSS_CHANGE_short_desc', description_msg='event_PSS_CHANGE_desc' where event_label='PSS_CHANGE';
UPDATE artifact_notification_event_default SET short_description_msg='event_ANY_OTHER_CHANGE_short_desc', description_msg='event_ANY_OTHER_CHANGE_desc' where event_label='ANY_OTHER_CHANGE';
UPDATE artifact_notification_event_default SET short_description_msg='event_I_MADE_IT_short_desc', description_msg='event_I_MADE_IT_desc' where event_label='I_MADE_IT';
UPDATE artifact_notification_event_default SET short_description_msg='event_NEW_ARTIFACT_short_desc', description_msg='event_NEW_ARTIFACT_desc' where event_label='NEW_ARTIFACT';

ALTER TABLE artifact_notification_event_default DROP short_description;
ALTER TABLE artifact_notification_event_default DROP description;


--  artifact_notification_role table
--
ALTER TABLE artifact_notification_role ADD short_description_msg VARCHAR(255);
ALTER TABLE artifact_notification_role ADD description_msg VARCHAR(255);
UPDATE artifact_notification_role SET short_description_msg='role_SUBMITTER_short_desc', description_msg='role_SUBMITTER_desc' where role_label='SUBMITTER';
UPDATE artifact_notification_role SET short_description_msg='role_ASSIGNEE_short_desc', description_msg='role_ASSIGNEE_desc' where role_label='ASSIGNEE';
UPDATE artifact_notification_role SET short_description_msg='role_CC_short_desc', description_msg='role_CC_desc' where role_label='CC';
UPDATE artifact_notification_role SET short_description_msg='role_COMMENTER_short_desc', description_msg='role_COMMENTER_desc' where role_label='COMMENTER';

ALTER TABLE artifact_notification_role DROP short_description;
ALTER TABLE artifact_notification_role DROP description;


--  artifact_notification_role_default table
--
ALTER TABLE artifact_notification_role_default ADD short_description_msg VARCHAR(255);
ALTER TABLE artifact_notification_role_default ADD description_msg VARCHAR(255);
UPDATE artifact_notification_role_default SET short_description_msg='role_SUBMITTER_short_desc', description_msg='role_SUBMITTER_desc' where role_label='SUBMITTER';
UPDATE artifact_notification_role_default SET short_description_msg='role_ASSIGNEE_short_desc', description_msg='role_ASSIGNEE_desc' where role_label='ASSIGNEE';
UPDATE artifact_notification_role_default SET short_description_msg='role_CC_short_desc', description_msg='role_CC_desc' where role_label='CC';
UPDATE artifact_notification_role_default SET short_description_msg='role_COMMENTER_short_desc', description_msg='role_COMMENTER_desc' where role_label='COMMENTER';

ALTER TABLE artifact_notification_role_default DROP short_description;
ALTER TABLE artifact_notification_role_default DROP description;


-- File release status
update frs_status set name='status_active' where name='Active'; 
update frs_status set name='status_hidden' where name='Hidden';


--
-- service table
--

UPDATE service SET label='service_summary_lbl_key', description='service_summary_desc_key' WHERE short_name='summary' AND label='Summary' AND description='Project Summary';
UPDATE service SET label='service_admin_lbl_key', description='service_admin_desc_key' WHERE short_name='admin' AND label='Admin' AND description='Project Administration';
UPDATE service SET label='service_homepage_lbl_key', description='service_homepage_desc_key' WHERE short_name='homepage' AND label='Home Page' AND description='Project Home Page';
UPDATE service SET label='service_forum_lbl_key', description='service_forum_desc_key' WHERE short_name='forum' AND label='Forums' AND description='Project Forums';
UPDATE service SET label='service_bugs_lbl_key', description='service_bugs_desc_key' WHERE short_name='bugs' AND label='Bugs' AND description='Bug Tracking System';
UPDATE service SET label='service_support_lbl_key', description='service_support_desc_key' WHERE short_name='support' AND label='Support' AND description='Support Request Manager';
UPDATE service SET label='service_patch_lbl_key', description='service_patch_desc_key' WHERE short_name='patch' AND label='Patches' AND description='Patch Manager';
UPDATE service SET label='service_mail_lbl_key', description='service_mail_desc_key' WHERE short_name='mail' AND label='Lists' AND description='Mailing Lists';
UPDATE service SET label='service_task_lbl_key', description='service_task_desc_key' WHERE short_name='task' AND label='Tasks' AND description='Task Manager';
UPDATE service SET label='service_doc_lbl_key', description='service_doc_desc_key' WHERE short_name='doc' AND label='Docs' AND description='Document Manager';
UPDATE service SET label='service_survey_lbl_key', description='service_survey_desc_key' WHERE short_name='survey' AND label='Surveys' AND description='Project Surveys';
UPDATE service SET label='service_news_lbl_key', description='service_news_desc_key' WHERE short_name='news' AND label='News' AND description='Project News';
UPDATE service SET label='service_cvs_lbl_key', description='service_cvs_desc_key' WHERE short_name='cvs' AND label='CVS' AND description='CVS Access';
UPDATE service SET label='service_file_lbl_key', description='service_file_desc_key' WHERE short_name='file' AND label='Files' AND description='File Releases';
UPDATE service SET label='service_tracker_lbl_key', description='service_tracker_desc_key' WHERE short_name='tracker' AND label='Trackers' AND description='Project Trackers';
UPDATE service SET label='service_svn_lbl_key', description='service_svn_desc_key' WHERE short_name='svn' AND label='Subversion' AND description='Subversion Access';
UPDATE service SET label='service_wiki_lbl_key', description='service_wiki_desc_key' WHERE short_name='wiki' AND label='Wiki' AND description='Wiki';


EOF


###############################################################################
# Update document access rights
#
$PERL <<'EOF'
use DBI;
require "/home/httpd/SF/utils/include.pl";

## load local.inc variables
&load_local_config();

&db_connect;


## get existing values from the doc_data table
$query = "SELECT docid,stateid,doc_group,restricted_access FROM doc_data";
$c = $dbh->prepare($query);
$c->execute();

my $failed=0;

while (my ($docid,$stateid,$doc_group,$restricted_access) = $c->fetchrow()) {
    if ($stateid=="2") { #deleted
        my $query3 = "DELETE FROM doc_data WHERE docid='$docid'";
        my $c3 = $dbh->prepare($query3);
        my $res=$c3->execute();
        if (!$res) {
            print "Could not delete document $docid\n";
            $failed=1;;
        }
    } elsif ($stateid=="4") { #hidden
        my $query3 = "INSERT INTO permissions VALUES ('DOCUMENT_READ',$docid,100)";
        my $c3 = $dbh->prepare($query3);
        my $res=$c3->execute();
        if (!$res) {
            print "Could not set hidden status for document $docid\n";
            $failed=1;;
        }
    } elsif ($stateid=="5") { #private
        my $query3 = "INSERT INTO permissions VALUES ('DOCUMENT_READ',$docid,3)";
        my $c3 = $dbh->prepare($query3);
        my $res=$c3->execute();
        if (!$res) {
            print "Could not set private status to document $docid\n";
            $failed=1;;
        }
    } else { #active or pending, check restricted_access
        if ($restricted_access=="1") {
            my $query3 = "INSERT INTO permissions VALUES ('DOCUMENT_READ',$docid,2)";
            my $c3 = $dbh->prepare($query3);
            my $res=$c3->execute();
            if (!$res) {
                print "Could not restrict access to document $docid\n";
                $failed=1;;
            }
        }
    }
}

# Now delete useless rows
if (!$failed) {
    my $query3 = "ALTER TABLE doc_data DROP COLUMN stateid, DROP COLUMN restricted_access";
    my $c3 = $dbh->prepare($query3);
    my $res=$c3->execute();
    if (!$res) {
        print "Could not drop columns from doc_data\n";
        $failed=1;;
    }
}

exit;
EOF



###############################################################################
# Add wiki service
#


$PERL <<'EOF'
use DBI;
require "/home/httpd/SF/utils/include.pl";

# load local.inc variables
&load_local_config();

&db_connect;

sub wiki_exist {
    my ($group_id) = @_;
    
    my ($query, $c, $res);
    $query = "SELECT service_id FROM service WHERE group_id=".$group_id." AND short_name='wiki'";
    $c = $dbh->prepare($query);
    $c->execute();
    return $c->rows;
}

# Check wiki service entry existency before calling this script
sub check_wiki_service_exist {
    my ($query, $c, $res);

    $query = "SELECT * FROM service WHERE service_id=17";
    $c = $dbh->prepare($query);
    $c->execute();
    if ($c->rows != 1) {
	print "Please apply SQL patch before executing this script!\n";
	exit 1;
    }
}

sub add_wiki {
    my ($group_id) = @_;
    my ($query, $c, $res);
    $query = "INSERT INTO service SET group_id=".$group_id.", label='Wiki', description='Wiki', short_name='wiki', link='/wiki/?group_id=".$group_id."', is_active=1, is_used=0, scope='system', rank=105";
    $c = $dbh->prepare($query);
    $res = $c->execute();
    if(!$res) {
	return FALSE;
    }
    return TRUE;
}

sub add_wiki_service {
    my ($group_id) = @_;

    if(!wiki_exist($group_id)) {
	if(!add_wiki($group_id)) {
	    print " Error Adding Wiki service for $group_id\n";
	    exit;
	}
	else {
	    #print " OK\n";
	}
    }
    else {
	print 'There is already a wiki service for '.$group_id."\n";
    }

}

sub setup_interwiki_map {
    my $interwikimap = '/home/httpd/SF/common/wiki/phpwiki/lib/interwiki.map';
    
    print 'Update '.$interwikimap."\n";

    if(($sys_force_ssl == 1) || ($sys_stay_in_ssl == 1)) {
	$host = 'https://'.$sys_https_host;
    }
    else {
	$host = 'http://'.$sys_default_domain;
    }
    
    open(FILE, ">$interwikimap") || die "Can't open $interwikimap: $!\n";
    print FILE "CodeX ".$host."/wiki/index.php?group_id=1&pagename=\n";
    close(FILE)
}

sub setup_wiki_admin_rights {
    my ($group_id) = @_;
    my ($query, $c, $res);
    $query = "UPDATE user_group SET wiki_flags='2' WHERE admin_flags='A'";
    $c = $dbh->prepare($query);
    $res = $c->execute();
    if(!$res) {
	return FALSE;
    }
    return TRUE;
}

#
# MAIN
#

# Check wiki service entry existence before calling this script
check_wiki_service_exist();

# Insert a wiki for each project
my ($query, $c, $res);
$query = "SELECT group_id FROM service WHERE group_id>100 OR group_id=1 GROUP BY group_id";
$c = $dbh->prepare($query);
$c->execute();
while(my $group_id = $c->fetchrow()) {
    add_wiki_service($group_id);
    setup_wiki_admin_rights($group_id);
}

# Setup interwiki_map - now useless since URL is relative.
#setup_interwiki_map();



exit;
EOF



##############################################
# Reinstall modified shell scripts
#
$CP $INSTALL_DIR/SF/utils/svn/commit-email.pl /usr/local/bin
$CHOWN sourceforge.sourceforge /usr/local/bin/commit-email.pl
$CHMOD 775 /usr/local/bin/commit-email.pl
$CHMOD u+s /usr/local/bin/commit-email.pl

$CP $INSTALL_DIR/SF/utils/cvs1/log_accum /usr/local/bin
$CHOWN sourceforge.sourceforge /usr/local/bin/log_accum
$CHMOD 775 /usr/local/bin/log_accum
$CHMOD u+s /usr/local/bin/log_accum

# should have been done before but...
$CP $INSTALL_DIR/SF/utils/cvs1/cvssh-restricted /usr/local/bin


##############################################
# Check for the existence of the following customized content files and advise the person in charge of the installation of the new .tab files to customize to obtain the same effect
#

service="account"
file_exist=0
for sitefile in register_confirmation.txt register_needs_approval.txt \
    register_email.txt register_purpose.txt register_login.txt
do
    $RPM -q $rpm  2>/dev/null 1>&2
    if [ -e /etc/codex/site-content/en_US/$service/$sitefile ]; then
        file_exist=1
        echo /etc/codex/site-content/en_US/$service/$sitefile
    fi
done
if [ $file_exist -eq 1 ]; then
    echo "The file(s) listed above are no longer used. Please customize /etc/codex/site-content/en_US/$service/$service.tab to obtain the same effect, then delete the old files."
fi

service="homepage"
file_exist=0
for sitefile in staff.txt thanks.txt welcome_intro.txt
do
    $RPM -q $rpm  2>/dev/null 1>&2
    if [ -e /etc/codex/site-content/en_US/$service/$sitefile ]; then
        file_exist=1
        echo /etc/codex/site-content/en_US/$service/$sitefile
    fi
done
if [ $file_exist -eq 1 ]; then
    echo "The file(s) listed above are no longer used. Please customize /etc/codex/site-content/en_US/$service/$service.tab to obtain the same effect, then delete the old files."
fi

service="my"
file_exist=0
for sitefile in intro.txt
do
    $RPM -q $rpm  2>/dev/null 1>&2
    if [ -e /etc/codex/site-content/en_US/$service/$sitefile ]; then
        file_exist=1
        echo /etc/codex/site-content/en_US/$service/$sitefile
    fi
done
if [ $file_exist -eq 1 ]; then
    echo "The file(s) listed above are no longer used. Please customize /etc/codex/site-content/en_US/$service/$service.tab to obtain the same effect, then delete the old files."
fi

# other modified files
file_exist=0
for sitefile in contact/contact.txt \
cvs/intro.txt \
file/editrelease_attach_file.txt \
file/qrs_attach_file.txt \
include/new_project_email.txt \
include/restricted_user_permissions.txt \
layout/footer.txt \
others/default_page.php \
register/complete.txt \
register/intro.txt \
register/license.txt \
register/projectname.txt \
register/registration.txt \
register/tos.txt \
svn/intro.txt \
tos/privacy.txt
do
    $RPM -q $rpm  2>/dev/null 1>&2
    if [ -e /etc/codex/site-content/en_US/$service/$sitefile ]; then
        file_exist=1
        echo /etc/codex/site-content/en_US/$service/$sitefile
    fi
done
if [ $file_exist -eq 1 ]; then
    echo "The file(s) listed above have change in CodeX 2.4. Please check that your customized files are still up-to-date."
fi


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
todo "Make sure that the CVS update is possible in /home/httpd/SF/utils/utils/generate_doc.sh. Do a cvs login on CVS server as user 'sourceforge'."
/home/httpd/SF/utils/generate_doc.sh -f
$CHOWN -R sourceforge.sourceforge $INSTALL_DIR/documentation


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
todo "Note that most PHP configuration is now set in /etc/httpd/conf.d/php.conf instead of /etc/php.ini "
todo "One exception: add the following parameter in /etc/php.ini: 'upload_tmp_dir = /home/large_tmp'


# End of it
echo "=============================================="
echo "Installation completed succesfully!"
$CAT $TODO_FILE

exit 1;
