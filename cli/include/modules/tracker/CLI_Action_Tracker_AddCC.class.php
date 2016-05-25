<?php
/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2007. All rights reserved
*
*
*/

require_once(CODENDI_CLI_DIR.'/CLI_Action.class.php');

class CLI_Action_Tracker_AddCC extends CLI_Action {
    function __construct() {
        parent::__construct('addCC', 'Add a CC list to a specific artifact.');
        $this->soapCommand = 'addArtifactCC';
        $this->addParam(array(
            'name'           => 'group_artifact_id',
            'description'    => '--tracker_id=<tracker_id>    The ID of the tracker the artifact belongs to.',
            'parameters'     => array('tracker_id'),
        ));
        $this->addParam(array(
            'name'           => 'artifact_id',
            'description'    => '--id=<artifact_id>           ID of the artifact the CC list will be added to.',
            'parameters'     => array('id'),
        ));
        $this->addParam(array(
            'name'           => 'cc_list',
            'description'    => '--cc_list=<cc_list>          The list of emails or logins to add in CC.',
            'parameters'     => array('cc_list'),
        ));
        $this->addParam(array(
            'name'           => 'cc_comment',
            'description'    => '--cc_comment=<cc_comment>    The optional comment that goes with the CC.',
            'parameters'     => array('cc_comment'),
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
    function validate_cc_list(&$cc_list) {
        if (!$cc_list) {
            exit_error("You must specify the CC list using the --cc_list parameter");
        }
        return true;
    }
}
