-- alter table plugin_forumml_message add column last_thread_update int unsigned not null default 0 after body;
CREATE TABLE plugin_forumml_attachment (
  id_attachment INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  id_message INTEGER UNSIGNED NOT NULL,
  file_name TEXT NOT NULL,
  file_type VARCHAR(80) NOT NULL,
  file_size INTEGER UNSIGNED NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  content_id varchar(255) not null default '',
  PRIMARY KEY(id_attachment),
  KEY idx_fk_id_message (id_message, content_id(10))
);

CREATE TABLE plugin_forumml_header (
  id_header INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  PRIMARY KEY(id_header),
  KEY idx_name (name (20))
);

CREATE TABLE plugin_forumml_message (
  id_message INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  id_list INTEGER UNSIGNED NOT NULL,
  id_parent INTEGER UNSIGNED NOT NULL,
  body TEXT NULL,
  last_thread_update INTEGER   UNSIGNED NOT NULL DEFAULT 0,
  msg_type varchar(30) not null default '',
  cached_html mediumtext default null,
  PRIMARY KEY(id_message),
  KEY idx_fk_id_list (id_list),
  KEY idx_fk_id_parent (id_parent)
);

CREATE TABLE plugin_forumml_messageheader (
  id_message INTEGER UNSIGNED NOT NULL,
  id_header INTEGER UNSIGNED NOT NULL,
  value TEXT NOT NULL,
  PRIMARY KEY(id_message, id_header)
);

INSERT INTO plugin_forumml_header (id_header, name) VALUES ('1','message-id');
INSERT INTO plugin_forumml_header (id_header, name) VALUES ('2','date');
INSERT INTO plugin_forumml_header (id_header, name) VALUES ('3','from');
INSERT INTO plugin_forumml_header (id_header, name) VALUES ('4','subject');
INSERT INTO plugin_forumml_header (id_header, name) VALUES ('5','return-path');
INSERT INTO plugin_forumml_header (id_header, name) VALUES ('6','delivered-to');
INSERT INTO plugin_forumml_header (id_header, name) VALUES ('7','to');
INSERT INTO plugin_forumml_header (id_header, name) VALUES ('8','in-reply-to');
INSERT INTO plugin_forumml_header (id_header, name) VALUES ('9','references');
INSERT INTO plugin_forumml_header (id_header, name) VALUES ('10','x-mailer');
INSERT INTO plugin_forumml_header (id_header, name) VALUES ('11','mime-version');
INSERT INTO plugin_forumml_header (id_header, name) VALUES ('12','content-type');
INSERT INTO plugin_forumml_header (id_header, name) VALUES ('13','content-transfer-encoding');
INSERT INTO plugin_forumml_header (id_header, name) VALUES ('14','sender');
INSERT INTO plugin_forumml_header (id_header, name) VALUES ('15','errors-to');
INSERT INTO plugin_forumml_header (id_header, name) VALUES ('16','x-beenthere');
INSERT INTO plugin_forumml_header (id_header, name) VALUES ('17','x-mailman-version');
INSERT INTO plugin_forumml_header (id_header, name) VALUES ('18','precedence');
INSERT INTO plugin_forumml_header (id_header, name) VALUES ('19','list-help');
INSERT INTO plugin_forumml_header (id_header, name) VALUES ('20','list-post');
INSERT INTO plugin_forumml_header (id_header, name) VALUES ('21','list-subscribe');
INSERT INTO plugin_forumml_header (id_header, name) VALUES ('22','list-id');
INSERT INTO plugin_forumml_header (id_header, name) VALUES ('23','list-unsubscribe');
INSERT INTO plugin_forumml_header (id_header, name) VALUES ('24','list-archive');
INSERT INTO plugin_forumml_header (id_header, name) VALUES ('25','x-original-to');
INSERT INTO plugin_forumml_header (id_header, name) VALUES ('26','x-priority');
INSERT INTO plugin_forumml_header (id_header, name) VALUES ('27','x-msmail-priority');
INSERT INTO plugin_forumml_header (id_header, name) VALUES ('28','importance');
INSERT INTO plugin_forumml_header (id_header, name) VALUES ('29','x-mimeole');
INSERT INTO plugin_forumml_header (id_header, name) VALUES ('30','reply-to');
INSERT INTO plugin_forumml_header (id_header, name) VALUES ('31','x-list-received-date');
INSERT INTO plugin_forumml_header (id_header, name) VALUES ('32','user-agent');
INSERT INTO plugin_forumml_header (id_header, name) VALUES ('33','x-mailman-approved-at');
INSERT INTO plugin_forumml_header (id_header, name) VALUES ('34','cc');
INSERT INTO plugin_forumml_header (id_header, name) VALUES ('35','x-mozilla-status');
INSERT INTO plugin_forumml_header (id_header, name) VALUES ('36','x-mozilla-status2');
INSERT INTO plugin_forumml_header (id_header, name) VALUES ('37','thread-index');
INSERT INTO plugin_forumml_header (id_header, name) VALUES ('38','x-accept-language');
INSERT INTO plugin_forumml_header (id_header, name) VALUES ('39','keywords');
INSERT INTO plugin_forumml_header (id_header, name) VALUES ('40','organization');
INSERT INTO plugin_forumml_header (id_header, name) VALUES ('41','x-reply-to');
INSERT INTO plugin_forumml_header (id_header, name) VALUES ('42','x-enigmail-version');
INSERT INTO plugin_forumml_header (id_header, name) VALUES ('43','x-enigmail-supports');
INSERT INTO plugin_forumml_header (id_header, name) VALUES ('44','x-ms-tnef-correlator');
INSERT INTO plugin_forumml_header (id_header, name) VALUES ('45','x-pgp-universal');
