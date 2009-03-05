<?php

require_once('Widget.class.php');
require_once('common/include/CodeX_HTMLPurifier.class.php');

/**
* Widget_ProjectDescription
* 
* Copyright (c) Xerox Corporation, Codendi 2001-2009.
*
* @author  marc.nazarian@xrce.xerox.com
*/
class Widget_ProjectDescription extends Widget {
    public function __construct() {
        $this->Widget('projectdescription');
    }
    public function getTitle() {
        return $GLOBALS['Language']->getText('include_project_home','project_description');
    }
    public function getContent() {
        $request =& HTTPRequest::instance();
        $group_id = $request->get('group_id');
        $project =& project_get_object($group_id);
        $hp =& CodeX_HTMLPurifier::instance();
        
        if ($project->getStatus() == 'H') {
            echo '<p>' . $GLOBALS['Language']->getText('include_project_home','not_official_site',$GLOBALS['sys_name']) . '</p>';
        }
        
        if ($project->getDescription()) {
            echo "<p>" . $hp->purify($project->getDescription(), CODEX_PURIFIER_LIGHT, $group_id) . "</p>";
            $details_prompt = '[' . $GLOBALS['Language']->getText('include_project_home','more_info') . '...]';
        } else {
            echo '<p>' . $Language->getText('include_project_home','no_short_desc',"/project/admin/editgroupinfo.php?group_id=$group_id") . '</p>';
            $details_prompt = '[' . $GLOBALS['Language']->getText('include_project_home','other_info') . '...]';
        }
        
        echo '<a href="/project/showdetails.php?group_id='.$group_id.'"> ' . $details_prompt . '</a>';
        
    }
    public function canBeUsedByProject(&$project) {
        return true;
    }
}
?>