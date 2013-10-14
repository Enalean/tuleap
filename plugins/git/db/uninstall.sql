DROP TABLE IF EXISTS plugin_git_post_receive_mail;
DROP TABLE IF EXISTS plugin_git_log;
DROP TABLE IF EXISTS plugin_git_ci;
DROP TABLE IF EXISTS plugin_git;
DROP TABLE IF EXISTS plugin_git_remote_servers;
DROP TABLE IF EXISTS plugin_git_remote_ugroups;
DROP TABLE IF EXISTS plugin_git_housekeeping;

DELETE FROM service WHERE short_name='plugin_git';
DELETE FROM reference_group WHERE reference_id=30;
DELETE FROM reference WHERE id=30;

DELETE FROM permissions_values WHERE permission_type IN ('PLUGIN_GIT_READ', 'PLUGIN_GIT_WRITE', 'PLUGIN_GIT_WPLUS');

