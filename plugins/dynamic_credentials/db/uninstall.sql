DROP TABLE IF EXISTS plugin_dynamic_credentials_account;
DELETE FROM forgeconfig WHERE name = 'dynamic_credentials_user_real_name';
DELETE FROM forgeconfig WHERE name = 'dynamic_credentials_signature_public_key';
