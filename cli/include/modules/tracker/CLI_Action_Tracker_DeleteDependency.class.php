<?php
/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2007. All rights reserved
*
*
*/

require_once(CODENDI_CLI_DIR.'/CLI_Action.class.php');

class CLI_Action_Tracker_DeleteDependency extends CLI_Action {
    function __construct() {
        parent::__construct('deleteDependency', 'Delete a dependency of a specific artifact.');
        $this->soapCommand = 'deleteArtifactDependency';
        $this->addParam(array(
            'name'           => 'group_artifact_id',
            'description'    => '--tracker_id=<tracker_id>    The ID of the tracker the artifact belongs to.',
            'parameters'     => array('tracker_id'),
        ));
        $this->addParam(array(
            'name'           => 'artifact_id',
            'description'    => '--id=<artifact_id>     ID of the artifact the dependency will be deleted to.',
            'parameters'     => array('id'),
        ));
        $this->addParam(array(
            'name'           => 'dependent_on_artifact_id',
            'description'    => '--dependency=<id>    The ID of the artifact that is dependent on this artifact.',
            'parameters'     => array('dependency'),
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
    function validate_dependent_on_artifact_id(&$dependency) {
        if (!$dependency) {
            exit_error("You must specify the dependency to delete using the --dependency parameter.");
        }
        return true;
    }
}
