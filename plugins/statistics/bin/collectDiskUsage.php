<?php
// This script is for development use only

require_once __DIR__ . '/../../../src/www/include/pre.php';

use Tuleap\SVN\DiskUsage\Collector as SVNCollector;
use Tuleap\SVN\DiskUsage\Retriever as SVNRetriever;

$disk_usage_dao = new Statistics_DiskUsageDao();
$svn_log_dao    = new SVN_LogDao();
$svn_retriever  = new SVNRetriever($disk_usage_dao);
$svn_collector  = new SVNCollector($svn_log_dao, $svn_retriever);

$disk_usage_manager = new Statistics_DiskUsageManager(
    $disk_usage_dao,
    $svn_collector,
    EventManager::instance()
);

$disk_usage_manager->collectAll();
