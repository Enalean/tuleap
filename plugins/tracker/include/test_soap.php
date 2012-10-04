<?php

require_once 'pre.php';
require_once 'Tracker/SOAPServer.class.php';

$soap_server = new Tracker_SOAPServer(
    UserManager::instance(),
    TrackerFactory::instance(),
    PermissionsManager::instance(),
    new Tracker_ReportDao(),
    Tracker_FormElementFactory::instance()
);

$criteria = array(
    array(
        'name' => 'remaining_effort',
        'value' => '>=5'
    )
);

$group_id = $offset = $max_rows = 0;

$res = $soap_server->getArtifacts('f3bc736bcf98a5e78947cc605e5d22f0', $group_id, 276, $criteria, $offset, $max_rows);
var_dump($res);