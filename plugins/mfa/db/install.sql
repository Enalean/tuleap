CREATE TABLE plugin_mfa_enrollment_totp (
  user_id INT(11) NOT NULL PRIMARY KEY,
  secret BLOB NOT NULL
);
