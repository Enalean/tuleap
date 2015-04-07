<?php

require_once 'setpath.php';

putenv("MW_INSTALL_PATH=$mediawikipath");
define('MW_CONFIG_FILE', getcwd().'/LocalSettings.php');
