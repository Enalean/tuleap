<?php

require_once('account.php');

require_once('RegisterProjectStep.class.php');

/**
* RegisterProjectStep_Name
* 
* TODO: description
* 
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* @author  N. Terray
* @version $Id$
*/
class RegisterProjectStep_Name extends RegisterProjectStep {
    function RegisterProjectStep_Name() {
        $this->RegisterProjectStep(
            $GLOBALS['Language']->getText('register_title', 'name', array($GLOBALS['sys_name'])),
            'CreatingANewProject.html'
        );
    }
    function display($data) {
        $full_name = htmlentities(isset($data['project']['form_full_name']) ? $data['project']['form_full_name'] : '', ENT_QUOTES);
        $unix_name = htmlentities(isset($data['project']['form_unix_name']) ? $data['project']['form_unix_name'] : '', ENT_QUOTES);
        include($GLOBALS['Language']->getContent('project/projectname'));
    }
    function onLeave($request, &$data) {
        $data['project']['form_full_name'] = $request->get('form_full_name');
        $data['project']['form_unix_name'] = $request->get('form_unix_name');
        return $this->validate($data);
    }
    function validate($data) {
        $is_valid = false;
        if (!$data['project']['form_full_name'] || !$data['project']['form_unix_name']) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('register_projectname', 'info_missed'));
        } else {
            //check for valid group name
            $form_unix_name = strtolower($data['project']['form_unix_name']);
            if (!account_groupnamevalid($form_unix_name)) {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('register_license','invalid_short_name'));
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['register_error']);
            } else {
                if (db_numrows(db_query("SELECT group_id FROM groups WHERE unix_group_name LIKE '$form_unix_name'")) > 0) {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('register_license','g_name_exist'));
                } else {
                    $is_valid = true;
                }
            }
        }
        return $is_valid;
    }
}

?>
