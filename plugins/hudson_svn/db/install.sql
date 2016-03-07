CREATE TABLE IF NOT EXISTS plugin_hudson_svn_job (
  job_id int(11) UNSIGNED NOT NULL PRIMARY KEY,
  repository_id int(11) UNSIGNED NOT NULL,
  path TEXT NOT NULL
);