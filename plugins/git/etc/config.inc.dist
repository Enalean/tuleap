<?php

// Backup directory
$git_backup_dir = '/tmp/';

// Number of weeks displayed in widget last project pushes
$weeks_number   = 12;

//
// Urls
// By default, only ssh is available for use and you can setup HTTP(s) access
// to your server.
// For convenience, you can either hardcode the URLs or you can use %server_name%
// variable that will be replace automatically by the value of $sys_default_domain
// in /etc/tuleap/conf/local.inc

// Tell to Tuleap that an HTTPS server for git is available at the given
// address
$git_http_url = "https://%server_name%/plugins/git";

// Define a custom ssh URL to get access to the sources
// You can disable display of this url by activating this variable and setting
// to '' (empty string)
//
// $git_ssh_url = 'ssh://gitolite@%server_name%:2222';

// Tell tuleap to use user friendly URLs in Git Plugin (eg: /git/plugins/project_name/repo_path)
$git_use_friendly_urls = 1;

// If you are mirroring repositories, then you should name the master
// so that end users know where they are cloning from, instead of "Default"
// If empty, "Default" will be displayed to end users
$master_location_name = '';