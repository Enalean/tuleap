<?php

/**
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
* 
* $Id$
* 
*/

function service_create_service($arr, $group_id, $template, $force_enable = false) {
    // Convert link to real values
    // NOTE: if you change link variables here, change them also in src/www/project/admin/servicebar.php and src/www/include/Layout.class.php
    $link=$arr['link'];
    if ($template['system']) {
        $link=str_replace('$projectname',group_getunixname($group_id),$link);
        $link=str_replace('$sys_default_domain',$GLOBALS['sys_default_domain'],$link);
        $link=str_replace('$group_id',$group_id,$link);
        if ($GLOBALS['sys_force_ssl']) {
            $sys_default_protocol='https'; 
        } else { $sys_default_protocol='http'; }
        $link=str_replace('$sys_default_protocol',$sys_default_protocol,$link);
    } else {
      //for non-system templates
      $link=str_replace($template['name'],group_getunixname($group_id),$link);
      $link=preg_replace('/group_id='. $template['id'] .'([^\d]|$)/', 'group_id='. $group_id .'$1', $link);
    }

    $sql    = "INSERT INTO service (group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES ($group_id, '".$arr['label']."', '".$arr['description']."', '".$arr['short_name']."', '".$link."', ".$arr['is_active'].", ". ($force_enable ? 1 : $arr['is_used']) .", '".$arr['scope']."', ".$arr['rank'].")";
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
?>
