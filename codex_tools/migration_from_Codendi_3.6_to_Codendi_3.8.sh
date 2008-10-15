DROP TABLE intel_agreement;
DROP TABLE user_diary;
DROP TABLE user_diary_monitor;
DROP TABLE user_metric0;
DROP TABLE user_metric1;
DROP TABLE user_metric_tmp1_1;
DROP TABLE user_ratings;
DROP TABLE user_trust_metric;


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
	("xmpp.muc.history.type", "all");
# TODO : Modify openfire/conf/openfire.xml : 
# TODO : $xml->provider->auth->className update node to CodexJDBCAuth
# TODO : $xml->jdbcAuthProvider->addChild('codexUserSessionIdSQL', "SELECT session_hash FROM session WHERE session.user_id = (SELECT user_id FROM user WHERE user.user_name = ?)");
# copy jar file into openfire lib dir
$CP $INSTALL_DIR/plugins/IM/include/jabbex_api/installation/resources/codendi_auth.jar /opt/openfire/lib/.
