<?php

ini_set('display_errors', 'on');
ini_set('max_execution_time', 0);
ini_set('memory_limit', -1);
date_default_timezone_set('Europe/Paris');

if (version_compare(PHP_VERSION, '5.3.0', '>=')) { 
    error_reporting(E_ALL & ~E_DEPRECATED);
} else {
    error_reporting(E_ALL);
}

if (PHP_INT_SIZE == 4 && extension_loaded('runkit')) {
    require_once(dirname(__FILE__) .'/../include/simpletest/mock_functions.php');
    define('MOCKFUNCTION_AVAILABLE', true);
} else {
    define('MOCKFUNCTION_AVAILABLE', false);
}

// Base dir:
$basedir      = realpath(dirname(__FILE__).'/../../..');
$src_path     = $basedir.'/src';
$include_path = $basedir.'/src/www/include';

ini_set('include_path', ini_get('include_path').':'.$src_path.':'.$include_path);

require(getenv('CODENDI_LOCAL_INC')?getenv('CODENDI_LOCAL_INC'):'/etc/codendi/conf/local.inc');

// Fix path if needed
if (isset($GLOBALS['htmlpurifier_dir'])) {
    ini_set('include_path', ini_get('include_path').PATH_SEPARATOR.$GLOBALS['htmlpurifier_dir']);
}
if (isset($GLOBALS['jpgraph_dir'])) {
    ini_set('include_path', ini_get('include_path').PATH_SEPARATOR.$GLOBALS['jpgraph_dir']);
}

require_once('common/autoload_zend.php');
require_once('common/autoload.php');

require_once dirname(__FILE__).'/../include/simpletest/unit_tester.php';
require_once dirname(__FILE__).'/../include/simpletest/mock_objects.php';
require_once dirname(__FILE__).'/../include/simpletest/web_tester.php';
require_once dirname(__FILE__).'/../include/simpletest/expectation.php';
require_once dirname(__FILE__).'/../include/simpletest/collector.php';

require_once dirname(__FILE__).'/../../../tests/lib/autoload.php';
require_once dirname(__FILE__).'/../../../tests/lib/constants.php';

//require_once dirname(__FILE__).'/../include/TestHelper.class.php';
//require_once 'TuleapTestCase.class.php';
//require_once 'TuleapDbTestCase.class.php';
//require_once 'MockBuilder.php';

?>
