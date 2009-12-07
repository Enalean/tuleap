<?php

  // This script is for development use only

require_once 'pre.php';
require_once dirname(__FILE__).'/../include/Statistics_DiskUsageManager.class.php';


$dum = new Statistics_DiskUsageManager();
$dum->collectAll();

?>
