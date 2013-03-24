CREATE TABLE plugin_mediawiki_interwiki (
	iw_prefix  TEXT      NOT NULL  UNIQUE,
	iw_url     TEXT      NOT NULL,
	iw_local   SMALLINT  NOT NULL,
	iw_trans   SMALLINT  NOT NULL  DEFAULT 0
);
