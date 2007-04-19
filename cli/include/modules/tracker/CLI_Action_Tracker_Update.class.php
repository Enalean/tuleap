<?php
/**
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* 
*/

require_once('CLI_Action_Tracker_MajFields.class.php');

class CLI_Action_Tracker_Update extends CLI_Action_Tracker_MajFields {
    function CLI_Action_Tracker_Update() {
        $this->CLI_Action_Tracker_MajFields('update', 'Update an artifact in a tracker.');
        $this->soapCommand = 'updateArtifactWithFieldNames';
    }
    function addParamArtifactId() {
        $this->addParam(array(
            'name'           => 'artifact_id',
            'description'    => '--id=<artifact_id>           ID of the artifact that will be updated.',
            'parameters'     => array('id'),
        ));
    }
    function validate_artifact_id(&$artifact_id) {
        if (!$artifact_id) {
            exit_error("You must specify an artifact ID using the --id parameter");
        }
        return true;
    }
    function getGroupArtifactIdDescription() {
        return 'Specify the ID of the tracker the artifact will be updated in.';
    }
}

?>
