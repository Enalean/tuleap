



dbauth_passwd="a"; dbauth_passwd2="b";
while [ "$dbauth_passwd" != "$dbauth_passwd2" ]; do
    read -s -p "Password for DB Authentication user: " dbauth_passwd
    echo
    read -s -p "Retype password for DB Authentication user: " dbauth_passwd2
    echo
done

###############################################################################
echo "Updating Packages"


# MUST reinstall: munin RPM (Codendi specific, with MySQL auth), viewVC (bug fixed)


###############################################################################
echo "Updating local.inc"

# Remove $sys_win_domain XXX ???


# dbauthuser and password
$GREP -q ^\$sys_dbauth_user  $ETC_DIR/conf/local.inc
if [ $? -ne 0 ]; then
  # Remove end PHP marker
  substitute '/etc/codex/conf/local.inc' '\?\>' ''

  $CAT <<EOF >> /etc/codex/conf/local.inc
// DB user for http authentication (must have access to user/group/user_group tables)
\$sys_dbauth_user = "dbauthuser";
\$sys_dbauth_passwd = '$dbauth_passwd';
?>
EOF
fi

# sys_pending_account_lifetime
$GREP -q ^\$sys_pending_account_lifetime  $ETC_DIR/conf/local.inc
if [ $? -ne 0 ]; then
  # Remove end PHP marker
  substitute '/etc/codex/conf/local.inc' '\?\>' ''

  $CAT <<EOF >> /etc/codex/conf/local.inc
// Duration before deleting pending accounts which have not been activated
// (in days)
// Default value is 60 days
\$sys_pending_account_lifetime = 60;
?>
EOF
fi

# unix_uid_add
$GREP -q ^\$unix_uid_add  $ETC_DIR/conf/local.inc
if [ $? -ne 0 ]; then
  # Remove end PHP marker
  substitute '/etc/codex/conf/local.inc' '\?\>' ''

  $CAT <<EOF >> /etc/codex/conf/local.inc

// How much to add to the database unix_uid to get the actual unix uid
\$unix_uid_add  = "20000";
?>
EOF
fi

# unix_gid_add
$GREP -q ^\$unix_gid_add  $ETC_DIR/conf/local.inc
if [ $? -ne 0 ]; then
  # Remove end PHP marker
  substitute '/etc/codex/conf/local.inc' '\?\>' ''

  $CAT <<EOF >> /etc/codex/conf/local.inc

// How much to add to the database group_id to get the unix gid
\$unix_gid_add  = "1000";
?>
EOF
fi


###############################################################################
# HTTP-based authentication
echo "Moving /etc/httpd/conf/htpasswd to /etc/httpd/conf/htpasswd.old"
echo "This file is no longer needed (now using MySQL based authentication with mod_auth_mysql)"

if [ -f "/etc/httpd/conf/htpasswd" ]; then
  $MV /etc/httpd/conf/htpasswd /etc/httpd/conf/htpasswd.codendi3.6
fi

echo "Update munin.conf accordingly"
# replace string patterns in munin.conf (for MySQL authentication)
substitute '/etc/httpd/conf.d/munin.conf' '%sys_dbauth_passwd%' "$dbauth_passwd" 


###############################################################################
echo "Updating database"

# See if MySQL root account is password protected
mysqlshow 2>&1 | grep password
while [ $? -eq 0 ]; do
    read -s -p "Existing CodeX DB is password protected. What is the Mysql root password?: " old_passwd
    echo
    mysqlshow --password=$old_passwd 2>&1 | grep password
done
[ "X$old_passwd" != "X" ] && pass_opt="--password=$old_passwd"




# Create dbauthuser, needed for MySQL-based authentication for HTTP (SVN) and Openfire
$CAT <<EOF | $MYSQL -u root mysql $pass_opt
GRANT SELECT ON codex.user to dbauthuser@localhost identified by '$dbauth_passwd';
GRANT SELECT ON codex.groups to dbauthuser@localhost;
GRANT SELECT ON codex.user_group to dbauthuser@localhost;
GRANT SELECT ON codex.session to dbauthuser@localhost;
FLUSH PRIVILEGES;
EOF


