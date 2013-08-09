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

require_once('RegisterProjectStep.class.php');

/**
* RegisterProjectStep_License
*/
class RegisterProjectStep_License extends RegisterProjectStep {
    function RegisterProjectStep_License() {
        $this->RegisterProjectStep(
            $GLOBALS['Language']->getText('register_title', 'license', array($GLOBALS['sys_name'])),
            'new-project.html'
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
