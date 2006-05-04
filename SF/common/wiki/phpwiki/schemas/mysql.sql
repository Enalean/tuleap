-- $Id: mysql.sql 1422 2005-04-12 13:33:49Z guerin $

drop table if exists page;
CREATE TABLE page (
	id              INT NOT NULL AUTO_INCREMENT,
        pagename        VARCHAR(100) BINARY NOT NULL,
	hits            INT NOT NULL DEFAULT 0,
        pagedata        MEDIUMTEXT NOT NULL DEFAULT '',
        PRIMARY KEY (id),
	UNIQUE KEY (pagename)
);

drop table if exists version;
CREATE TABLE version (
	id              INT NOT NULL,
        version         INT NOT NULL,
	mtime           INT NOT NULL,
	minor_edit      TINYINT DEFAULT 0,
        content         MEDIUMTEXT NOT NULL DEFAULT '',
        versiondata     MEDIUMTEXT NOT NULL DEFAULT '',
        PRIMARY KEY (id,version),
	INDEX (mtime)
);

drop table if exists recent;
CREATE TABLE recent (
	id              INT NOT NULL,
	latestversion   INT,
	latestmajor     INT,
	latestminor     INT,
        PRIMARY KEY (id)
);

drop table if exists nonempty;
CREATE TABLE nonempty (
	id              INT NOT NULL,
	PRIMARY KEY (id)
);

drop table if exists link;
CREATE TABLE link (
	linkfrom        INT NOT NULL,
        linkto          INT NOT NULL,
	INDEX (linkfrom),
        INDEX (linkto)
);

drop table if exists session;
CREATE TABLE session (
    	sess_id 	CHAR(32) NOT NULL DEFAULT '',
    	sess_data 	BLOB NOT NULL,
    	sess_date 	INT UNSIGNED NOT NULL,
    	sess_ip 	CHAR(15) NOT NULL,
    	PRIMARY KEY (sess_id),
	INDEX (sess_date)
); -- TYPE=heap; -- if your Mysql supports it and you have enough RAM

-- upgrade from 1.3.7:
-- ALTER TABLE session ADD sess_ip CHAR(15) NOT NULL;
-- CREATE INDEX sess_date on session (sess_date);

-- Optional DB Auth and Prefs
-- For these tables below the default table prefix must be used 
-- in the DBAuthParam SQL statements also.

drop table if exists pref;
CREATE TABLE pref (
  	userid 	CHAR(48) BINARY NOT NULL UNIQUE,
  	prefs  	TEXT NULL DEFAULT '',
  	PRIMARY KEY (userid)
) TYPE=MyISAM;

-- better use the extra pref table where such users can be created easily 
-- without password.
drop table if exists user;
CREATE TABLE user (
  	userid 	CHAR(48) BINARY NOT NULL UNIQUE,
  	passwd 	CHAR(48) BINARY DEFAULT '',
--	prefs  	TEXT NULL DEFAULT '',
--	groupname CHAR(48) BINARY DEFAULT 'users',
  	PRIMARY KEY (userid)
) TYPE=MyISAM;

drop table if exists member;
CREATE TABLE member (
	userid    CHAR(48) BINARY NOT NULL,
   	groupname CHAR(48) BINARY NOT NULL DEFAULT 'users',
   	INDEX (userid),
   	INDEX (groupname)
) TYPE=MyISAM;

-- if you plan to use the wikilens theme
drop table if exists rating;
CREATE TABLE rating (
        dimension INT(4) NOT NULL,
        raterpage INT(11) NOT NULL,
        rateepage INT(11) NOT NULL,
        ratingvalue FLOAT NOT NULL,
        rateeversion INT(11) NOT NULL,
        tstamp TIMESTAMP(14) NOT NULL,
        PRIMARY KEY (dimension, raterpage, rateepage)
);
