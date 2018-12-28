DROP TABLE plugin_gitlfs_authorization_action;
DROP TABLE plugin_gitlfs_object;
DROP TABLE plugin_gitlfs_object_repository;
DROP TABLE plugin_gitlfs_ssh_authorization;

DELETE FROM forgeconfig WHERE name = 'git_lfs_display_config';
DELETE FROM forgeconfig WHERE name = 'git_lfs_max_file_size';