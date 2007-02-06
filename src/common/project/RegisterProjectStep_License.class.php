<?php

require_once('RegisterProjectStep.class.php');

/**
* RegisterProjectStep_License
* 
* TODO: description
* 
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* @author  N. Terray
*/
class RegisterProjectStep_License extends RegisterProjectStep {
    function RegisterProjectStep_License() {
        $this->RegisterProjectStep(
            $GLOBALS['Language']->getText('register_title', 'license', array($GLOBALS['sys_name'])),
            'CreatingANewProject.html'
        );
    }
    function display() {
        global $Language;
        require('vars.php');
        include($GLOBALS['Language']->getContent('project/license'));
    }
    function onLeave($request, &$data) {
        $data['project']['form_license']       = $request->get('form_license');
        $data['project']['form_license_other'] = $request->get('form_license_other');
        return $this->validate($data);
    }
    function validate($data) {
        if (!$data['project']['form_license']) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('register_projectname', 'info_missed'));
            return false;
        }
        return true;
    }
}

?>
