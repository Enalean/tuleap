<?php

require_once('RegisterProjectStep.class.php');

/**
* RegisterProjectStep_Confirmation
* 
* TODO: description
* 
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* @author  N. Terray
*/
class RegisterProjectStep_Confirmation extends RegisterProjectStep {
    function RegisterProjectStep_Confirmation() {
        $this->RegisterProjectStep(
            $GLOBALS['Language']->getText('register_title', 'confirm', array($GLOBALS['sys_name'])),
            'CreatingANewProject.html'
        );
    }
    function display($data) {
        global $Language;
        require('vars.php');
        include($GLOBALS['Language']->getContent('project/confirmation'));
    }
    function onLeave($request, &$data) {
        //Arggh! Copy paste from steps. Must be enhanced !!!!
        $data['project']['form_short_description'] = $request->get('form_short_description');
        $data['project']['form_purpose']           = $request->get('form_purpose');
        $data['project']['form_required_sw']       = $request->get('form_required_sw');
        $data['project']['form_patents']           = $request->get('form_patents');
        $data['project']['form_comments']          = $request->get('form_comments');
        
        $data['project']['form_license']       = $request->get('form_license');
        $data['project']['form_license_other'] = $request->get('form_license_other');
        
        $data['project']['form_full_name'] = $request->get('form_full_name');
        
        return true;
    }
}
?>