# Add support for > 2GB files in DB (FRS and Wiki)
$CAT <<EOF | $MYSQL -u root mysql $pass_opt
ALTER TABLE frs_file CHANGE file_size file_size BIGINT NOT NULL DEFAULT '0';
ALTER TABLE wiki_attachment_revision CHANGE size size BIGINT NOT NULL;
EOF


# Remove useless tables
$CAT <<EOF | $MYSQL $pass_opt codex
DROP TABLE intel_agreement;
DROP TABLE user_diary;
DROP TABLE user_diary_monitor;
DROP TABLE user_metric0;
DROP TABLE user_metric1;
DROP TABLE user_metric_tmp1_1;
DROP TABLE user_ratings;
DROP TABLE user_trust_metric;
EOF


# account approver
$CAT <<EOF | $MYSQL $pass_opt codex
ALTER TABLE user ADD COLUMN approved_by int(11) NOT NULL default '0' AFTER add_date;
EOF

echo "Please note that Windows shares (with Samba) are no longer supported"

# Windows password no longer needed
$CAT <<EOF | $MYSQL $pass_opt codex
ALTER TABLE user DROP COLUMN windows_pw;
EOF


# 
# Table structure for System Events
# 
# type        : one of "PROJECT_CREATE", "PROJECT_DELETE", "USER_CREATE", etc.
# parameters  : event parameters (group_id, etc.) depending on event type
# priority    : event priority from 3 (high prio) to 1 (low prio)
# status      : event status: 'NEW' = nothing done yet, 'RUNNING' = event is being processed, 
#               'DONE', 'ERROR', 'WARNING' = event processed successfully, with error, or with a warning message respectively.
# create_date : date when the event was created in the DB
# process_date: date when event processing started
# end_date    : date when processing finished
# log         : log message after processing (useful for e.g. error messages or warnings).

DROP TABLE IF EXISTS system_event;
CREATE TABLE IF NOT EXISTS system_event (
  id INT(11) unsigned NOT NULL AUTO_INCREMENT, 
  type VARCHAR(255) NOT NULL default '',
  parameters TEXT,
  priority TINYINT(1) NOT NULL default '0',
  status  ENUM( 'NEW', 'RUNNING', 'DONE', 'ERROR', 'WARNING' ) NOT NULL DEFAULT 'NEW',
  create_date DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL,
  process_date DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL,
  end_date DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL,
  log TEXT,
  PRIMARY KEY (id)
) TYPE=MyISAM;



# artifact permissions
ALTER TABLE artifact ADD COLUMN use_artifact_permissions tinyint(1) NOT NULL DEFAULT '0' AFTER group_artifact_id;

INSERT INTO permissions_values (permission_type,ugroup_id,is_default) VALUES ('TRACKER_ARTIFACT_ACCESS',1,1);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ARTIFACT_ACCESS',2);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ARTIFACT_ACCESS',3);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ARTIFACT_ACCESS',4);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ARTIFACT_ACCESS',15);

# add the field severity on all reports
UPDATE artifact_report_field SET show_on_result = 1 WHERE field_name = 'severity';

# Mandatory reference in SVN commit message
ALTER TABLE groups ADD svn_mandatory_ref TINYINT NOT NULL DEFAULT '0' AFTER svn_tracker;

# Cross references : add a new field 'nature'
ALTER TABLE reference ADD nature VARCHAR( 64 ) NOT NULL;
# Set the nature for existing references
UPDATE reference
SET nature = 'artifact'
WHERE (keyword = 'art' OR
       keyword = 'artifact' OR
       keyword = 'bug' OR
       keyword = 'patch' OR
       keyword = 'slmbug' OR
       keyword = 'sr' OR
       keyword = 'story' OR
       keyword = 'task' OR
      );
UPDATE reference
SET nature = 'document'
WHERE (keyword = 'doc' OR
       keyword = 'document' OR
       keyword = 'dossier' OR
       keyword = 'folder'
      );
UPDATE reference
SET nature = 'cvs_commit'
WHERE (keyword = 'cvs' OR
       keyword = 'commit'
      );
UPDATE reference
SET nature = 'svn_revision'
WHERE (keyword = 'svn' OR
       keyword = 'revision' OR
       keyword = 'rev'
      );
