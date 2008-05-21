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
