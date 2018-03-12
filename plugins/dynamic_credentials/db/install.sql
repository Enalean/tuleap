CREATE TABLE plugin_dynamic_credentials_account (
  identifier VARCHAR(64) NOT NULL PRIMARY KEY,
  password VARCHAR(255) NOT NULL,
  expiration INT(11) NOT NULL,
  revoked TINYINT(1) NOT NULL DEFAULT 0
);