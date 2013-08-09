<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('account.php');

require_once('RegisterProjectStep.class.php');

/**
* RegisterProjectStep_Name
*/
class RegisterProjectStep_Name extends RegisterProjectStep {
    function RegisterProjectStep_Name() {
        $this->RegisterProjectStep(
            $GLOBALS['Language']->getText('register_title', 'name', array($GLOBALS['sys_name'])),
            'new-project.html'
        );
    }
    function display($data) {
        $hp = Codendi_HTMLPurifier::instance();
        $full_name =  $hp->purify(isset($data['project']['form_full_name']) ? $data['project']['form_full_name'] : '', CODENDI_PURIFIER_CONVERT_HTML) ;
        $unix_name =  $hp->purify(isset($data['project']['form_unix_name']) ? $data['project']['form_unix_name'] : '', CODENDI_PURIFIER_CONVERT_HTML) ;
        include($GLOBALS['Language']->getContent('project/projectname'));
    }
    function onLeave($request, &$data) {
        $data['project']['form_full_name'] = trim($request->get('form_full_name'));
        $data['project']['form_unix_name'] = $request->get('form_unix_name');
        return $this->validate($data);
    }
    function validate($data) {
        $is_valid = true;
        if (!$data['project']['form_full_name'] || !$data['project']['form_unix_name']) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('register_projectname', 'info_missed'));
            $is_valid = false;
        } else {
            //check for valid group name
            $form_unix_name = $data['project']['form_unix_name'];
            $rule = new Rule_ProjectName();
            if (!$rule->isValid($form_unix_name)) {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('register_license','invalid_short_name'));
                $GLOBALS['Response']->addFeedback('error', $rule->getErrorMessage());
                $is_valid = false;
            } else {
                $form_full_name = $data['project']['form_full_name'];
                $rule = new Rule_ProjectFullName();
                if (!$rule->isValid($form_full_name)) {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('register_license','invalid_full_name'));
                    $GLOBALS['Response']->addFeedback('error', $rule->getErrorMessage());
                    $is_valid = false;
                }
            }
        }
        return $is_valid;
    }
}

?>
