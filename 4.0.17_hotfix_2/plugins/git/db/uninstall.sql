DROP TABLE IF EXISTS plugin_git_post_receive_mail;
DROP TABLE IF EXISTS plugin_git;

DELETE FROM service WHERE short_name='git';
DELETE FROM reference_group WHERE reference_id=30;
DELETE FROM reference WHERE id=30;