UPDATE reference
SET nature = 'file'
WHERE (keyword = 'file'
      );
UPDATE reference
SET nature = 'release'
WHERE (keyword = 'release'
      );
UPDATE reference
SET nature = 'forum'
WHERE (keyword = 'forum'
      );
UPDATE reference
SET nature = 'forum_message'
WHERE (keyword = 'msg'
      );
UPDATE reference
SET nature = 'news'
WHERE (keyword = 'news'
      );
UPDATE reference
SET nature = 'snippet'
WHERE (keyword = 'snippet'
      );
UPDATE reference
SET nature = 'wiki_page'
WHERE (keyword = 'wiki'
      );
UPDATE reference
SET nature = 'other'
WHERE (nature = '' OR
       nature IS NULL);

UPDATE reference
SET service_short_name = 'tracker'
WHERE (nature = '' OR nature IS NULL);

# cross-references change the type of column to handle wiki references (not int)
ALTER TABLE cross_references CHANGE source_id source_id VARCHAR( 128 ) NOT NULL DEFAULT '0';
ALTER TABLE cross_references CHANGE target_id target_id VARCHAR( 128 ) NOT NULL DEFAULT '0';
# cross references : add two fields
ALTER TABLE cross_references ADD source_keyword VARCHAR( 32 ) NOT NULL AFTER source_type;
ALTER TABLE cross_references ADD target_keyword VARCHAR( 32 ) NOT NULL AFTER target_type;
# change type of existing cross references from 'revision_svn' to 'svn_revision'
UPDATE cross_references SET source_type = 'svn_revision' WHERE source_type LIKE 'revision_svn';
UPDATE cross_references SET target_type = 'svn_revision' WHERE target_type LIKE 'revision_svn';
# set keywords
UPDATE cross_references SET source_keyword = 'art' WHERE source_type LIKE 'artifact';
UPDATE cross_references SET source_keyword = 'doc' WHERE source_type LIKE 'document';
UPDATE cross_references SET source_keyword = 'cvs' WHERE source_type LIKE 'cvs_commit';
UPDATE cross_references SET source_keyword = 'svn' WHERE source_type LIKE 'svn_revision';
UPDATE cross_references SET source_keyword = 'file' WHERE source_type LIKE 'file';
UPDATE cross_references SET source_keyword = 'release' WHERE source_type LIKE 'release';
UPDATE cross_references SET source_keyword = 'forum' WHERE source_type LIKE 'forum';
UPDATE cross_references SET source_keyword = 'msg' WHERE source_type LIKE 'forum_message';
UPDATE cross_references SET source_keyword = 'news' WHERE source_type LIKE 'news';
UPDATE cross_references SET source_keyword = 'snippet' WHERE source_type LIKE 'snippet';
UPDATE cross_references SET source_keyword = 'wiki' WHERE source_type LIKE 'wiki_page';
UPDATE cross_references SET target_keyword = 'art' WHERE target_type LIKE 'artifact';
UPDATE cross_references SET target_keyword = 'doc' WHERE target_type LIKE 'document';
UPDATE cross_references SET target_keyword = 'cvs' WHERE target_type LIKE 'cvs_commit';
UPDATE cross_references SET target_keyword = 'svn' WHERE target_type LIKE 'svn_revision';
UPDATE cross_references SET target_keyword = 'file' WHERE target_type LIKE 'file';
UPDATE cross_references SET target_keyword = 'release' WHERE target_type LIKE 'release';
UPDATE cross_references SET target_keyword = 'forum' WHERE target_type LIKE 'forum';
UPDATE cross_references SET target_keyword = 'msg' WHERE target_type LIKE 'forum_message';
UPDATE cross_references SET target_keyword = 'news' WHERE target_type LIKE 'news';
UPDATE cross_references SET target_keyword = 'snippet' WHERE target_type LIKE 'snippet';
UPDATE cross_references SET target_keyword = 'wiki' WHERE target_type LIKE 'wiki_page';


# fix references > services
UPDATE reference
SET service_short_name = 'tracker'
WHERE scope = 'P'
AND (service_short_name = '' OR service_short_name IS NULL)
AND link LIKE '/tracker/%func=detail%';

