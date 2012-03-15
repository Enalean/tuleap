<?php

ini_set('max_execution_time', 0);
ini_set('memory_limit', -1);

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

/**
 * Method called when a class is not defined.
 *
 * Used to load Zend classes on the fly
 *
 * @param String $className
 *
 * @return void
 */
function __autoload($className) {
    global $Language;
    if (strpos($className, 'Zend') === 0 && !class_exists($className)) {
        if (isset($GLOBALS['zend_path'])) {
            ini_set('include_path', $GLOBALS['zend_path'].':'.ini_get('include_path'));
            $path = str_replace('_', '/', $className);
            require_once $path.'.php';
        } else if (is_dir('/usr/share/zend')) {
            ini_set('include_path', '/usr/share/zend/:'.ini_get('include_path'));
            $path = str_replace('_', '/', $className);
            require_once $path.'.php';
        } else {
            exit_error($Language->getText('global','error'),$Language->getText('include_pre','zend_path_not_set',$GLOBALS['sys_email_admin']));
        }
    }
}

?>
