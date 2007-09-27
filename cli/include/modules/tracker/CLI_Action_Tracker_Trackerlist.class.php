<?php
/**
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* $Id$
*/

require_once(CODEX_CLI_DIR.'/CLI_Action.class.php');

class CLI_Action_Tracker_Trackerlist extends CLI_Action {
    function CLI_Action_Tracker_Trackerlist() {
        $this->CLI_Action('trackerlist', 'Returns the list of trackers that belongs to a project.');
        $this->soapCommand = 'getArtifactTypes';
    }
}

?>
