



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

# Windows password no longer needed
$CAT <<EOF | $MYSQL $pass_opt codex
ALTER TABLE user DROP COLUMN windows_pw;
EOF



# artifact permissions
ALTER TABLE artifact ADD COLUMN use_artifact_permissions tinyint(1) NOT NULL DEFAULT '0' AFTER group_artifact_id;

INSERT INTO permissions_values (permission_type,ugroup_id,is_default) VALUES ('TRACKER_ARTIFACT_ACCESS',1,1);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ARTIFACT_ACCESS',2);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ARTIFACT_ACCESS',3);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ARTIFACT_ACCESS',4);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ARTIFACT_ACCESS',15);

# add the field severity on all reports
UPDATE artifact_report_field SET show_on_result = 1 WHERE field_name = 'severity';

# fix references > services
UPDATE reference
SET service_short_name = 'tracker'
WHERE scope = 'P'
AND (service_short_name = '' OR service_short_name IS NULL)
AND link LIKE '/tracker/%func=detail%'


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


#custom themes
=> no more images
=> refactoring in common/layout instead of www/include

#TODO remove reserved names javascript
