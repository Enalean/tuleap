<?php

require_once('Widget.class.php');
require_once('WidgetLayoutManager.class.php');
require_once('www/my/my_utils.php');

/**
* Widget_MyTasks
* 
* Tasks assigned to me
* 
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* @author  N. Terray
*/
class Widget_MyTasks extends Widget {
    var $content;
    var $can_be_displayed;
    
    function Widget_MyTasks() {
        $this->Widget('mytasks');
        $this->content = '';
        $lm =& new WidgetLayoutManager();
        $this->setOwner(user_getid(), $lm->OWNER_TYPE_USER);
        $last_group=0;
    
        $sql = 'SELECT groups.group_id, groups.group_name, project_group_list.group_project_id, project_group_list.project_name '.
            'FROM groups,project_group_list,project_task,project_assigned_to '.
            'WHERE project_task.project_task_id=project_assigned_to.project_task_id '.
            'AND project_assigned_to.assigned_to_id='.user_getid().
            ' AND project_task.status_id=1 AND project_group_list.group_id=groups.group_id '.
            "AND project_group_list.is_public!='9' ".
          'AND project_group_list.group_project_id=project_task.group_project_id GROUP BY group_id,group_project_id';
    
        $result=db_query($sql);
        $rows=db_numrows($result);
    
        if ($result && $rows >= 1) {
            $request =& HTTPRequest::instance();
            $this->content .= '<table style="width:100%">';
            for ($j=0; $j<$rows; $j++) {
    
                $group_id = db_result($result,$j,'group_id');
                $group_project_id = db_result($result,$j,'group_project_id');
        
                $sql2 = 'SELECT project_task.project_task_id, project_task.priority, project_task.summary,project_task.percent_complete '.
                    'FROM groups,project_group_list,project_task,project_assigned_to '.
                    'WHERE project_task.project_task_id=project_assigned_to.project_task_id '.
                    "AND project_assigned_to.assigned_to_id='".user_getid()."' AND project_task.status_id='1'  ".
                    'AND project_group_list.group_id=groups.group_id '.
                    "AND groups.group_id=$group_id ".
                    'AND project_group_list.group_project_id=project_task.group_project_id '.
                    "AND project_group_list.is_public!='9' ".
                   "AND project_group_list.group_project_id= $group_project_id LIMIT 100";
        
        
                $result2 = db_query($sql2);
                $rows2 = db_numrows($result2);
        
                $hide_item_id = $request->exist('hide_item_id') ? $request->get('hide_item_id') : null;
                $hide_pm      = $request->exist('hide_pm')      ? $request->get('hide_pm')      : null;
                list($hide_now,$count_diff,$hide_url) = my_hide_url('pm',$group_project_id,$hide_item_id,$rows2,$hide_pm);
        
                $html_hdr = ($j ? '<tr class="boxitem"><td colspan="3">' : '').
                    $hide_url.'<A HREF="/pm/task.php?group_id='.$group_id.
                    '&group_project_id='.$group_project_id.'">'.
                    db_result($result,$j,'group_name').' - '.
                    db_result($result,$j,'project_name').'</A>&nbsp;&nbsp;&nbsp;&nbsp;';
                $html = '';
                $count_new = max(0, $count_diff);
                for ($i=0; $i<$rows2; $i++) {
                    
                    if (!$hide_now) {
        
                    $html .= '
                    <TR class="'.get_priority_color(db_result($result2,$i,'priority')).
                        '"><TD class="small"><A HREF="/pm/task.php/?func=detailtask&project_task_id='.
                        db_result($result2, $i, 'project_task_id').'&group_id='.
                        $group_id.'&group_project_id='.$group_project_id.
                        '">'.db_result($result2,$i,'project_task_id').'</A></TD>'.
                        '<TD class="small">'.stripslashes(db_result($result2,$i,'summary')).'</TD>'.
                        '<TD class="small">'.(db_result($result2,$i,'percent_complete')-1000).'%</TD></TR>';
        
                    }
                }
        
                $html_hdr .= my_item_count($rows2,$count_new).'</td></tr>';
                $this->content .= $html_hdr.$html;
            }
            $this->content .= '</table>';
        }
    }
    function getTitle() {
        return $GLOBALS['Language']->getText('my_index', 'my_tasks');
    }
    function getContent() {
        return $this->content;
    }
    function isAvailable() {
        $sql = "SELECT s.short_name
        FROM groups g, user_group ug, service s
        WHERE g.group_id = ug.group_id
        AND g.group_id = s.group_id
        AND ug.user_id = ". $this->owner_id ."
        AND g.status = 'A'
        AND s.short_name = 'task'
        AND s.is_used = 1
        AND s.is_active = 1
        LIMIT 1";
        $result=db_query($sql);
        return $result && db_numrows($result) > 0;
    }
}
?>
