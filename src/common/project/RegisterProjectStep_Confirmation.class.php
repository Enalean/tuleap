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
* RegisterProjectStep_Confirmation
*/
class RegisterProjectStep_Confirmation extends RegisterProjectStep {
    function RegisterProjectStep_Confirmation() {
        $this->RegisterProjectStep(
            $GLOBALS['Language']->getText('register_title', 'confirm', array($GLOBALS['sys_name'])),
            'new-project.html'
        );
    }
    function display($data) {
        global $Language;
        require('vars.php');
        require_once('RegisterProjectDescription.php');
        include($GLOBALS['Language']->getContent('project/confirmation'));
    }
    function onLeave($request, &$data) {
        //Arggh! Copy paste from steps. Must be enhanced !!!!
        $data['project']['form_short_description'] = $request->get('form_short_description');
        
        $descfieldsinfos = getProjectsDescFieldsInfos();
    	
    	for($i=0;$i<sizeof($descfieldsinfos);$i++){
    		
    		$data['project']["form_".$descfieldsinfos[$i]["group_desc_id"]] = $request->get("form_".$descfieldsinfos[$i]["group_desc_id"]);
    		
    	}
        
        $data['project']['form_license']       = $request->get('form_license');
        $data['project']['form_license_other'] = $request->get('form_license_other');
        
        $data['project']['form_full_name'] = trim($request->get('form_full_name'));
        $data['project']['form_unix_name'] = $request->get('form_unix_name');
        
        return true;
    }
}
?>
