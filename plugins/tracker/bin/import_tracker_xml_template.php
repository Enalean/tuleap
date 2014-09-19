<?php
//TODO : dont check arguments, but extract parameters from XML file
require_once 'www/include/pre.php';
require_once 'common/include/Response.class.php';
require_once dirname(__FILE__) .'/../include/Tracker/TrackerFactory.class.php';

// PERMISSIONS CHECK

$posix_user = posix_getpwuid(posix_geteuid());
$sys_user = $posix_user['name'];
if ( $sys_user !== 'root' && $sys_user !== 'codendiadm' ) {
    die('Unsufficient privileges for user '.$sys_user.PHP_EOL);
}

// ARGS RETRIEVAL
$xmlFile     =  !empty($argv[1]) ? $argv[1] : '';
$name        =  !empty($argv[2]) ? $argv[2] : '';
$description =  !empty($argv[3]) ? $argv[3] : '';
$item_name   =  !empty($argv[4]) ? $argv[4] : '';
$group_id    =  !empty($argv[5]) ? $argv[5] : 100;
$user_name   =  !empty($argv[6]) ? $argv[6] : 'admin';

$GLOBALS['Response'] = new Response();
$user = UserManager::instance()->forceLogin($user_name);

if ($user->isAnonymous()) {
    die("Unable to authenticate the user, cannot import the template".PHP_EOL);
}

if ( !is_readable($xmlFile) ) {
    die('Unable to read xml file'.PHP_EOL);
}

$xml_security = new XML_Security();
$xml_element  = $xml_security->loadFile($xml_file_path);
if (empty($xml_element)) {
    echo 'Can not open file '.$xmlFile.PHP_EOL;
    exit(1);
}

if ( empty($name) ) {
    echo 'Fetching name from XML'.PHP_EOL;
    $name = $xml_element->name;
}

if ( empty($description) ) {
    echo 'Fetching description from XML'.PHP_EOL;
    $description = $xml_element->description;
}

if ( empty($item_name) ) {
    echo 'Fetching item name from XML'.PHP_EOL;
    $item_name = $xml_element->item_name;
}

#FILE PROCESSING
$output = '';
ob_start();
$tf      = TrackerFactory::instance();
$tracker = $tf->createFromXML($xml_element, $group_id, $name, $description, $item_name, null);
$output  = ob_get_contents();
ob_end_flush();

#WARN AND ERRORS PARSING
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




?>
