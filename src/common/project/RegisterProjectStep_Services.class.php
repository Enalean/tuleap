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
require_once('common/server/ServerFactory.class.php');

/**
* RegisterProjectStep_Services
* 
* Allow the user to select services during registration process
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
        
        $sf = new ServerFactory();
        $servers = $sf->getAllServers();
        $can_display_servers = count($servers) > 1;
        
        $pm = ProjectManager::instance();
        $p = $pm->getProject($data['project']['built_from_template']);
        $server_head = '';
        if ($can_display_servers) {
            $server_head = '<th>'. $GLOBALS['Language']->getText('register_services','server') .'</th>';
        }
        echo '<table class="table table-striped table-bordered table-condensed table-hover">
            <thead>
                <tr>
                    <th>&nbsp;</th>
                    <th>'. $GLOBALS['Language']->getText('project_admin_editservice','s_label') .'</th>
                    <th>'. $GLOBALS['Language']->getText('project_admin_editservice','s_desc') .'</th>
                    '. $server_head .'
                </tr>
            </thead>
            <tbody>';
        
        foreach($p->services as $key => $nop) {
            if (!in_array($p->services[$key]->getShortName(), array('summary', 'admin')) && $p->services[$key]->isActive() && !$p->services[$key]->isRestricted()) {
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
                
                echo '<tr>';
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
                //{{{ server
                if ($can_display_servers) {
                    echo '<td style="text-align:center">';
                    if ($short_name == 'svn' || $short_name == 'file') {
                        echo '<select name="services['. $id .'][server_id]">';
                        foreach($servers as $server_key => $nop) {
                            $selected = $servers[$server_key]->getId() == $p->services[$key]->getServerId() ? 'selected="selected"' : '';
                            echo '<option value="'. $servers[$server_key]->getId() .'" '. $selected .'>'. $servers[$server_key]->getName() .'</option>';
                        }
                        echo '</select>';
                    } else {
                        echo '-';
                        echo '<input type="hidden" name="services['. $id .'][server_id]" value="'. $p->services[$key]->getServerId() .'" />';
                    }
                    echo '</td>';
                }
                //}}}
                echo '</tr>';
            }
        }
        echo '</tbody></table>';
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
