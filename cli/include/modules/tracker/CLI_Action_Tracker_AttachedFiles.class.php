<?php
/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2007. All rights reserved
*
*
*/

require_once(CODENDI_CLI_DIR.'/CLI_Action.class.php');

class CLI_Action_Tracker_AttachedFiles extends CLI_Action {
    function __construct() {
        parent::__construct('attachedFiles', 'Returns a list of files attached to a specific artifact.');

        $this->soapCommand = 'getArtifactAttachedFiles';

        $this->addParam(array(
            'name'           => 'group_artifact_id',
            'description'    => '--tracker_id=<tracker_id>    The ID of the tracker the returned attached files belong to.',
            'parameters'     => array('tracker_id'),
        ));
        $this->addParam(array(
            'name'           => 'artifact_id',
            'description'    => '--artifact_id=<artifact ID>        The ID of the artifact.',
            'parameters'     => array('artifact_id'),
        ));
    }
    function validate_group_artifact_id(&$group_artifact_id) {
        if (!$group_artifact_id) {
            exit_error("You must specify a tracker ID using the --tracker_id parameter");
        }
        return true;
    }
    function validate_artifact_id(&$artifact_id) {
        if (!$artifact_id) {
            exit_error("You must specify an artifactID using the --artifact_id parameter");
        }
        return true;
    }

}
