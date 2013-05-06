<?php

/**
 * PHP file called by the pre-commit hook
 */

try {
    include_once("pre.php");
    include_once("common/reference/ReferenceManager.class.php");

    $repository = $argv[1];
    // retrieve the group name from repository
    $unix_group_name = substr($repository, strlen($GLOBALS['svn_prefix'])+1);
    $group_id = group_getid_by_name($unix_group_name);
    $project = new Project($group_id);

    if ($project->isSVNMandatoryRef()) {
        $ref_manager = ReferenceManager::instance();

        // open the standard error output
        $stderr = fopen('php://stderr', 'w');

        $txn = $argv[2];
        $logmsg = array();
        exec("/usr/bin/svnlook log -t '$txn' '$repository'", $logmsg);
        $logmsg = implode("\n", $logmsg);
        
        $references_array = array();
        $references_array = $ref_manager->extractReferences($logmsg, $project->getId());

        if (sizeof($references_array) < 1) {
            // No reference has been found: commit is rejected
            fwrite($stderr, "\nYou must make at least one reference in the commit message.\n".$unix_group_name);
            fclose($stderr);
            exit(1);
        }
        fclose($stderr);
    }
} catch (DataAccessException $e) {
    $stderr = fopen('php://stderr', 'w');
    fwrite ($stderr, $e->getMessage());
    fclose($stderr);
    exit(1);
}
?>