# add new reference for IM chat
INSERT INTO reference SET 
    keyword='chat', 
    description='plugin_im:reference_chat_desc_key', 
    link='/plugins/IM/?group_id=$group_id&action=viewchatlog&chat_log=$1', 
    scope='S', 
    service_short_name='IM',
    nature='im_chat';
INSERT INTO reference_group (reference_id, group_id, is_active)
SELECT last_insert_id, group_id, 1
FROM (SELECT LAST_INSERT_ID() as last_insert_id) AS R, groups; 

# IM plugin
# TODO : stop openfire service ($SERVICE openfire stop)

# Add IM service
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES ( 100 , 'plugin_im:service_lbl_key' , 'plugin_im:service_desc_key' , 'IM', '/plugins/IM/?group_id=$group_id', 1 , 1 , 'system',  210 );
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES ( 1   , 'plugin_im:service_lbl_key' , 'plugin_im:service_desc_key' , 'IM', '/plugins/IM/?group_id=1', 1 , 0 , 'system',  210 );
# Create IM service for all other projects (but disabled)
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank)
SELECT DISTINCT group_id , 'plugin_im:service_lbl_key' , 'plugin_im:service_desc_key' , 'IM', CONCAT('/plugins/IM/?group_id=', group_id), 1 , 0 , 'system',  210
FROM service
WHERE group_id NOT IN (SELECT group_id
    FROM service
    WHERE short_name
    LIKE 'IM');

