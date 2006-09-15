


Various Notes concerning CodeX 2.8 to 3.0 upgrade.


Done in 2.8 support branch:
- copy new backup_job in /home/tools
- add sys_default_trove_cat in local.inc
- redirect commit-email.pl to /dev/null


TODO in migration_30
- when moving httpd to httpd_28, don t forget to move the '.subversion' directory back
- Convert BDB to FSFS?
- /usr/local/bin/log_accum and commit_prep called from CVS hooks... commit-email called from SVN post-commit. -> create links or update?

RHEL4 Testing:

/usr/sbin/groupadd -g "104" sourceforge
/usr/sbin/groupadd -g "96" ftpadmin
/usr/sbin/useradd  -c 'Owner of CodeX directories' -M -d '/home/httpd' -p "$1$h67e4niB$xUTI.9DkGdpV.B65r1NVl/" -u 104 -g 104 -s '/bin/bash' -G ftpadmin sourceforge

don t need perl-CGI
'mysql' service is now called 'mysqld' -> update install guide.
remove --force and --nodeps?


[root@malaval RPMS]# ls -Za /home/httpd
drwxrwxr-x  sourcefo sourcefo root:object_r:user_home_dir_t    .
drwxr-xr-x  root     root     system_u:object_r:home_root_t    ..
-rw-------  sourcefo sourcefo user_u:object_r:user_home_t      .bash_history
drwxr-xr-x  sourcefo sourcefo root:object_r:user_home_t        cgi-bin
drwxr-xr-x  sourcefo sourcefo root:object_r:user_home_t        documentation
drwxr-xr-x  sourcefo sourcefo root:object_r:user_home_t        plugins
drwxr-xr-x  sourcefo sourcefo root:object_r:user_home_t        SF
drwxr-xr-x  sourcefo sourcefo root:object_r:user_home_t        site-content


chcon -R -h -t httpd_sys_content_t /home/httpd

[root@malaval RPMS]# ls -Za /home/httpd
drwxrwxr-x  sourcefo sourcefo root:object_r:httpd_sys_content_t .
drwxr-xr-x  root     root     system_u:object_r:home_root_t    ..
-rw-------  sourcefo sourcefo user_u:object_r:httpd_sys_content_t .bash_history
drwxr-xr-x  sourcefo sourcefo root:object_r:httpd_sys_content_t cgi-bin
drwxr-xr-x  sourcefo sourcefo root:object_r:httpd_sys_content_t documentation
drwxr-xr-x  sourcefo sourcefo root:object_r:httpd_sys_content_t plugins
drwxr-xr-x  sourcefo sourcefo root:object_r:httpd_sys_content_t SF
drwxr-xr-x  sourcefo sourcefo root:object_r:httpd_sys_content_t site-content


[root@malaval RPMS]# ls -Za /home/ftp/codex/
drwxr-xr-x  root     root     root:object_r:user_home_t        .
drwxr-xr-x  root     root     root:object_r:user_home_dir_t    ..

chcon -R -h -t httpd_sys_content_t /home/ftp/codex/


PHPMyAdmin:
[root@malaval scripts]# ls -Z /var/lib/php
drwxrwx---  root     apache   system_u:object_r:httpd_var_run_t session
[root@malaval scripts]# chmod 777 /var/lib/php/session

Add question: do you wish to use HTTPS
-> ssl.conf
-> phpMyadmin conf
-> generate certificate (optional)



chcon -R -h -t httpd_sys_content_t /home/groups
chcon -R -h -t httpd_sys_content_t /home/sfcache
chcon -R -h -t httpd_sys_content_t /etc/codex


RPMs mandatory:
#mrtg ?
# munin needs perl-DateManip and sysstat + external RPMs: perl-HTML-Template perl-Net-Server rrdtool perl-rrdtool
# cp /usr/share/doc/munin-1.2.4/README-apache-cgi /etc/httpd/conf.d/munin.conf + edit to add alias
# /usr/sbin/munin-node-configure -> useless
Add option: install munin?



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
    read -s -p "Existing CodeX DB is password protected. What is the Mysql root password?: " old_passwd
    echo
    mysqlshow --password=$old_passwd 2>&1 | grep password
done
[ "X$old_passwd" != "X" ] && pass_opt="--password=$old_passwd"

echo "Starting DB update for CodeX 3.0. This might take a few minutes."

echo " DB - Fieldset update"
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

EOF


################################################################################
echo " Upgrading 2.8 if needed"

$PERL <<'EOF'
use DBI;
use Sys::Hostname;
use Carp;

require "/home/httpd/SF/utils/include.pl";  # Include all the predefined functions

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
$CAT <<EOF | $MYSQL $pass_opt sourceforge

################################################################################
# artifact_history: updating values
#
UPDATE artifact_history 
SET field_name='comment' 
WHERE field_name='details' 

EOF

echo "End of main DB upgrade"
