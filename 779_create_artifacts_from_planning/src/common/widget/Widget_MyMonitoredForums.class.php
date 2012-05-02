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

require_once('Widget.class.php');

/**
* Widget_MyMonitoredForums
* 
* Forums that are actively monitored
*/
class Widget_MyMonitoredForums extends Widget {
    function Widget_MyMonitoredForums() {
        $this->Widget('mymonitoredforums');
    }
    function getTitle() {
        return $GLOBALS['Language']->getText('my_index', 'my_forums');
    }
    function getContent() {
        $html_my_monitored_forums = '';
        $sql="SELECT groups.group_id, groups.group_name ".
             "FROM groups,forum_group_list,forum_monitored_forums ".
             "WHERE groups.group_id=forum_group_list.group_id ".
             "AND groups.status = 'A' ".
            "AND forum_group_list.is_public <> 9 ".
             "AND forum_group_list.group_forum_id=forum_monitored_forums.forum_id ".
             "AND forum_monitored_forums.user_id='".user_getid()."' ";
        $um =& UserManager::instance();
        $current_user =& $um->getCurrentUser();
        if ($current_user->isRestricted()) {
            $projects = $current_user->getProjects();
            $sql .= "AND groups.group_id IN (". implode(',', $projects) .") ";
        }
        $sql .= "GROUP BY group_id ORDER BY group_id ASC LIMIT 100";
    
        $result=db_query($sql);
        $rows=db_numrows($result);
        if (!$result || $rows < 1) {
            $html_my_monitored_forums .= $GLOBALS['Language']->getText('my_index', 'my_forums_msg');
        } else {
            $request =& HTTPRequest::instance();
            $html_my_monitored_forums .= '<table style="width:100%">';
            for ($j=0; $j<$rows; $j++) {
                $group_id = db_result($result,$j,'group_id');
        
                $sql2="SELECT forum_group_list.group_forum_id,forum_group_list.forum_name ".
                    "FROM groups,forum_group_list,forum_monitored_forums ".
                    "WHERE groups.group_id=forum_group_list.group_id ".
                    "AND groups.group_id=$group_id ".
                    "AND forum_group_list.is_public <> 9 ".
                    "AND forum_group_list.group_forum_id=forum_monitored_forums.forum_id ".
                    "AND forum_monitored_forums.user_id='".user_getid()."' LIMIT 100";
        
                $result2 = db_query($sql2);
                $rows2 = db_numrows($result2);

                $vItemId = new Valid_UInt('hide_item_id');
                $vItemId->required();
                if($request->valid($vItemId)) {
                    $hide_item_id = $request->get('hide_item_id');
                } else {
                    $hide_item_id = null;
                }

                $vForum = new Valid_WhiteList('hide_forum', array(0, 1));
                $vForum->required();
                if($request->valid($vForum)) {
                    $hide_forum = $request->get('hide_forum');
                } else {
                    $hide_forum = null;
                }

                list($hide_now,$count_diff,$hide_url) = my_hide_url('forum',$group_id,$hide_item_id,$rows2,$hide_forum);
        
                $html_hdr = ($j ? '<tr class="boxitem"><td colspan="2">' : '').
                    $hide_url.'<A HREF="/forum/?group_id='.$group_id.'">'.
                    db_result($result,$j,'group_name').'</A>&nbsp;&nbsp;&nbsp;&nbsp;';
        
                $html = '';
                $count_new = max(0, $count_diff);
                for ($i=0; $i<$rows2; $i++) {
        
                    if (!$hide_now) {
        
                    $group_forum_id = db_result($result2,$i,'group_forum_id');
                    $html .= '
                    <TR class="'. util_get_alt_row_color($i) .'"><TD WIDTH="99%">'.
                        '&nbsp;&nbsp;&nbsp;-&nbsp;<A HREF="/forum/forum.php?forum_id='.$group_forum_id.'">'.
                        stripslashes(db_result($result2,$i,'forum_name')).'</A></TD>'.
                        '<TD ALIGN="center"><A HREF="/my/stop_monitor.php?forum_id='.$group_forum_id.
                        '" onClick="return confirm(\''.$GLOBALS['Language']->getText('my_index', 'stop_forum').'\')">'.
                        '<IMG SRC="'.util_get_image_theme("ic/trash.png").'" HEIGHT="16" WIDTH="16" '.
                        'BORDER=0 ALT="'.$GLOBALS['Language']->getText('my_index', 'stop_monitor').'"></A></TD></TR>';
                    }
                }
        
                $html_hdr .= my_item_count($rows2,$count_new).'</td></tr>';
                $html_my_monitored_forums .= $html_hdr.$html;
            }
            $html_my_monitored_forums .= '</table>';
        }
        return $html_my_monitored_forums;
    }
    
    function getCategory() {
        return 'forums';
    }
    function getDescription() {
        return $GLOBALS['Language']->getText('widget_description_my_monitored_forums','description');;
    }
    function isAjax() {
        return true;
    }
    function getAjaxUrl($owner_id, $owner_type) {
        $request =& HTTPRequest::instance();
        $ajax_url = parent::getAjaxUrl($owner_id, $owner_type);
        if ($request->exist('hide_item_id') || $request->exist('hide_forum')) {
            $ajax_url .= '&hide_item_id=' . $request->get('hide_item_id') . '&hide_forum=' . $request->get('hide_forum');
        }
        return $ajax_url;
    }
}
?>