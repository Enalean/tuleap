<?php
/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2007. All rights reserved
*
*
*/

require_once(CODENDI_CLI_DIR.'/CLI_Action.class.php');

class CLI_Action_Tracker_Trackers extends CLI_Action {
    function __construct() {
        parent::__construct('trackers', 'Returns the list of trackers (with their structure) that belongs to a project.');
        $this->soapCommand = 'getArtifactTypes';
    }
}
