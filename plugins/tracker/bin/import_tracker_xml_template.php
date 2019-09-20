<?php
//TODO : dont check arguments, but extract parameters from XML file
require_once __DIR__ . '/../../../src/www/include/pre.php';

// PERMISSIONS CHECK

$posix_user = posix_getpwuid(posix_geteuid());
$sys_user = $posix_user['name'];
if ($sys_user !== 'root' && $sys_user !== 'codendiadm') {
    die('Unsufficient privileges for user '.$sys_user.PHP_EOL);
}

// ARGS RETRIEVAL
$xmlFile     =  !empty($argv[1]) ? $argv[1] : '';
$group_id    =  !empty($argv[2]) ? $argv[2] : 100;

$GLOBALS['Response'] = new Response();
$user = UserManager::instance()->forceLogin('admin');

if (!is_readable($xmlFile)) {
    die('Unable to read xml file'.PHP_EOL);
}

// FILE PROCESSING
try {
    $logger = new TruncateLevelLogger(
        new Log_ConsoleLogger(),
        ForgeConfig::get('sys_logger_level')
    );
    $project = ProjectManager::instance()->getProject($group_id);
    if ($project && ! $project->isError()) {
        TrackerXmlImport::build(new XMLImportHelper(UserManager::instance()), $logger)
            ->createFromXMLFile($project, $xmlFile);
        if ($GLOBALS['Response']->feedbackHasErrors()) {
            echo $GLOBALS['Response']->getRawFeedback();
            exit(1);
        }

        if ($GLOBALS['Response']->feedbackHasWarningsOrErrors()) {
            echo $GLOBALS['Response']->getRawFeedback();
            exit(2);
        }
        echo 'Import succeeded'.PHP_EOL;
        exit(0);
    } else {
        fwrite(STDERR, "invalid project".PHP_EOL);
        exit(1);
    }
} catch (XML_ParseException $exception) {
    foreach ($exception->getErrors() as $parse_error) {
        fwrite(STDERR, $parse_error.PHP_EOL);
    }
    echo 'Invalid XML format'.PHP_EOL;
    exit(1);
}
