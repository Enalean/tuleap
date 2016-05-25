<?php
/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2007. All rights reserved
*
*
*/

require_once(CODENDI_CLI_DIR.'/CLI_Action.class.php');

class CLI_Action_Tracker_UpdateComment extends CLI_Action {
    function __construct() {
        parent::__construct('updateComment', 'Update a follow-up comment.');
        $this->soapCommand = 'updateArtifactFollowUp';
        $this->addParam(array(
            'name'           => 'group_artifact_id',
            'description'    => '--tracker_id=<tracker_id>    The ID of the tracker the returned artifact comments belong to.',
            'parameters'     => array('tracker_id'),
        ));
        $this->addParam(array(
            'name'           => 'artifact_id',
            'description'    => '--id=<artifact_id>    ID of the artifact.',
            'parameters'     => array('artifact_id'),
        ));
        $this->addParam(array(
            'name'           => 'artifact_history_id',
            'description'    => '--comment_id=<comment_id>    ID of the follow-up comment.',
            'parameters'     => array('comment_id'),
        ));
        $this->addParam(array(
            'name'           => 'comment',
            'description'    => '--comment=<comment>    the new follow-up comment.',
            'parameters'     => array('comment'),
        ));
    }
    function validate_comment(&$comment) {
        if (!$comment) {
            exit_error("You must specify a comment using the --comment parameter");
        }
        return true;
    }
    function validate_artifact_history_id(&$comment_id) {
        if (!$comment_id) {
            exit_error("You must specify a comment ID using the --comment_id parameter");
        }
        return true;
    }
    function validate_artifact_id(&$artifact_id) {
        if (!$artifact_id) {
            exit_error("You must specify an artifact ID using the --artifact_id parameter");
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
