#
# Table structure for table 'doc_data'
#

CREATE TABLE doc_data (
  docid int(11) NOT NULL auto_increment,
  title varchar(255) NOT NULL default '',
  data longblob NOT NULL,
  updatedate int(11) NOT NULL default '0',
  createdate int(11) NOT NULL default '0',
  created_by int(11) NOT NULL default '0',
  doc_group int(11) NOT NULL default '0',
  rank int(11) NOT NULL default '0',
  description text,
  filename text,
  filesize INT UNSIGNED NOT NULL DEFAULT 0,
  filetype text,
  PRIMARY KEY  (docid),
  KEY idx_doc_group_doc_group (doc_group)
);

#
# Table structure for table 'doc_groups'
#

CREATE TABLE doc_groups (
  doc_group int(12) NOT NULL auto_increment,
  groupname varchar(255) NOT NULL default '',
  group_rank int(11) NOT NULL default '0',
  group_id int(11) NOT NULL default '0',
  PRIMARY KEY  (doc_group),
  KEY idx_doc_groups_group (group_id)
);

#
# Table structure for table 'doc_log'
#

CREATE TABLE doc_log (
  user_id int(11) NOT NULL default '0',
  docid int(11) NOT NULL default '0',
  time int(11) NOT NULL default '0',
  KEY all_idx (user_id,docid),
  KEY time_idx (time),
  KEY docid_idx (docid)
);
