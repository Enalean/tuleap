## mysqldiff 0.23
## 
## run on Wed Sep  6 16:01:49 2000
##
## --- file: SF1.5.sql
## +++ file: SF2.0.sql

ALTER TABLE filerelease CHANGE COLUMN release_time int(11) DEFAULT '0' NOT NULL; # was int(11)
ALTER TABLE filerelease ADD INDEX idx_release_time (release_time);

ALTER TABLE forum ADD INDEX idx_forum_thread_date_followup (thread_id,date,is_followup_to);
ALTER TABLE forum ADD INDEX idx_forum_id_date_followup (group_forum_id,date,is_followup_to);
ALTER TABLE forum ADD INDEX idx_forum_id_date (group_forum_id,date);

ALTER TABLE frs_dlstats_file_agg DROP COLUMN downloads_total; # was int(11) DEFAULT '0' NOT NULL
ALTER TABLE frs_dlstats_file_agg ADD COLUMN downloads int(11) DEFAULT '0' NOT NULL;
ALTER TABLE frs_dlstats_file_agg ADD COLUMN day int(11) DEFAULT '0' NOT NULL;
ALTER TABLE frs_dlstats_file_agg ADD INDEX idx_dlstats_file_down (downloads);
ALTER TABLE frs_dlstats_file_agg ADD INDEX idx_dlstats_file_file_id (file_id);
ALTER TABLE frs_dlstats_file_agg ADD INDEX idx_dlstats_file_day (day);

ALTER TABLE frs_dlstats_filetotal_agg ADD INDEX idx_stats_agr_tmp_fid (file_id);
ALTER TABLE frs_dlstats_filetotal_agg DROP PRIMARY KEY; # was ((file_id))
ALTER TABLE frs_dlstats_filetotal_agg ADD PRIMARY KEY ();

ALTER TABLE frs_dlstats_grouptotal_agg ADD INDEX idx_stats_agr_tmp_gid (group_id);
ALTER TABLE frs_dlstats_grouptotal_agg DROP PRIMARY KEY; # was ((group_id))
ALTER TABLE frs_dlstats_grouptotal_agg ADD PRIMARY KEY ();

ALTER TABLE groups DROP COLUMN file_downloads; # was int(11) DEFAULT '0' NOT NULL
ALTER TABLE groups ADD COLUMN send_all_patches int(11) DEFAULT '0' NOT NULL;
ALTER TABLE groups ADD COLUMN send_all_bugs int(11) DEFAULT '0' NOT NULL;
ALTER TABLE groups ADD COLUMN send_all_support int(11) DEFAULT '0' NOT NULL;

ALTER TABLE mail_group_list ADD COLUMN description text;

ALTER TABLE project_group_list ADD COLUMN description text;

ALTER TABLE stats_agg_pages_by_day CHANGE COLUMN count int(11) DEFAULT '0' NOT NULL; # was int(11)
ALTER TABLE stats_agg_pages_by_day CHANGE COLUMN day int(11) DEFAULT '0' NOT NULL; # was int(11)
ALTER TABLE stats_agg_pages_by_day ADD INDEX idx_pages_by_day_day (day);

ALTER TABLE survey_rating_aggregate CHANGE COLUMN response float DEFAULT '0' NOT NULL; # was float(10,2) DEFAULT '0.00' NOT NULL

DROP TABLE temp_trove_treesums;

CREATE TABLE db_images (
  id int(11) NOT NULL auto_increment,
  group_id int(11) DEFAULT '0' NOT NULL,
  description text DEFAULT '' NOT NULL,
  bin_data longblob DEFAULT '' NOT NULL,
  filename text DEFAULT '' NOT NULL,
  filesize int(11) DEFAULT '0' NOT NULL,
  filetype text DEFAULT '' NOT NULL,
  width int(11) DEFAULT '0' NOT NULL,
  height int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (id),
  KEY idx_db_images_group (group_id)
);

CREATE TABLE foundry_data (
  foundry_id int(11) NOT NULL auto_increment,
  freeform1_html text,
  freeform2_html text,
  sponsor1_html text,
  sponsor2_html text,
  guide_image_id int(11) DEFAULT '0' NOT NULL,
  logo_image_id int(11) DEFAULT '0' NOT NULL,
  trove_categories text,
  PRIMARY KEY (foundry_id)
);

CREATE TABLE foundry_news (
  foundry_news_id int(11) NOT NULL auto_increment,
  foundry_id int(11) DEFAULT '0' NOT NULL,
  news_id int(11) DEFAULT '0' NOT NULL,
  approve_date int(11) DEFAULT '0' NOT NULL,
  is_approved int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (foundry_news_id),
  KEY idx_foundry_news_foundry (foundry_id),
  KEY idx_foundry_news_foundry_approved_date (foundry_id,is_approved,approve_date),
  KEY idx_foundry_news_foundry_approved (foundry_id,is_approved)
);