# IM plugin : grant privileges for openfireadm on session table (required for webmuc)
GRANT SELECT ON codex.session to openfireadm@localhost;
FLUSH PRIVILEGES;
# IM openfire configuration
# TODO : create database_im.inc in /etc/codex/plugins/IM/etc/
# Specific configuration for webmuc
INSERT INTO openfire.jiveProperty (name, propValue) VALUES 
	("httpbind.enabled", "true"),
	("httpbind.port.plain", "7070"),
	("xmpp.httpbind.client.requests.polling", "0"),
	("xmpp.httpbind.client.requests.wait", "10"),
	("xmpp.httpbind.scriptSyntax.enabled", "true"),
	("xmpp.muc.history.type", "all"),
	("conversation.idleTime", "10"),
    ("conversation.maxTime, "240"),
    ("conversation.messageArchiving, "false"),
    ("conversation.metadataArchiving, "true"),
    ("conversation.roomArchiving, "true");
# TODO : Modify openfire/conf/openfire.xml : 
# TODO : $xml->provider->auth->className update node to CodexJDBCAuth
# TODO : $xml->jdbcAuthProvider->addChild('codexUserSessionIdSQL', "SELECT session_hash FROM session WHERE session.user_id = (SELECT user_id FROM user WHERE user.user_name = ?)");
# copy jar file into openfire lib dir
$CP $INSTALL_DIR/plugins/IM/include/jabbex_api/installation/resources/codendi_auth.jar /opt/openfire/lib/.
# TODO : update httpd.conf and codex_aliases.conf (see rev #10208 for details)
# TODO : instal monitoring plugin (copy plugin jar in openfire plugin dir)


#
# CI with Hudson plugin
#
CREATE TABLE plugin_hudson_job (
  job_id int(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT ,
  group_id int(11) NOT NULL ,
  job_url varchar(255) NOT NULL ,
  name varchar(128) NOT NULL ,
  use_svn_trigger tinyint(4) NOT NULL default 0 ,
  use_cvs_trigger tinyint(4) NOT NULL default 0 ,
  token varchar(128) NOT NULL
);
CREATE TABLE plugin_hudson_widget (
  id int(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT ,
  widget_name varchar(64) NOT NULL ,
  owner_id int(11) UNSIGNED NOT NULL ,
  owner_type varchar(1) NOT NULL ,
  job_id int(11) NOT NULL
);
# Add hudson service
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES ( 100 , 'plugin_hudson:service_lbl_key' , 'plugin_hudson:service_desc_key' , 'hudson', '/plugins/hudson/?group_id=$group_id', 1 , 1 , 'system',  220 );
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES ( 1   , 'plugin_hudson:service_lbl_key' , 'plugin_hudson:service_desc_key' , 'hudson', '/plugins/hudson/?group_id=1', 1 , 0 , 'system',  220 );
# Create hudson service for all other projects (but disabled)
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank)
SELECT DISTINCT group_id , 'plugin_hudson:service_lbl_key' , 'plugin_hudson:service_desc_key' , 'hudson', CONCAT('/plugins/hudson/?group_id=', group_id), 1 , 0 , 'system',  220
FROM service
WHERE group_id NOT IN (SELECT group_id
    FROM service
    WHERE short_name
    LIKE 'hudson');



TODO : Déplacer le script de debug dans Layout.class.php


ALTER TABLE user CHANGE language_id language_id VARCHAR( 17 ) NOT NULL DEFAULT 'en_US' 
UPDATE user 
SET language_id = 'fr_FR'
WHERE language_id = 2;

UPDATE user 
SET language_id = 'en_US'
WHERE language_id != 'fr_FR';

ALTER TABLE wiki_group_list CHANGE language_id language_id VARCHAR( 17 ) NOT NULL DEFAULT 'en_US'
UPDATE wiki_group_list 
SET language_id = 'fr_FR'
WHERE language_id = 2;

UPDATE wiki_group_list 
SET language_id = 'en_US'
WHERE language_id != 'fr_FR';

DROP TABLE supported_languages;


# Add common stylesheet in custom themes


# Reorder report fields for prepareRanking usage
SET @counter = 0;
SET @previous = NULL;
UPDATE artifact_report_field 
        INNER JOIN (SELECT @counter := IF(@previous = report_id, @counter + 1, 1) AS new_rank, 
                           @previous := report_id, 
                           artifact_report_field.* 
                    FROM artifact_report_field 
                    ORDER BY report_id, place_result, field_name
        ) as R1 USING(report_id,field_name)
SET artifact_report_field.place_result = R1.new_rank;
SET @counter = 0;
SET @previous = NULL;
UPDATE artifact_report_field 
        INNER JOIN (SELECT @counter := IF(@previous = report_id, @counter + 1, 1) AS new_rank, 
                           @previous := report_id, 
                           artifact_report_field.* 
                    FROM artifact_report_field 
                    ORDER BY report_id, place_query, field_name
        ) as R1 USING(report_id,field_name)
SET artifact_report_field.place_query = R1.new_rank;


# Add 3 new widgets on project summary page
INSERT INTO layouts_contents(owner_id, owner_type, layout_id, column_id, name, rank)
SELECT group_id, 'g', 1, 1, 'projectclassification', R.rank
FROM groups 
     INNER JOIN (SELECT owner_id, owner_type, layout_id, column_id, MIN(rank) - 1 as rank 
                 FROM layouts_contents
                 WHERE owner_type = 'g' 
                   AND layout_id  = 1
                   AND column_id  = 1
                 GROUP BY owner_id, owner_type, layout_id, column_id) AS R
           ON (owner_id = group_id);

INSERT INTO layouts_contents(owner_id, owner_type, layout_id, column_id, name, rank)
SELECT group_id, 'g', 1, 1, 'projectdescription', R.rank
FROM groups
     INNER JOIN (SELECT owner_id, owner_type, layout_id, column_id, MIN(rank) - 1 as rank 
                 FROM layouts_contents
                 WHERE owner_type = 'g' 
                   AND layout_id  = 1
                   AND column_id  = 1
                 GROUP BY owner_id, owner_type, layout_id, column_id) AS R
           ON (owner_id = group_id);

INSERT INTO layouts_contents(owner_id, owner_type, layout_id, column_id, name, rank)
SELECT group_id, 'g', 1, 2, 'projectmembers', R.rank
FROM groups
     INNER JOIN (SELECT owner_id, owner_type, layout_id, column_id, MIN(rank) - 1 as rank 
                 FROM layouts_contents
                 WHERE owner_type = 'g' 
                   AND layout_id  = 1
                   AND column_id  = 2
                 GROUP BY owner_id, owner_type, layout_id, column_id) AS R
           ON (owner_id = group_id)
WHERE hide_members = 0;

DELETE FROM layouts_contents WHERE name IN ('projectclassification', 'projectdescription', 'projectmembers');

# Delete hide_members column (not needed anymore, please do it after previous request)
ALTER TABLE groups DROP hide_members;

# Add cvs_is_private 
ALTER TABLE groups ADD cvs_is_private TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER cvs_preamble ;
TODO: migrate .CODEX_PRIVATE

#Layouts for dashboard
INSERT INTO layouts(id, name, description, scope) VALUES
(2, '3 columns', 'Simple layout made of 3 columns', 'S'),
(3, 'Left', 'Simple layout made of a main column and a small, left sided, column', 'S'),
(4, 'Right', 'Simple layout made of a main column and a small, right sided, column', 'S');

INSERT INTO layouts_rows(id, layout_id, rank) VALUES
(2, 2, 0),
(3, 3, 0),
(4, 4, 0);

INSERT INTO layouts_rows_columns(id, layout_row_id, width) VALUES
(3, 2, 33),
(4, 2, 33),
(5, 2, 33),
(6, 3, 33),
(7, 3, 66),
(8, 4, 66),
(9, 4, 33);


#Upgrade docman
ALTER TABLE plugin_docman_approval CHANGE COLUMN version_id version_id INT(11) UNSIGNED UNSIGNED NULL DEFAULT NULL;
ALTER TABLE plugin_docman_approval CHANGE COLUMN wiki_version_id wiki_version_id INT(11) UNSIGNED UNSIGNED NULL DEFAULT NULL;

#perfs
ALTER TABLE artifact_field_value 
    DROP INDEX idx_field_id, 
    DROP INDEX idx_artifact_id, 
    DROP INDEX idx_art_field_id, 
    DROP INDEX valueInt;

ALTER TABLE artifact_field_value
    ADD INDEX idx_valueInt(artifact_id, field_id, valueInt);

# CVS is private
# TODO : private projects 
$CAT <<EOF | $PHP
<?php
require_once('/etc/codex/conf/local.inc');
require_once('/etc/codex/conf/database.inc');
mysql_connect($sys_dbhost, $sys_dbuser, $sys_dbpasswd) or die('ERROR: Unable to connect to the database. Aborting.');
mysql_select_db($sys_dbname) or die('ERROR: Unable to select the database. Aborting.');

$groups = array();
foreach(glob($GLOBALS['cvs_prefix'] .'/*/.CODEX_PRIVATE') as $g) {
    $groups[] = "'". mysql_real_escape_string(preg_replace('|^.*/([^/]*)/.CODEX_PRIVATE|', '$1', $g)) ."'";
    unlink($g);
}
if (count($groups)) {
    echo 'The following projects want to set their cvs repository private: '. implode(', ', $groups). PHP_EOL;
    $sql = "UPDATE groups 
            SET cvs_is_private 
            WHERE unix_group_name IN (". implode(', ', $groups) .")";
    mysql_query($sql) or die("ERROR: While executing the sql statement: ". mysql_error() ." -> ".$sql);
    echo 'done.'. PHP_EOL;
} else {
    echo 'No projects want to set their cvs repository private.'. PHP_EOL;
}
?>


#custom themes
=> no more images
=> refactoring in common/layout instead of www/include

#TODO Clean-up CodendiBlack (fix blue labels on IE, ...)
#TODO remove reserved names javascript

#
# TODO: add these lines to /etc/my.cnf under [mysqld]
#

# TODO : CREATE / UPDATE the pre-commit hook for every existing project.

#
# TODO: copy /src/utils/svn/codendi_svn_pre_commit.php into /usr/lib/codex/bin/codendi_svn_pre_commit.php
# TODO: copy /src/utils/svn/commit-email.pl into /usr/lib/codex/bin/commit-email.pl
# TODO: copy /src/utils/cvs1/log_accum into /usr/lib/codex/bin/log_accum
#

  # Skip logging openfire db (for instant messaging)
  # The 'monitor' openrfire plugin creates large codex-bin files
  # Comment this line if you prefer to be safer.
  set-variable  = binlog-ignore-db=openfire

#


