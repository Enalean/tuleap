<?php

/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
* 
* 
* 
*/

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
