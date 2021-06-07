DROP TABLE IF EXISTS plugin_gitlab_repository_integration;
DROP TABLE IF EXISTS plugin_gitlab_repository_integration_webhook;
DROP TABLE IF EXISTS plugin_gitlab_repository_integration_token;
DROP TABLE IF EXISTS plugin_gitlab_repository_integration_commit_info;
DROP TABLE IF EXISTS plugin_gitlab_repository_integration_merge_request_info;
DROP TABLE IF EXISTS plugin_gitlab_repository_integration_tag_info;
DROP TABLE IF EXISTS plugin_gitlab_repository_integration_branch_info;

DELETE FROM cross_references
WHERE source_type IN ('plugin_gitlab_branch', 'plugin_gitlab_commit', 'plugin_gitlab_mr', 'plugin_gitlab_tag')
OR target_type IN ('plugin_gitlab_branch', 'plugin_gitlab_commit', 'plugin_gitlab_mr', 'plugin_gitlab_tag');
