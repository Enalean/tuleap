<?php

// Sad Martin is Sad :'(
$IS_ACCESSING_INDEX_PHP = preg_match('#^.*index\.php$#', $_SERVER['REQUEST_URI']);

require_once 'setenv.php';
require_once "$mediawikipath/index.php";