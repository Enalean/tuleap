<?php
/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2007. All rights reserved
*
*
*/

require_once(CODENDI_CLI_DIR.'/CLI_Action.class.php');

class CLI_Action_Tracker_AddComment extends CLI_Action {
    function __construct() {
        parent::__construct('addComment', 'Add a follow-up comment to a specific artifact.');
        $this->soapCommand = 'addArtifactFollowup';
        $this->addParam(array(
            'name'           => 'group_artifact_id',
            'description'    => '--tracker_id=<tracker_id>    The ID of the tracker the artifact comments belong to.',
            'parameters'     => array('tracker_id'),
        ));
        $this->addParam(array(
            'name'           => 'artifact_id',
            'description'    => '--id=<artifact_id>           ID of the artifact the comment will be added to.',
            'parameters'     => array('id'),
        ));
        $this->addParam(array(
            'name'           => 'body',
            'description'    => '--message=<message>          The body message of the follow-up comment that will be added to the artifact.',
            'parameters'     => array('message'),
        ));
        $this->addParam(array(
            'name'           => 'comment_type_id',
            'description'    => '--comment_type_id=<ID>       The ID of the comment type to include into the comment.',
            'parameters'     => array('comment_type_id'),
        ));
        $this->addParam(array(
            'name'           => 'format',
            'description'    => '--format=<format>            The format within the comment will be posted (text/html).',
            'parameters'     => array('format'),
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
    function validate_body(&$body) {
        if (!$body) {
            exit_error("You must specify the message using the --message parameter");
        }
        return true;
    }
    function validate_format(&$format) {
        if ($format) {
            if (strtolower($format) == 'text') {
                $format = 0;
            } elseif (strtolower($format) == 'html') {
                $format = 1;
            } else {
                exit_error("The format of the comment may be text or HTML, --format parameter permitted values are 'text' or 'html'");
            }
        } else {
            $format = 0;
        }
        return true;
    }
}
