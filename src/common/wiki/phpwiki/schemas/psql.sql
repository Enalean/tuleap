-- $Id$

\set QUIET


--================================================================
-- Prefix for table names.
--
-- You should set this to the same value you specify for
-- $DBParams['prefix'] in index.php.

\set prefix 	''

--================================================================
-- Which postgres user gets access to the tables?
--
-- You should set this to the name of the postgres
-- user who will be accessing the tables.
--
-- Commonly, connections from php are made under
-- the user name of 'nobody', 'apache' or 'www'.

\set httpd_user	'rurban'

--================================================================
--
-- Don't modify below this point unless you know what you are doing.
--
--================================================================

\set qprefix '\'' :prefix '\''
\set qhttp_user '\'' :httpd_user '\''
\echo Initializing PhpWiki tables with:
\echo '       prefix = ' :qprefix
\echo '   httpd_user = ' :qhttp_user
\echo
\echo 'Expect some \'Relation \'*\' does not exists\' errors unless you are'
\echo 'overwriting existing tables.'

\set page_tbl		:prefix 'page'
\set page_id		:prefix 'page_id'
\set page_nm		:prefix 'page_nm'

\set version_tbl	:prefix 'version'
\set vers_id		:prefix 'vers_id'
\set vers_mtime		:prefix 'vers_mtime'

\set recent_tbl		:prefix 'recent'
\set recent_id		:prefix 'recent_id'

\set nonempty_tbl	:prefix 'nonempty'
\set nonmt_id		:prefix 'nonmt_id'

\set link_tbl		:prefix 'link'
\set link_from		:prefix 'link_from'
\set link_to		:prefix 'link_to'

\set session_tbl	:prefix 'session'
\set sess_id		:prefix 'sess_id'
\set sess_date		:prefix 'sess_date'
\set sess_ip		:prefix 'sess_ip'

\set pref_tbl		:prefix 'pref'
\set pref_id		:prefix 'pref_id'

\echo Dropping :page_tbl
DROP TABLE :page_tbl;
\echo Creating :page_tbl
CREATE TABLE :page_tbl (
	id		INT NOT NULL,
        pagename	VARCHAR(100) NOT NULL,
	hits		INT NOT NULL DEFAULT 0,
        pagedata	TEXT NOT NULL DEFAULT ''
);
CREATE UNIQUE INDEX :page_id ON :page_tbl (id);
CREATE UNIQUE INDEX :page_nm ON :page_tbl (pagename);

\echo Dropping :version_tbl
DROP TABLE :version_tbl;
\echo Creating :version_tbl
CREATE TABLE :version_tbl (
	id		INT NOT NULL,
        version		INT NOT NULL,
	mtime		INT NOT NULL,
--FIXME: should use boolean, but that returns 't' or 'f'. not 0 or 1. 
	minor_edit	INT2 DEFAULT 0,
        content		TEXT NOT NULL DEFAULT '',
        versiondata	TEXT NOT NULL DEFAULT ''
);
CREATE UNIQUE INDEX :vers_id ON :version_tbl (id,version);
CREATE INDEX :vers_mtime ON :version_tbl (mtime);

\echo Dropping :recent_tbl
DROP TABLE :recent_tbl;
\echo Creating :recent_tbl
CREATE TABLE :recent_tbl (
	id		INT NOT NULL,
	latestversion	INT,
	latestmajor	INT,
	latestminor	INT
);
CREATE UNIQUE INDEX :recent_id ON :recent_tbl (id);


\echo Dropping :nonempty_tbl
DROP TABLE :nonempty_tbl;
\echo Creating :nonempty_tbl
CREATE TABLE :nonempty_tbl (
	id		INT NOT NULL
);
CREATE UNIQUE INDEX :nonmt_id
	ON :nonempty_tbl (id);

\echo Dropping :link_tbl
DROP TABLE :link_tbl;
\echo Creating :link_tbl
CREATE TABLE :link_tbl (
        linkfrom	INT NOT NULL,
        linkto		INT NOT NULL
);
CREATE INDEX :link_from ON :link_tbl (linkfrom);
CREATE INDEX :link_to   ON :link_tbl (linkto);

\echo Dropping :session_tbl
DROP TABLE :session_tbl;
\echo Creating :session_tbl
CREATE TABLE :session_tbl (
	sess_id 	CHAR(32) NOT NULL DEFAULT '',
    	sess_data 	TEXT NOT NULL,
    	sess_date 	INT,
    	sess_ip 	CHAR(15) NOT NULL
);
CREATE UNIQUE INDEX :sess_id ON :session_tbl (sess_id);
CREATE INDEX :sess_date ON :session_tbl (sess_date);
CREATE INDEX :sess_ip   ON :session_tbl (sess_ip);

-- Optional DB Auth and Prefs
-- For these tables below the default table prefix must be used 
-- in the DBAuthParam SQL statements also.

\echo Dropping :pref_tbl
DROP TABLE :pref_tbl;
\echo Creating :pref_tbl
CREATE TABLE :pref_tbl (
  	userid 	CHAR(48) NOT NULL,
  	prefs  	TEXT NULL DEFAULT ''
);
CREATE UNIQUE INDEX :pref_id ON :pref_tbl (userid);

GRANT ALL ON :page_tbl		TO :httpd_user;
GRANT ALL ON :version_tbl	TO :httpd_user;
GRANT ALL ON :recent_tbl	TO :httpd_user;
GRANT ALL ON :nonempty_tbl	TO :httpd_user;
GRANT ALL ON :link_tbl		TO :httpd_user;
GRANT ALL ON :session_tbl	TO :httpd_user;
GRANT ALL ON :pref_tbl		TO :httpd_user;
