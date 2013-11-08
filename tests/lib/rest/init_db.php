<?php

require_once dirname(__FILE__).'/../../../src/common/autoload.php';
require_once dirname(__FILE__).'/../autoload.php';

$db_init = new DatabaseInitialization();
$db_init->setUp();
