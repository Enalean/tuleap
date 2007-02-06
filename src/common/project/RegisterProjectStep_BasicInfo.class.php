<?php

require_once('RegisterProjectStep.class.php');

/**
* RegisterProjectStep_BasicInfo
* 
* Ask the user basic information about the new project
* 
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* @author  author
*/
class RegisterProjectStep_BasicInfo extends RegisterProjectStep {
    function RegisterProjectStep_BasicInfo() {
        $this->RegisterProjectStep(
            $GLOBALS['Language']->getText('register_title', 'basic'),
            'CreatingANewProject.html'
        );
    }
    function display($data) {
        include($GLOBALS['Language']->getContent('project/basinfo'));
    }
    function onLeave($request, &$data) {
        $data['project']['form_short_description'] = $request->get('form_short_description');
        $data['project']['form_purpose']           = $request->get('form_purpose');
        $data['project']['form_required_sw']       = $request->get('form_required_sw');
        $data['project']['form_patents']           = $request->get('form_patents');
        $data['project']['form_comments']          = $request->get('form_comments');
        return $this->validate($data);
    }
    function validate($data) {
        //Check missing parameters
        if (!$data['project']['form_short_description'] || !$data['project']['form_purpose']) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('register_projectname', 'info_missed'));
            return false;
        }
        return true;
    }
}

?>
