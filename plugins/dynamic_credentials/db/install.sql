INSERT INTO user SET
  user_id = 80,
  user_name = 'forge__dynamic_credential',
  email = '',
  realname = 'Dynamic user',
  register_purpose = NULL,
  status = 'D'
ON DUPLICATE KEY UPDATE
  user_name = 'forge__dynamic_credential',
  email = '',
  realname = 'Dynamic user',
  register_purpose = NULL,
  status = 'D';

INSERT IGNORE INTO user_access SET user_id = 80, last_access_date = '0';

CREATE TABLE plugin_dynamic_credentials_account (
  identifier VARCHAR(64) NOT NULL PRIMARY KEY,
  password VARCHAR(255) NOT NULL,
  expiration INT(11) NOT NULL,
  revoked TINYINT(1) NOT NULL DEFAULT 0
);