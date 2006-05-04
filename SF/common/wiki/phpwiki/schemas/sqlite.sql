-- http://www.hezmatt.org/~mpalmer/sqlite-phpwiki/sqlite.sql

-- $Id: sqlite.sql 1422 2005-04-12 13:33:49Z guerin $

CREATE TABLE page (
	id              INT NOT NULL,
	pagename        VARCHAR(100) NOT NULL,
	hits            INT NOT NULL DEFAULT 0,
	pagedata        MEDIUMTEXT NOT NULL DEFAULT '',
	PRIMARY KEY (id)
);

CREATE UNIQUE INDEX page_index ON page (pagename);

CREATE TABLE version (
	id              INT NOT NULL,
	version         INT NOT NULL,
	mtime           INT NOT NULL,
	minor_edit      TINYINT DEFAULT 0,
	content         MEDIUMTEXT NOT NULL DEFAULT '',
	versiondata     MEDIUMTEXT NOT NULL DEFAULT '',
	PRIMARY KEY (id,version)
);

CREATE INDEX version_index ON version (mtime);

CREATE TABLE recent (
	id              INT NOT NULL,
	latestversion   INT,
	latestmajor     INT,
	latestminor     INT,
	PRIMARY KEY (id)
);

CREATE TABLE nonempty (
	id              INT NOT NULL,
	PRIMARY KEY (id)
);

CREATE TABLE link (
	linkfrom        INT NOT NULL,
	linkto          INT NOT NULL
);

CREATE INDEX linkfrom_index ON link (linkfrom);
CREATE INDEX linkto_index ON link (linkto);

CREATE TABLE session (
	sess_id   char(32) not null default '',
	sess_data blob not null,
	sess_date INT UNSIGNED NOT NULL,
	sess_ip   char(15) not null,
	PRIMARY KEY (sess_id)
);

CREATE INDEX sessdate_index ON session (sess_date);
CREATE INDEX sessip_index ON session (sess_ip);

-- Optional DB Auth and Prefs
-- For these tables below the default table prefix must be used 
-- in the DBAuthParam SQL statements also.

CREATE TABLE pref (
  	userid 	CHAR(48) BINARY NOT NULL UNIQUE,
  	prefs  	TEXT NULL DEFAULT '',
  	PRIMARY KEY (userid)
);
