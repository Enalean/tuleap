<?php
/**
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* 
*/

require_once(CODEX_CLI_DIR.'/CLI_Action.class.php');

class CLI_Action_Tracker_Tracker extends CLI_Action {
    function CLI_Action_Tracker_Tracker() {
        $this->CLI_Action('tracker', 'Returns the structure of a tracker.');
        $this->soapCommand = 'getArtifactType';
        
        $this->addParam(array(
            'name'           => 'group_artifact_id',
            'description'    => '--tracker_id=<tracker_id>    The ID of the tracker.',
            'parameters'     => array('tracker_id'),
        ));
    }
    
    function validate_group_artifact_id(&$group_artifact_id) {
        if (!$group_artifact_id) {
            exit_error("You must specify a tracker ID using the --tracker_id parameter");
        }
        return true;
    }
}

?>
