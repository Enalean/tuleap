<?php
/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2007. All rights reserved
*
*
*/

require_once(CODENDI_CLI_DIR.'/CLI_Action.class.php');

class CLI_Action_Tracker_DeleteAttachedFile extends CLI_Action {
    function __construct() {
        parent::__construct('deleteAttachedFile', 'Delete an attached file to a specific artifact.');

        $this->soapCommand = 'deleteArtifactAttachedFile';

        $this->addParam(array(
            'name'           => 'group_artifact_id',
            'description'    => '--tracker_id=<tracker_id>    The ID of the tracker the returned attached file belong to.',
            'parameters'     => array('tracker_id'),
        ));
        $this->addParam(array(
            'name'           => 'artifact_id',
            'description'    => '--artifact_id=<artifact ID>        The ID of the artifact the attached file belong to.',
            'parameters'     => array('artifact_id'),
        ));
        $this->addParam(array(
            'name'           => 'file_id',
            'description'    => '--file_id=<file ID>          ID of the attach file to delete',
            'parameters'     => array('file_id'),
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
            exit_error("You must specify an artifact ID using the --artifact_id parameter");
        }
        return true;
    }
    function validate_file_id(&$file_id) {
        if (!$file_id) {
            exit_error("You must specify a file ID using the --file_id parameter");
        }
        return true;
    }
}
