<?php

require_once('RegisterProjectStep.class.php');

/**
* RegisterProjectStep_Services
* 
* Allow the user to select services during registration process
* 
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* @author  N. Terray
*/
class RegisterProjectStep_Services extends RegisterProjectStep {
    function RegisterProjectStep_Services() {
        $this->RegisterProjectStep(
            $GLOBALS['Language']->getText('register_title', 'services', array($GLOBALS['sys_name'])),
            'CreatingANewProject.html'
        );
    }
    function display($data) {
        echo '<p>'. $GLOBALS['Language']->getText('register_services', 'desc') .'</p>';
        
        $em =& EventManager::instance();
        $em->processEvent("plugin_load_language_file", null);
        
        $p =& project_get_object($data['project']['built_from_template']);
        $title_arr=array();
        $title_arr[]=''; //$GLOBALS['Language']->getText('project_admin_editservice','enabled');
        $title_arr[]=$GLOBALS['Language']->getText('project_admin_editservice','s_label');
        $title_arr[]=$GLOBALS['Language']->getText('project_admin_editservice','s_desc');
        echo html_build_list_table_top($title_arr);
        $row_num = 0;
        foreach($p->services as $key => $nop) {
            if (!in_array($p->services[$key]->getShortName(), array('summary', 'admin')) && $p->services[$key]->isActive()) {
                $short_name  = $p->services[$key]->getShortName();
                $description = $p->services[$key]->getDescription();
                $label       = $p->services[$key]->getLabel();
                $id          = $p->services[$key]->getId();
                $is_used     = isset($data['project']['services'][$id]['is_used']) ?
                                $data['project']['services'][$id]['is_used'] :
                                $p->services[$key]->isUsed();
                $matches = array();
                if ($description == "service_".$short_name."_desc_key") {
                  $description = $GLOBALS['Language']->getText('project_admin_editservice',$description);
                }
                elseif(preg_match('/(.*):(.*)/', $description, $matches)) {
                    $description = $GLOBALS['Language']->getText($matches[1], $matches[2]);
                }
            
                if ($label == "service_".$short_name."_lbl_key") {
                  $label = $GLOBALS['Language']->getText('project_admin_editservice',$label);
                }
                elseif(preg_match('/(.*):(.*)/', $label, $matches)) {
                    $label = $GLOBALS['Language']->getText($matches[1], $matches[2]);
                }
                
                echo '<tr class="'. util_get_alt_row_color($row_num++) .'">';
                //{{{ is_used
                echo '<td>';
                $field_name = 'services['. $id .'][is_used]';
                $checked    = $is_used ? 'checked="checked"' : '';
                echo '<input type="hidden" name="'. $field_name .'" value="0" />';
                echo '<input type="checkbox" id="project_register_service_is_used_'. $id .'" name="'. $field_name .'" value="1" '. $checked .' />';
                echo '</td>';
                //}}}
                echo '<td>'. $label .'</td>';
                echo '<td>'. $description .'</td>';
                echo '</tr>';
            }
        }
        echo '</table>';
    }
    function onEnter($request, &$data) {
        return isset($data['project']['built_from_template']);
    }
    function onLeave($request, &$data) {
        $data['project']['services'] = $request->get('services');
        return $this->validate($data);
    }
    function validate($data) {
        if (!$data['project']['services']) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('register_projectname', 'info_missed'));
            return false;
        }
        return true;
    }
}

?>
