
CREATE TABLE stats_site ( 
	month	int(11) DEFAULT '0' NOT NULL,
	week	int(11) DEFAULT '0' NOT NULL,
	day	int(11) DEFAULT '0' NOT NULL,

	site_views	int(11) DEFAULT '0' NOT NULL,
	subdomain_views	int(11) DEFAULT '0' NOT NULL,
	downloads	int(11) DEFAULT '0' NOT NULL,
	uniq_users	int(11) DEFAULT '0' NOT NULL,
	sessions	int(11) DEFAULT '0' NOT NULL,
	total_users	int(11) DEFAULT '0' NOT NULL,
	new_users	int(11) DEFAULT '0' NOT NULL,
	new_projects	int(11) DEFAULT '0' NOT NULL,

	KEY idx_stats_site_month (month),
	KEY idx_stats_site_week (week),
	KEY idx_stats_site_day (day)
);

CREATE TABLE stats_project ( 
	month	int(11) DEFAULT '0' NOT NULL,
	week	int(11) DEFAULT '0' NOT NULL,
	day	int(11) DEFAULT '0' NOT NULL,

	group_id	int(11) DEFAULT '0' NOT NULL,
	group_ranking	int(11) DEFAULT '0' NOT NULL,
	group_metric	float(8,5) DEFAULT '0' NOT NULL,
	developers	smallint(6) DEFAULT '0' NOT NULL,
	file_releases	smallint(6) DEFAULT '0' NOT NULL,
	downloads	int(11) DEFAULT '0' NOT NULL,
	site_views	int(11) DEFAULT '0' NOT NULL,
	proj_views	int(11) DEFAULT '0' NOT NULL,
	msg_posted	smallint(6) DEFAULT '0' NOT NULL,
	msg_uniq_auth	smallint(6) DEFAULT '0' NOT NULL,
	bugs_opened	smallint(6) DEFAULT '0' NOT NULL,
	bugs_closed	smallint(6) DEFAULT '0' NOT NULL,
	support_opened	smallint(6) DEFAULT '0' NOT NULL,
	support_closed	smallint(6) DEFAULT '0' NOT NULL,
	patches_opened	smallint(6) DEFAULT '0' NOT NULL,
	patches_closed	smallint(6) DEFAULT '0' NOT NULL,
	tasks_opened	smallint(6) DEFAULT '0' NOT NULL,
	tasks_closed	smallint(6) DEFAULT '0' NOT NULL,
	help_requests	smallint(6) DEFAULT '0' NOT NULL,
	cvs_checkouts	smallint(6) DEFAULT '0' NOT NULL,
	cvs_commits	smallint(6) DEFAULT '0' NOT NULL,
	cvs_adds	smallint(6) DEFAULT '0' NOT NULL,

	KEY idx_project_log_group (group_id),
	KEY idx_archive_project_month (month),
	KEY idx_archive_project_week (week),
	KEY idx_archive_project_day (day)
);


CREATE TABLE stats_agr_project ( 
	group_id	int(11) DEFAULT '0' NOT NULL,
	group_ranking	int(11) DEFAULT '0' NOT NULL,
	group_metric	float(8,5) DEFAULT '0' NOT NULL,
	developers	smallint(6) DEFAULT '0' NOT NULL,
	file_releases	smallint(6) DEFAULT '0' NOT NULL,
	downloads	int(11) DEFAULT '0' NOT NULL,
	site_views	int(11) DEFAULT '0' NOT NULL,
	subdomain_views	int(11) DEFAULT '0' NOT NULL,
	msg_posted	smallint(6) DEFAULT '0' NOT NULL,
	msg_uniq_auth	smallint(6) DEFAULT '0' NOT NULL,
	bugs_opened	smallint(6) DEFAULT '0' NOT NULL,
	bugs_closed	smallint(6) DEFAULT '0' NOT NULL,
	support_opened	smallint(6) DEFAULT '0' NOT NULL,
	support_closed	smallint(6) DEFAULT '0' NOT NULL,
	patches_opened	smallint(6) DEFAULT '0' NOT NULL,
	patches_closed	smallint(6) DEFAULT '0' NOT NULL,
	tasks_opened	smallint(6) DEFAULT '0' NOT NULL,
	tasks_closed	smallint(6) DEFAULT '0' NOT NULL,
	help_requests	smallint(6) DEFAULT '0' NOT NULL,
	cvs_checkouts	smallint(6) DEFAULT '0' NOT NULL,
	cvs_commits	smallint(6) DEFAULT '0' NOT NULL,
	cvs_adds	smallint(6) DEFAULT '0' NOT NULL,

	KEY idx_project_agr_log_group (group_id)
);



