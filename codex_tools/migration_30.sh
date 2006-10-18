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
#  This script migrates a site running CodeX 2.8 to CodeX 3.0
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
# CodeX 2.8 to 3.0 migration
##############################################
echo "Migration script from CodeX 2.8 data to CodeX 3.0"
echo "This script must be run AFTER a clean CodeX 3.0 installation, and copy of CodeX 2.8 data."
echo "Read migration_30.README.FIRST for details"
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




################################################################################

Various Notes concerning CodeX 2.8 to 3.0 upgrade.


Done in 2.8 support branch:
- redirect commit-email.pl to /dev/null


TODO in migration_30
- Convert BDB to FSFS?
- /usr/local/bin/log_accum and commit_prep called from CVS hooks... commit-email called from SVN post-commit. -> create links or update?
- /etc/aliases.codex: /home/mailman/mail/mailman
- mailman public archive links to private...
- server update plugin instanciation at install time...

Add question: do you wish to use HTTPS
-> ssl.conf
-> phpMyadmin conf
-> generate certificate (optional)


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

echo "Starting DB update for CodeX 3.0. This might take a few minutes."

$CAT <<EOF | $MYSQL $pass_opt sourceforge

###############################################################################
# Fieldset: create tables
#

DROP TABLE IF EXISTS artifact_field_set;
CREATE TABLE artifact_field_set (
    field_set_id int(11) unsigned NOT NULL auto_increment,
    group_artifact_id int(11) unsigned NOT NULL default '0',
    name text NOT NULL,
    description text NOT NULL,
    rank int(11) unsigned NOT NULL default '0',
    PRIMARY KEY  (field_set_id),
    KEY idx_fk_group_artifact_id (group_artifact_id)
);

ALTER TABLE artifact_field ADD field_set_id INT( 11 ) UNSIGNED NOT NULL AFTER group_artifact_id;



###############################################################################
# Project Templates
#

#
#  Default data for project_type
#
DROP TABLE IF EXISTS group_type;
CREATE TABLE group_type (
  type_id int(11) NOT NULL,
  name text NOT NULL default '',
  PRIMARY KEY  (type_id)
) TYPE=MyISAM;

INSERT INTO group_type VALUES ('1','project');
INSERT INTO group_type VALUES ('2','template');
INSERT INTO group_type VALUES ('3','test_project');

ALTER TABLE groups ADD built_from_template int(11) NOT NULL default '100' AFTER type;

# mark project 100  as template created from itself (built-from-template = 100)
UPDATE groups set type = '2', group_name = 'Default Site Template', short_description = 'The default CodeX template' where group_id = '100';



###############################################################################
# Survey Manager
# 1- create a new table 'survey_radio_choices' to the survey manager database. 
# This table contains all useful information about edited radio buttons, it has 
# 4 columns : 'choice_id', 'question_id', 'radio_choice' and 'choice_rank'
# 2- define a new question type 'Radio Buttons' in 'survey_question_types' table
# 3- change type name of yes/no questions from 'Radio Button Yes/No' to 'Yes/No'
# 
# References:
# request #391
#

## Create the new table 'survey_radio_choices'
CREATE TABLE survey_radio_choices (
  choice_id int(11) NOT NULL auto_increment,
  question_id int(11) NOT NULL default '0',  
  choice_rank int(11) NOT NULL default '0',
  radio_choice text NOT NULL,
  PRIMARY KEY  (choice_id),
  KEY idx_survey_radio_choices_question_id (question_id)  
) TYPE=MyISAM;

## Make it possible to show question types in the order we like
ALTER TABLE survey_question_types ADD COLUMN rank int(11) NOT NULL default '0';
## Localize question types
UPDATE survey_question_types SET type='radio_buttons_1_5', rank='21' WHERE type='Radio Buttons 1-5';
UPDATE survey_question_types SET type='text_area', rank='30' WHERE type='Text Area';
UPDATE survey_question_types SET type='radio_buttons_yes_no', rank='22' WHERE type='Radio Buttons Yes/No' OR type='Yes/No';
UPDATE survey_question_types SET type='comment_only', rank='10' WHERE type='Comment Only';
UPDATE survey_question_types SET type='text_field', rank='31' WHERE type='Text Field';
UPDATE survey_question_types SET type='none', rank='40' WHERE type='None';

## Add new type value 'Radio Buttons', id=6, in 'survey_question_types' table
DELETE FROM survey_question_types WHERE id='6';
INSERT INTO survey_question_types (id, type, rank) VALUES (6,'radio_buttons','20');

## Localize Developer Survey title
UPDATE surveys SET survey_title = 'dev_survey_title_key' WHERE survey_id='1';


###############################################################################
# Private News
# Add entries in permissions_values table, corresponding to 'News' item.
# Default permission is 'read for anonymous users'
#


INSERT INTO permissions_values (permission_type,ugroup_id,is_default) values ('NEWS_READ',1,1);


###############################################################################
# Multiple ugroup bind of tracker field value_function
#

ALTER TABLE artifact_field MODIFY value_function TEXT;

###############################################################################
# name of plugin is unique
#

