


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


################################################################################
# PLUGIN Docman
#

#Does the plugin already installed ?
$result_docman = $dbh->prepare("SHOW TABLES LIKE 'plugin_docman_item'");
$result_docman->execute();
if (result_trackers->fetchrow()) {
    
    $permissions = $dbh->prepare("INSERT INTO permissions(permission_type, ugroup_id, object_id) VALUES ('PLUGIN_DOCMAN_READ', 1, ?), ('PLUGIN_DOCMAN_MANAGE', 1, ?)");
    $insert = $dbh->prepare("INSERT INTO plugin_docman_item (parent_id, group_id, title, description,           create_date,           update_date, delete_date, user_id, status, obsolescence_date, rank, item_type, link_url, wiki_page, file_is_embedded) 
                                                     VALUES (        ?,        1,     ?,           ?, UNIX_TIMESTAMP(NOW()), UNIX_TIMESTAMP(NOW()),        NULL,     101,      0,                 0,    ?,         ?,        ?,         ?,             NULL);");
    
    #Docman is already installed
    $insert->execute(0, 'Documentation du projet', '', 0, 1, undef, undef);
    $id = $dh->last_insert_id();
    $permissions->execute($id, $id);
    $root = $id;

    $insert = $dbh->prepare($root, 'English Documentation', '', 0, 1, undef, undef);
    $insert->execute();
    $id = $dh->last_insert_id();
    $permissions->execute($id, $id);
    $en = $id;
    
    $insert = $dbh->prepare($en, 'CodeX User Guide', 'A comprehensive guide describing all the CodeX services and how to use them in an optimal way. Also provides a lot of useful tips and guidelines to manage your CodeX project efficiently.', -1, 1, undef, undef);
    $insert->execute();
    $id = $dh->last_insert_id();
    $permissions->execute($id, $id);
    $cug = $id;
    $insert = $dbh->prepare($cug, 'PDF Version', '', -1, 3, '/documentation/user_guide/pdf/en_US/CodeX_User_Guide.pdf', '');
    $insert->execute();
    $id = $dh->last_insert_id();
    $permissions->execute($id, $id);
    $insert = $dbh->prepare($cug, 'Multi-page HTML Version', '', 1, 3, '/documentation/user_guide/html/en_US/index.html', '');
    $insert->execute();
    $id = $dh->last_insert_id();
    $permissions->execute($id, $id);
    $insert = $dbh->prepare($cug, 'Single-page HTML (2.7 MB) Version', '', 2, 3, '/documentation/user_guide/html/en_US/CodeX_User_Guide.html', '');
    $insert->execute();
    $id = $dh->last_insert_id();
    $permissions->execute($id, $id);
    
    $insert = $dbh->prepare($en, 'Command-Line Interface', 'A comprehensive guide describing all the functions of the CodeX Command-Line Interface.', 1, 1, undef, undef);
    $insert->execute();
    $id = $dh->last_insert_id();
    $permissions->execute($id, $id);
    $cli = $id;
    $insert = $dbh->prepare($cli, 'PDF Version', '', -3, 3, '/documentation/cli/pdf/en_US/CodeX_CLI.pdf', '');
    $insert->execute();
    $id = $dh->last_insert_id();
    $permissions->execute($id, $id);
    $insert = $dbh->prepare($cli, 'Multi-page HTML Version', '', -2, 3, '/documentation/cli/html/en_US/index.html', '');
    $insert->execute();
    $id = $dh->last_insert_id();
    $permissions->execute($id, $id);
    $insert = $dbh->prepare($cli, 'Single-page HTML Version', '', 0, 3, '/documentation/cli/html/en_US/CodeX_CLI.html', '');
    $insert->execute();
    $id = $dh->last_insert_id();
    $permissions->execute($id, $id);
    
    $insert = $dbh->prepare(1, 'Documentation en fran�ais', '', 1, 1, undef, undef);
    $insert->execute();
    $id = $dh->last_insert_id();
    $permissions->execute($id, $id);
    $fr = $id;
    
    $insert = $dbh->prepare($fr, 'Guide de l\'Utilisateur CodeX', 'Un guide complet d�crivant tous les services de CodeX et comment les utiliser de mani�re optimale. Fournit �galement de nombreuses astuces et explications pour g�rer efficacement votre projet CodeX.', -1, 1, undef, undef);
    $insert->execute();
    $id = $dh->last_insert_id();
    $permissions->execute($id, $id);
    $cug = $id;
    $insert = $dbh->prepare($cug, 'Version PDF', '', -1, 3, '/documentation/user_guide/pdf/fr_FR/CodeX_User_Guide.pdf', '');
    $insert->execute();
    $id = $dh->last_insert_id();
    $permissions->execute($id, $id);
    $insert = $dbh->prepare($cug, 'Version HTML multi-pages', '', 1, 3, '/documentation/user_guide/html/fr_FR/index.html', '');
    $insert->execute();
    $id = $dh->last_insert_id();
    $permissions->execute($id, $id);
    $insert = $dbh->prepare($cug, 'Version HTML une page (4,2 Mo)', '', 2, 3, '/documentation/user_guide/html/fr_FR/CodeX_User_Guide.html', '');
    $insert->execute();
    $id = $dh->last_insert_id();
    $permissions->execute($id, $id);
    
    $insert = $dbh->prepare($fr, 'Interface de Commande en Ligne', 'Un guide complet d�crivant toutes les fonctions de l\'Interface de Commande en Ligne de CodeX.', 0, 1, undef, undef);
    $insert->execute();
    $id = $dh->last_insert_id();
    $permissions->execute($id, $id);
    $cli = $id;
    $insert = $dbh->prepare($cli, 'Version PDF', '', 3, 3, '/documentation/cli/pdf/fr_FR/CodeX_CLI.pdf', '');
    $insert->execute();
    $id = $dh->last_insert_id();
    $permissions->execute($id, $id);
    $insert = $dbh->prepare($cli, 'Version HTML multi-pages', '', 4, 3, '/documentation/cli/html/fr_FR/index.html', '');
    $insert->execute();
    $id = $dh->last_insert_id();
    $permissions->execute($id, $id);
    $insert = $dbh->prepare($cli, 'Version HTML une page', '', 5, 3, '/documentation/cli/html/fr_FR/CodeX_CLI.html', '');
    $insert->execute();
    $id = $dh->last_insert_id();
    $permissions->execute($id, $id);
} else {
    #Docman must be installed
    $CAT $INSTALL_DIR/plugins/docman/db/install.sql | $MYSQL -u codexadm codex --password=$codexadm_passwd # SH !
}

echo "End of main DB upgrade"
