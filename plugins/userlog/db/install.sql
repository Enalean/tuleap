##
## Sql Install Script
##
DROP TABLE IF EXISTS plugin_userlog_request;
CREATE TABLE plugin_userlog_request (
  time int(11) NOT NULL default 0,
  group_id int(11) NOT NULL default 0,
  user_id int(11) NOT NULL default 0,
  http_user_agent VARCHAR(255) NOT NULL default '',
  http_request_uri VARCHAR(255) NOT NULL default '',
  http_request_method VARCHAR(4) NOT NULL default '',
  http_remote_addr VARCHAR(16) NOT NULL default '',
  http_referer VARCHAR(255) NOT NULL default '',
  KEY idx_time (time),
  KEY idx_group_id (group_id),
  KEY idx_user_id(user_id)
);
