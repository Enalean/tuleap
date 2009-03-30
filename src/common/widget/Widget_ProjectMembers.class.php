<?php

require_once('Widget.class.php');
require_once('common/event/EventManager.class.php');

/**
* Widget_ProjectMembers
* 
* Copyright (c) Xerox Corporation, Codendi 2001-2009.
*
* @author  marc.nazarian@xrce.xerox.com
*/
class Widget_ProjectMembers extends Widget {
    public function __construct() {
        $this->Widget('projectmembers');
    }
    public function getTitle() {
        return $GLOBALS['Language']->getText('include_project_home','devel_info');
    }
    public function getContent() {
        $request =& HTTPRequest::instance();
        $group_id = $request->get('group_id');
        $pm = ProjectManager::instance();
        $project = $pm->getProject($group_id);
        
        $res_admin = db_query("SELECT user.user_id AS user_id,user.user_name AS user_name, user.realname as realname "
                            . "FROM user,user_group "
                            . "WHERE user_group.user_id=user.user_id AND user_group.group_id=".db_ei($group_id)." AND "
                            . "user_group.admin_flags = 'A'");
        if (db_numrows($res_admin) > 0) {
            $user_helper = new UserHelper();
            $em = EventManager::instance();
            echo '<span class="develtitle">' . $GLOBALS['Language']->getText('include_project_home','proj_admins').':</span><br />';
            while ($row_admin = db_fetch_array($res_admin)) {
                $display_name = '';
                $em->processEvent('get_user_display_name', array(
                          'user_id'           => $row_admin['user_id'],
                          'user_name'         => $row_admin['user_name'],
                          'realname'          => $row_admin['realname'],
                          'user_display_name' => &$display_name
                      ));
                if (!$display_name) {
                    $display_name = $user_helper->getDisplayNameFromUserId($row_admin['user_id']);
                }
                echo '<a href="/users/'.$row_admin['user_name'].'/">'. $display_name .'</a><br />';
            }
            echo '<hr width="100%" size="1" NoShade>';                   
        }
        echo '<span class="develtitle">' . $GLOBALS['Language']->getText('include_project_home','devels') . ':</span><br />';
        // count of developers on this project
        $res_count = db_query("SELECT user_id FROM user_group WHERE group_id=".db_ei($group_id));
        echo db_numrows($res_count);
        echo ' <a href="/project/memberlist.php?group_id=' . $group_id . '">[' . $GLOBALS['Language']->getText('include_project_home','view_members') . ']</a>';               
    }
    public function canBeUsedByProject(&$project) {
        return true;
    }
}
?>