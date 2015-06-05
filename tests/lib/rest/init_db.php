<?php

require_once dirname(__FILE__).'/../../../src/common/autoload.php';
require_once dirname(__FILE__).'/../autoload.php';

$db_init = new REST_DatabaseInitialization();
$db_init->setUp();