ALTER TABLE plugin ADD UNIQUE ( name );


###############################################################################
# typo in trove_cat
#

UPDATE trove_cat SET shortname = 'communications' WHERE shortname = 'coomunications';


###############################################################################
# Old document manager is now legacy
#

UPDATE service SET is_active = 0, is_used = 0 WHERE group_id = 100 AND short_name = 'doc';
REPLACE INTO plugin (name, available) VALUES ('docman', '1');

###############################################################################
# Active plugin serverupdate
#
INSERT INTO plugin (name, available) VALUES ('serverupdate', '1');

###############################################################################
# Add permissions for 'stage' field
#

INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','1#30',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','1#30',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','2#15',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','2#15',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','3#12',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','3#12',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','4#11',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','4#11',3);


EOF


################################################################################
echo " DB - Fieldset update"

$PERL <<'EOF'
use DBI;
use Sys::Hostname;
use Carp;

require $ENV{INSTALL_DIR}."/src/utils/include.pl";  # Include all the predefined functions

&load_local_config();

&db_connect;

# Looking for all trackers
$query_trackers = "SELECT group_artifact_id FROM artifact_group_list";
$result_trackers = $dbh->prepare($query_trackers);
$result_trackers->execute();
# For each tracker ...
while (my ($group_artifact_id) = $result_trackers->fetchrow()) {
    # Create a new fieldset with default name, and attach it to the current tracker
    $insert_fieldset = "INSERT INTO artifact_field_set (group_artifact_id, name, description, rank) VALUES ($group_artifact_id, 'fieldset_default_lbl_key', 'fieldset_default_desc_key', 10)";
    $result_insert_fieldset = $dbh->prepare($insert_fieldset);
    $result_insert_fieldset->execute();

    # Retrieve the id number of the new fieldset just created
    $fieldset_id = $result_insert_fieldset->{'mysql_insertid'};
    
    # Looking for all fields of the current tracker
    $query_fields = "SELECT field_id FROM artifact_field WHERE group_artifact_id=$group_artifact_id";
    $result_fields = $dbh->prepare($query_fields);
    $result_fields->execute();
    # For each field of the current tracker ...
    while (my ($field_id) = $result_fields->fetchrow()) {
        # attach the field to the new fieldset just created
        $update_field = "UPDATE artifact_field SET field_set_id=$fieldset_id WHERE group_artifact_id=$group_artifact_id AND field_id=$field_id";
        $result_update_field = $dbh->prepare($update_field);
        $result_update_field->execute();
    }
}
EOF

echo " DB - Artifact details Field and Follow-up comments update"
################################################################################
# artifact_history: updating values
#
$CAT <<EOF | $MYSQL $pass_opt sourceforge

UPDATE artifact_history 
SET field_name='comment' 
WHERE field_name='details' 

EOF


################################################################################
# PLUGIN Docman
# Insert CodeX documentation in Project 1 docman
#

$PERL <<'EOF'
use DBI;
use Sys::Hostname;
use Carp;

require $ENV{INSTALL_DIR}."/src/utils/include.pl";  # Include all the predefined functions

&db_connect;

