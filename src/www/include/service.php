<?php

/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
* 
* 
* 
*/
require_once 'common/project/ProjectManager.class.php';

function service_create_service($arr, $group_id, $template, $force_enable = false) {
    // Convert link to real values
    // NOTE: if you change link variables here, change them also in src/www/project/admin/servicebar.php and src/www/include/Layout.class.php
    $link=$arr['link'];
    $pm = ProjectManager::instance();
    if ($template['system']) {
        $link=str_replace('$projectname',$pm->getProject($group_id)->getUnixName(),$link);
        $link=str_replace('$sys_default_domain',$GLOBALS['sys_default_domain'],$link);
        $link=str_replace('$group_id',$group_id,$link);
        if ($GLOBALS['sys_force_ssl']) {
            $sys_default_protocol='https'; 
        } else { $sys_default_protocol='http'; }
        $link=str_replace('$sys_default_protocol',$sys_default_protocol,$link);
    } else {
        //for non-system templates
        $link = service_replace_template_name_in_link($link, $template, $pm->getProject($group_id));
    }

    $is_used   = isset($template['is_used'])   ? $template['is_used']   : $arr['is_used'];
    $server_id = isset($template['server_id']) ? $template['server_id'] : $arr['server_id'];
    $sql    = "INSERT INTO service (group_id, label, description, short_name, link, is_active, is_used, scope, rank, location, server_id, is_in_iframe) VALUES (".db_ei($group_id).", '".db_es($arr['label'])."', '".db_es($arr['description'])."', '".db_es($arr['short_name'])."', '".db_es($link)."', ".db_ei($arr['is_active']).", ". ($force_enable ? 1 : db_ei($is_used)) .", '".db_es($arr['scope'])."', ".db_ei($arr['rank']).",  '".db_es($arr['location'])."', ". db_ei($server_id) .", ". db_ei($arr['is_in_iframe']) .")";
    $result = db_query($sql);
    
    if ($result) {
        // activate corresponding references
        $reference_manager =& ReferenceManager::instance();
	    if ($arr['short_name'] != "") {
	      $reference_manager->addSystemReferencesForService($template['id'],$group_id,$arr['short_name']);
        }
        return true;
    } else {
        return false;
    }
}

/**
 * @return string
 */
function service_replace_template_name_in_link($link, array $template , Project $project) {
    $link = preg_replace('#(/www/|/projects/|group=)'.$template['name'].'(/|&|$)#','$1'.$project->getUnixName().'$2',$link);
    $link = preg_replace('/group_id='. $template['id'] .'([^\d]|$)/', 'group_id='. $project->getGroupId() .'$1', $link);
    EventManager::instance()->processEvent(
        Event::SERVICE_REPLACE_TEMPLATE_NAME_IN_LINK,
        array(
            'link'     => &$link,
            'template' => $template,
            'project'  => $project
        )
    );
    return $link;
}
?>
