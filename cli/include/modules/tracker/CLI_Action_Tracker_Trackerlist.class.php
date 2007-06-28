<?php
/**
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* 
*/

require_once(CODEX_CLI_DIR.'/CLI_Action.class.php');

class CLI_Action_Tracker_Trackerlist extends CLI_Action {
    function CLI_Action_Tracker_Trackerlist() {
        $this->CLI_Action('trackerlist', 'Returns the list of trackers that belongs to a project. The tracker description is light, without all the tracker structure. To get the structure, please use the trackers function.');
        $this->soapCommand = 'getTrackerList';
    }
}

?>
