<?php
/**
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* 
*/

require_once(CODEX_CLI_DIR.'/CLI_Action.class.php');

class CLI_Action_Tracker_Trackers extends CLI_Action {
    function CLI_Action_Tracker_Trackers() {
        $this->CLI_Action('trackers', 'Returns the list of trackers (with their structure) that belongs to a project.');
        $this->soapCommand = 'getArtifactTypes';
    }
}

?>