CREATE TABLE foundry_preferred_projects (
  foundry_project_id int(11) NOT NULL auto_increment,
  foundry_id int(11) DEFAULT '0' NOT NULL,
  group_id int(11) DEFAULT '0' NOT NULL,
  rank int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (foundry_project_id),
  KEY idx_foundry_project_group (group_id),
  KEY idx_foundry_project_group_rank (group_id,rank)
);

CREATE TABLE foundry_projects (
  id int(11) NOT NULL auto_increment,
  foundry_id int(11) DEFAULT '0' NOT NULL,
  project_id int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (id),
  KEY idx_foundry_projects_foundry (foundry_id)
);

CREATE TABLE frs_file (
  file_id int(11) NOT NULL auto_increment,
  filename text,
  release_id int(11) DEFAULT '0' NOT NULL,
  type_id int(11) DEFAULT '0' NOT NULL,
  processor_id int(11) DEFAULT '0' NOT NULL,
  release_time int(11) DEFAULT '0' NOT NULL,
  file_size int(11) DEFAULT '0' NOT NULL,
  post_date int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (file_id),
  KEY idx_frs_file_release_id (release_id),
  KEY idx_frs_file_type (type_id),
  KEY idx_frs_file_date (post_date),
  KEY idx_frs_file_processor (processor_id),
  KEY idx_frs_file_name (filename(45))
);

CREATE TABLE frs_filetype (
  type_id int(11) NOT NULL auto_increment,
  name text,
  PRIMARY KEY (type_id)
);

CREATE TABLE frs_package (
  package_id int(11) NOT NULL auto_increment,
  group_id int(11) DEFAULT '0' NOT NULL,
  name text,
  status_id int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (package_id),
  KEY idx_package_group_id (group_id)
);

CREATE TABLE frs_processor (
  processor_id int(11) NOT NULL auto_increment,
  name text,
  PRIMARY KEY (processor_id)
);

CREATE TABLE frs_release (
  release_id int(11) NOT NULL auto_increment,
  package_id int(11) DEFAULT '0' NOT NULL,
  name text,
  notes text,
  changes text,
  status_id int(11) DEFAULT '0' NOT NULL,
  preformatted int(11) DEFAULT '0' NOT NULL,
  release_date int(11) DEFAULT '0' NOT NULL,
  released_by int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (release_id),
  KEY idx_frs_release_by (released_by),
  KEY idx_frs_release_date (release_date),
  KEY idx_frs_release_package (package_id)
);

CREATE TABLE frs_status (
  status_id int(11) NOT NULL auto_increment,
  name text,
  PRIMARY KEY (status_id)
);

CREATE TABLE group_history (
  group_history_id int(11) NOT NULL auto_increment,
  group_id int(11) DEFAULT '0' NOT NULL,
  field_name text DEFAULT '' NOT NULL,
  old_value text DEFAULT '' NOT NULL,
  mod_by int(11) DEFAULT '0' NOT NULL,
  date int(11),
  PRIMARY KEY (group_history_id),
  KEY idx_group_history_group_id (group_id)
);

CREATE TABLE stats_agg_site_by_day (
  day int(11) DEFAULT '0' NOT NULL,
  count int(11) DEFAULT '0' NOT NULL
);

CREATE TABLE stats_agg_site_by_group (
  day int(11) DEFAULT '0' NOT NULL,
  group_id int(11) DEFAULT '0' NOT NULL,
  count int(11) DEFAULT '0' NOT NULL
);

CREATE TABLE stats_agr_filerelease (
  filerelease_id int(11) DEFAULT '0' NOT NULL,
  group_id int(11) DEFAULT '0' NOT NULL,
  downloads int(11) DEFAULT '0' NOT NULL,
  KEY idx_stats_agr_tmp_fid (filerelease_id),
  KEY idx_stats_agr_tmp_gid (group_id)
);

CREATE TABLE stats_agr_project (
  group_id int(11) DEFAULT '0' NOT NULL,
  group_ranking int(11) DEFAULT '0' NOT NULL,
  group_metric float(8,5) DEFAULT '0.00000' NOT NULL,
  developers smallint(6) DEFAULT '0' NOT NULL,
  file_releases smallint(6) DEFAULT '0' NOT NULL,
  downloads int(11) DEFAULT '0' NOT NULL,
  site_views int(11) DEFAULT '0' NOT NULL,
  logo_views int(11) DEFAULT '0' NOT NULL,
  msg_posted smallint(6) DEFAULT '0' NOT NULL,
  msg_uniq_auth smallint(6) DEFAULT '0' NOT NULL,
  bugs_opened smallint(6) DEFAULT '0' NOT NULL,
  bugs_closed smallint(6) DEFAULT '0' NOT NULL,
  support_opened smallint(6) DEFAULT '0' NOT NULL,
  support_closed smallint(6) DEFAULT '0' NOT NULL,
  patches_opened smallint(6) DEFAULT '0' NOT NULL,
  patches_closed smallint(6) DEFAULT '0' NOT NULL,
  tasks_opened smallint(6) DEFAULT '0' NOT NULL,
  tasks_closed smallint(6) DEFAULT '0' NOT NULL,
  help_requests smallint(6) DEFAULT '0' NOT NULL,
  cvs_checkouts smallint(6) DEFAULT '0' NOT NULL,
  cvs_commits smallint(6) DEFAULT '0' NOT NULL,
  cvs_adds smallint(6) DEFAULT '0' NOT NULL,
  KEY idx_project_agr_log_group (group_id)
);

