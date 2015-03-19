<?php
//TODO : dont check arguments, but extract parameters from XML file
require_once 'pre.php';

// PERMISSIONS CHECK

$posix_user = posix_getpwuid(posix_geteuid());
$sys_user = $posix_user['name'];
if ( $sys_user !== 'root' && $sys_user !== 'codendiadm' ) {
    die('Unsufficient privileges for user '.$sys_user.PHP_EOL);
}

// ARGS RETRIEVAL
$xmlFile     =  !empty($argv[1]) ? $argv[1] : '';
$group_id    =  !empty($argv[2]) ? $argv[2] : 100;

$GLOBALS['Response'] = new Response();
$user = UserManager::instance()->forceLogin('admin');

if ( !is_readable($xmlFile) ) {
    die('Unable to read xml file'.PHP_EOL);
}

// FILE PROCESSING
$output = '';
ob_start();
TrackerXmlImport::build()->createFromXMLFile($group_id, $xmlFile);
$output  = ob_get_contents();
ob_end_flush();

// WARN AND ERRORS PARSING
$matches = array();
if ( preg_match_all('/.*\s+error\:\s+.*/', $output, $matches) ) {
    echo 'Invalid XML format'.PHP_EOL;    
    exit(1);
}

if ( $GLOBALS['Response']->feedbackHasErrors() ) {
    echo $GLOBALS['Response']->getRawFeedback();    
    exit(1);
}

if ( $GLOBALS['Response']->feedbackHasWarningsOrErrors() ) {
    echo $GLOBALS['Response']->getRawFeedback();    
    exit(2);
}

echo 'Import succeeded'.PHP_EOL;
exit(0);
