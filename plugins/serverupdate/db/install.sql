CREATE TABLE `upgrade` (
    date INT(11) UNSIGNED NOT NULL default '0',
    script VARCHAR(64) NOT NULL default '',
    execution_mode VARCHAR(32) NOT NULL default '',
    success TINYINT(4) NOT NULL default '0',
    error TEXT NOT NULL,
  PRIMARY KEY(date)
);
