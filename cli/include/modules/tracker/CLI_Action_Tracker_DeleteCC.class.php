<?php
/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2007. All rights reserved
*
*
*/

require_once(CODENDI_CLI_DIR.'/CLI_Action.class.php');

class CLI_Action_Tracker_DeleteCC extends CLI_Action {
    function __construct() {
        parent::__construct('deleteCC', 'Delete a CC of a specific artifact.');
        $this->soapCommand = 'deleteArtifactCC';
        $this->addParam(array(
            'name'           => 'group_artifact_id',
            'description'    => '--tracker_id=<tracker_id>    The ID of the tracker the artifact CC belong to.',
            'parameters'     => array('tracker_id'),
        ));
        $this->addParam(array(
            'name'           => 'artifact_id',
            'description'    => '--id=<artifact_id>    ID of the artifact the CC will be deleted.',
            'parameters'     => array('id'),
        ));
        $this->addParam(array(
            'name'           => 'artifact_cc_id',
            'description'    => '--cc_id=<artifact_cc_id>     The ID of the artifact_cc to delete.',
            'parameters'     => array('cc_id'),
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
    function validate_artifact_cc_id(&$cc_id) {
        if (!$cc_id) {
            exit_error("You must specify the CC to delete using the --cc_id parameter.");
        }
        return true;
    }
}
