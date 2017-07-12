<?php
/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2007. All rights reserved
*
*
*/

require_once(CODENDI_CLI_DIR.'/CLI_Action.class.php');

class CLI_Action_Tracker_Comments extends CLI_Action {
    function __construct() {
        parent::__construct('comments', 'Returns the list of follow-up comments associated with a specific artifact.');
        $this->soapCommand = 'getArtifactFollowups';
        $this->addParam(array(
            'name'           => 'group_artifact_id',
            'description'    => '--tracker_id=<tracker_id>    The ID of the tracker the returned artifact comments belong to.',
            'parameters'     => array('tracker_id'),
        ));
        $this->addParam(array(
            'name'           => 'artifact_id',
            'description'    => '--id=<artifact_id>    ID of the artifact.',
            'parameters'     => array('id'),
        ));
    }
    function validate_artifact_id(&$artifact_id) {
        if (!$artifact_id) {
            exit_error("You must specify an artifact ID using the --id parameter");
        }
        return true;
    }
    function validate_group_artifact_id(&$group_artifact_id) {
        if (!$group_artifact_id) {
            exit_error("You must specify a tracker ID using the --tracker_id parameter");
        }
        return true;
    }
}
