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
* RegisterProjectStep_BasicInfo
* 
* Ask the user basic information about the new project
* 
*/
class RegisterProjectStep_BasicInfo extends RegisterProjectStep {
    function RegisterProjectStep_BasicInfo() {
        $this->RegisterProjectStep(
            $GLOBALS['Language']->getText('register_title', 'basic'),
            'new-project.html'
        );
    }
    function display($data) {
        include($GLOBALS['Language']->getContent('project/basinfo'));
        require_once('RegisterProjectDescription.php');
    }
    function onLeave($request, &$data) {
    	$descfieldsinfos = getProjectsDescFieldsInfos();
    	
    	for($i=0;$i<sizeof($descfieldsinfos);$i++){
    		
    		$data['project']["form_".$descfieldsinfos[$i]["group_desc_id"]] = $request->get("form_".$descfieldsinfos[$i]["group_desc_id"]);
    		
    	}
    	$data['project']['form_short_description'] = $request->get('form_short_description');
        return $this->validate($data);
    }
    function validate($data) {
    	//Check missing parameters
        if (!$data['project']['form_short_description']) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('register_projectname', 'info_missed'));
            return false;
        }
        
		$descfieldsinfos = getProjectsDescFieldsInfos();
		for($i=0;$i<sizeof($descfieldsinfos);$i++){
    		
    		if ( ($descfieldsinfos[$i]['desc_required']==1) && (!$data['project']["form_".$descfieldsinfos[$i]["group_desc_id"]]) ) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('register_projectname', 'info_missed'));
            return false;
       		}
    	}
		
        
        return true;
    }
}

?>
