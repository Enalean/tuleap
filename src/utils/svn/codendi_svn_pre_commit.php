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

        $txn = $argv[2];
        $logmsg = array();
        exec("/usr/bin/svnlook log -t '$txn' '$repository'", $logmsg);
        $logmsg = implode("\n", $logmsg);

        if (! $ref_manager->stringContainsReferences($logmsg, $project)) {
            // No reference has been found: commit is rejected
            fwrite(STDERR, "\nYou must make at least one reference in the commit message.\n".$unix_group_name);
            exit(1);
        }
    }
} catch (DataAccessException $e) {
    fwrite (STDERR, $e->getMessage());
    exit(1);
}
?>
