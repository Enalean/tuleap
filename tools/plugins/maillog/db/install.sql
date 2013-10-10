--
-- Copyright © STMicroelectronics, 2008. All Rights Reserved.
--
-- Originally written by Manuel Vacelet, 2008
--
-- This file is a part of Codendi.
--
-- Codendi is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 2 of the License, or
-- (at your option) any later version.
--
-- Codendi is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with Codendi; if not, write to the Free Software
-- Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
--
-- Sql Install Script
--

DROP TABLE IF EXISTS plugin_maillog_header;
CREATE TABLE plugin_maillog_header (
  id_header INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  PRIMARY KEY(id_header),
  KEY idx_name (name (20))
);

DROP TABLE IF EXISTS plugin_maillog_message;
CREATE TABLE plugin_maillog_message (
  id_message INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  body TEXT NULL,
  html_body TEXT NULL,
  PRIMARY KEY(id_message)
);

DROP TABLE IF EXISTS plugin_maillog_messageheader;
CREATE TABLE plugin_maillog_messageheader (
  id_message INTEGER UNSIGNED NOT NULL,
  id_header INTEGER UNSIGNED NOT NULL,
  value TEXT NOT NULL,
  PRIMARY KEY(id_message, id_header)
);

INSERT INTO plugin_maillog_header (id_header, name) VALUES ('1','message-id');
INSERT INTO plugin_maillog_header (id_header, name) VALUES ('2','date');
INSERT INTO plugin_maillog_header (id_header, name) VALUES ('3','from');
INSERT INTO plugin_maillog_header (id_header, name) VALUES ('4','subject');
INSERT INTO plugin_maillog_header (id_header, name) VALUES ('5','to');
INSERT INTO plugin_maillog_header (id_header, name) VALUES ('6','cc');
INSERT INTO plugin_maillog_header (id_header, name) VALUES ('7','bcc');
