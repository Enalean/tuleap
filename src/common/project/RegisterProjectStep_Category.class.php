<?php

require_once('trove.php');

require_once('RegisterProjectStep.class.php');

/**
* RegisterProjectStep_Category
* 
* TODO: description
* 
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* @author  N. Terray
*/
class RegisterProjectStep_Category extends RegisterProjectStep {
    function RegisterProjectStep_Category() {
        $this->RegisterProjectStep(
            $GLOBALS['Language']->getText('register_title', 'category', array($GLOBALS['sys_name'])),
            'CreatingANewProject.html'
        );
    }
    function display($data) {
        $group_id = $data['project']['built_from_template'];
        include($GLOBALS['Language']->getContent('project/category'));
    }
    function onLeave($request, &$data) {
        $data['project']['trove'] = array();
        if ($request->exist('root1')) {
            $root1 = $request->get('root1');
            if (is_array($root1)) {
                foreach($root1 as $rootnode => $value) {
                    for($i = 1 ; $i <= $GLOBALS['TROVE_MAXPERROOT'] ; $i++) {
                        $trove = $request->get('root'. $i);
                        $value = $trove[$rootnode];
                        if ($value) {
                            $data['project']['trove'][$rootnode][] = $value;
                        }
                    }
                }
            }
        }
        return true;
    }
}

?>
