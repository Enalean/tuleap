<?php
/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2007. All rights reserved
*
*
*/

require_once(CODENDI_CLI_DIR.'/CLI_Action.class.php');

class CLI_Action_Tracker_AddDependencies extends CLI_Action {
    function __construct() {
        parent::__construct('addDependencies', 'Add dependencies to a specific artifact.');
        $this->soapCommand = 'addArtifactDependencies';
        $this->addParam(array(
            'name'           => 'group_artifact_id',
            'description'    => '--tracker_id=<tracker_id>    The ID of the tracker the artifact belongs to.',
            'parameters'     => array('tracker_id'),
        ));
        $this->addParam(array(
            'name'           => 'artifact_id',
            'description'    => '--id=<artifact_id>           ID of the artifact the dependencies will be added to.',
            'parameters'     => array('id'),
        ));
        $this->addParam(array(
            'name'           => 'is_dependent_on_artifact_ids',
            'description'    => '--dependencies=<id1,id2>          The IDs of the artifact that are dependent on this artifact. If there is several IDs, separate them with a comma.',
            'parameters'     => array('dependencies'),
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
    function validate_is_dependent_on_artifact_ids(&$dependencies) {
        if (!$dependencies) {
            exit_error("You must specify the dependency(ies) using the --dependencies parameter. (If there is several dependencies, separate the IDs with a comma)");
        }
        return true;
    }
}