CREATE TABLE stats_ftp_downloads (
  day int(11) DEFAULT '0' NOT NULL,
  filerelease_id int(11) DEFAULT '0' NOT NULL,
  group_id int(11) DEFAULT '0' NOT NULL,
  downloads int(11) DEFAULT '0' NOT NULL,
  KEY idx_ftpdl_day (day),
  KEY idx_ftpdl_fid (filerelease_id),
  KEY idx_ftpdl_group_id (group_id)
);

CREATE TABLE stats_http_downloads (
  day int(11) DEFAULT '0' NOT NULL,
  filerelease_id int(11) DEFAULT '0' NOT NULL,
  group_id int(11) DEFAULT '0' NOT NULL,
  downloads int(11) DEFAULT '0' NOT NULL,
  KEY idx_httpdl_day (day),
  KEY idx_httpdl_fid (filerelease_id),
  KEY idx_httpdl_group_id (group_id)
);

CREATE TABLE stats_project (
  month int(11) DEFAULT '0' NOT NULL,
  week int(11) DEFAULT '0' NOT NULL,
  day int(11) DEFAULT '0' NOT NULL,
  group_id int(11) DEFAULT '0' NOT NULL,
  group_ranking int(11) DEFAULT '0' NOT NULL,
  group_metric float(8,5) DEFAULT '0.00000' NOT NULL,
  developers smallint(6) DEFAULT '0' NOT NULL,
  file_releases smallint(6) DEFAULT '0' NOT NULL,
  downloads int(11) DEFAULT '0' NOT NULL,
  site_views int(11) DEFAULT '0' NOT NULL,
  subdomain_views int(11) DEFAULT '0' NOT NULL,
  msg_posted smallint(6) DEFAULT '0' NOT NULL,
  msg_uniq_auth smallint(6) DEFAULT '0' NOT NULL,
  bugs_opened smallint(6) DEFAULT '0' NOT NULL,
  bugs_closed smallint(6) DEFAULT '0' NOT NULL,
  support_opened smallint(6) DEFAULT '0' NOT NULL,
  support_closed smallint(6) DEFAULT '0' NOT NULL,
  patches_opened smallint(6) DEFAULT '0' NOT NULL,
  patches_closed smallint(6) DEFAULT '0' NOT NULL,
  tasks_opened smallint(6) DEFAULT '0' NOT NULL,
  tasks_closed smallint(6) DEFAULT '0' NOT NULL,
  help_requests smallint(6) DEFAULT '0' NOT NULL,
  cvs_checkouts smallint(6) DEFAULT '0' NOT NULL,
  cvs_commits smallint(6) DEFAULT '0' NOT NULL,
  cvs_adds smallint(6) DEFAULT '0' NOT NULL,
  KEY idx_project_log_group (group_id),
  KEY idx_archive_project_month (month),
  KEY idx_archive_project_week (week),
  KEY idx_archive_project_day (day),
  KEY idx_archive_project_monthday (month,day)
);

CREATE TABLE stats_site (
  month int(11) DEFAULT '0' NOT NULL,
  week int(11) DEFAULT '0' NOT NULL,
  day int(11) DEFAULT '0' NOT NULL,
  site_views int(11) DEFAULT '0' NOT NULL,
  subdomain_views int(11) DEFAULT '0' NOT NULL,
  downloads int(11) DEFAULT '0' NOT NULL,
  uniq_users int(11) DEFAULT '0' NOT NULL,
  sessions int(11) DEFAULT '0' NOT NULL,
  total_users int(11) DEFAULT '0' NOT NULL,
  new_users int(11) DEFAULT '0' NOT NULL,
  new_projects int(11) DEFAULT '0' NOT NULL,
  KEY idx_stats_site_month (month),
  KEY idx_stats_site_week (week),
  KEY idx_stats_site_day (day),
  KEY idx_stats_site_monthday (month,day)
);

CREATE TABLE theme_prefs (
  user_id int(11) DEFAULT '0' NOT NULL,
  user_theme int(8) DEFAULT '0' NOT NULL,
  BODY_font char(80) DEFAULT '',
  BODY_size char(5) DEFAULT '',
  TITLEBAR_font char(80) DEFAULT '',
  TITLEBAR_size char(5) DEFAULT '',
  COLOR_TITLEBAR_BACK char(7) DEFAULT '',
  COLOR_LTBACK1 char(7) DEFAULT '',
  PRIMARY KEY (user_id)
);

CREATE TABLE themes (
  theme_id int(11) NOT NULL auto_increment,
  dirname varchar(80),
  fullname varchar(80),
  PRIMARY KEY (theme_id)
);


