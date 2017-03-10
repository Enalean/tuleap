<?php

  // This script is for development use only

require_once 'pre.php';

use Tuleap\SVN\DiskUsage\Collector;
use Tuleap\SVN\DiskUsage\Retriever;

$disk_usage_dao = new Statistics_DiskUsageDao();
$svn_log_dao    = new SVN_LogDao();
$retriever      = new Retriever($disk_usage_dao);
$collector      = new Collector($svn_log_dao, $retriever);

$disk_usage_manager = new Statistics_DiskUsageManager($disk_usage_dao, $collector, EventManager::instance());
$disk_usage_manager->collectAll();