#Does the plugin already installed ?
$result_docman = $dbh->prepare("SHOW TABLES LIKE 'plugin_docman_item'");
$result_docman->execute();
if ($result_docman->fetchrow()) {
    
    #Docman is already installed
    $permissions = $dbh->prepare("INSERT INTO permissions(permission_type, ugroup_id, object_id) VALUES ('PLUGIN_DOCMAN_READ', 1, ?), ('PLUGIN_DOCMAN_MANAGE', 1, ?)");
    $insert = $dbh->prepare("INSERT INTO plugin_docman_item (parent_id, group_id, title, description,           create_date,           update_date, delete_date, user_id, status, obsolescence_date, rank, item_type, link_url, wiki_page, file_is_embedded) 
                                                     VALUES (        ?,        1,     ?,           ?, UNIX_TIMESTAMP(NOW()), UNIX_TIMESTAMP(NOW()),        NULL,     101,      0,                 0,    ?,         ?,        ?,         ?,             NULL);");
    $result_root = $dbh->prepare("SELECT item_id FROM plugin_docman_item WHERE group_id = 1 AND parent_id = 0");
    $result_root->execute();
    my($root) = $result_root->fetchrow();
    if (!$root) {
        # create a root
        $insert->execute(0, 'Documentation du projet', '', 0, 1, undef, undef);
        $id = $dbh->{q{mysql_insertid}};
        $permissions->execute($id, $id);
        $root = $id;
    }
    $insert->execute($root, 'English Documentation', '', 0, 1, undef, undef);
    $id = $dbh->{q{mysql_insertid}};
    $permissions->execute($id, $id);
    $en = $id;
    
    $insert->execute($en, 'CodeX User Guide', 'A comprehensive guide describing all the CodeX services and how to use them in an optimal way. Also provides a lot of useful tips and guidelines to manage your CodeX project efficiently.', -1, 1, undef, undef);
    $id = $dbh->{q{mysql_insertid}};
    $permissions->execute($id, $id);
    $cug = $id;
    $insert->execute($cug, 'PDF Version', '', -1, 3, '/documentation/user_guide/pdf/en_US/CodeX_User_Guide.pdf', '');
    $id = $dbh->{q{mysql_insertid}};
    $permissions->execute($id, $id);
    $insert->execute($cug, 'Multi-page HTML Version', '', 1, 3, '/documentation/user_guide/html/en_US/index.html', '');
    $id = $dbh->{q{mysql_insertid}};
    $permissions->execute($id, $id);
    $insert->execute($cug, 'Single-page HTML (2.7 MB) Version', '', 2, 3, '/documentation/user_guide/html/en_US/CodeX_User_Guide.html', '');
    $id = $dbh->{q{mysql_insertid}};
    $permissions->execute($id, $id);
    
    $insert->execute($en, 'Command-Line Interface', 'A comprehensive guide describing all the functions of the CodeX Command-Line Interface.', 1, 1, undef, undef);
    $id = $dbh->{q{mysql_insertid}};
    $permissions->execute($id, $id);
    $cli = $id;
    $insert->execute($cli, 'PDF Version', '', -3, 3, '/documentation/cli/pdf/en_US/CodeX_CLI.pdf', '');
    $id = $dbh->{q{mysql_insertid}};
    $permissions->execute($id, $id);
    $insert->execute($cli, 'Multi-page HTML Version', '', -2, 3, '/documentation/cli/html/en_US/index.html', '');
    $id = $dbh->{q{mysql_insertid}};
    $permissions->execute($id, $id);
    $insert->execute($cli, 'Single-page HTML Version', '', 0, 3, '/documentation/cli/html/en_US/CodeX_CLI.html', '');
    $id = $dbh->{q{mysql_insertid}};
    $permissions->execute($id, $id);
    
    $insert->execute($root, 'Documentation en français', '', 1, 1, undef, undef);
    $id = $dbh->{q{mysql_insertid}};
    $permissions->execute($id, $id);
    $fr = $id;
    
    $insert->execute($fr, 'Guide de l\'Utilisateur CodeX', 'Un guide complet décrivant tous les services de CodeX et comment les utiliser de manière optimale. Fournit également de nombreuses astuces et explications pour gérer efficacement votre projet CodeX.', -1, 1, undef, undef);
    $id = $dbh->{q{mysql_insertid}};
    $permissions->execute($id, $id);
    $cug = $id;
    $insert->execute($cug, 'Version PDF', '', -1, 3, '/documentation/user_guide/pdf/fr_FR/CodeX_User_Guide.pdf', '');
    $id = $dbh->{q{mysql_insertid}};
    $permissions->execute($id, $id);
    $insert->execute($cug, 'Version HTML multi-pages', '', 1, 3, '/documentation/user_guide/html/fr_FR/index.html', '');
    $id = $dbh->{q{mysql_insertid}};
    $permissions->execute($id, $id);
    $insert->execute($cug, 'Version HTML une page (4,2 Mo)', '', 2, 3, '/documentation/user_guide/html/fr_FR/CodeX_User_Guide.html', '');
    $id = $dbh->{q{mysql_insertid}};
    $permissions->execute($id, $id);
    
    $insert->execute($fr, 'Interface de Commande en Ligne', 'Un guide complet décrivant toutes les fonctions de l\'Interface de Commande en Ligne de CodeX.', 0, 1, undef, undef);
    $id = $dbh->{q{mysql_insertid}};
    $permissions->execute($id, $id);
    $cli = $id;
    $insert->execute($cli, 'Version PDF', '', 3, 3, '/documentation/cli/pdf/fr_FR/CodeX_CLI.pdf', '');
    $id = $dbh->{q{mysql_insertid}};
    $permissions->execute($id, $id);
    $insert->execute($cli, 'Version HTML multi-pages', '', 4, 3, '/documentation/cli/html/fr_FR/index.html', '');
    $id = $dbh->{q{mysql_insertid}};
    $permissions->execute($id, $id);
    $insert->execute($cli, 'Version HTML une page', '', 5, 3, '/documentation/cli/html/fr_FR/CodeX_CLI.html', '');
    $id = $dbh->{q{mysql_insertid}};
    $permissions->execute($id, $id);
} else {
    #Docman must be installed
    `$ENV{MYSQL} -h $sys_dbhost -u $sys_dbuser $sys_dbname --password=$sys_dbpasswd < $ENV{INSTALL_DIR}/plugins/docman/db/install.sql`
}
EOF

################################################################################
# install PLUGIN serverupdate
#
$CAT $INSTALL_DIR/plugins/serverupdate/db/install.sql | $MYSQL -u codexadm codex --password=$codexadm_passwd

echo "End of main DB upgrade"



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
$SERVICE smb start



todo "If you have custom themes, in stylesheets, please :"
todo " - replace horitontal-align by text-align"
todo " - add the rule"
todo "        img { border:none; }"
todo "-----------------------------------------"
todo "This TODO list is available in $TODO_FILE"


# End of it
echo "=============================================="
echo "Migration completed succesfully!"
$CAT $TODO_FILE

exit 1;


