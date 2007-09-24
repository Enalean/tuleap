<?php

require_once('Widget.class.php');
require_once('www/my/my_utils.php');

/**
* Widget_MyBugs
* 
* Bugs assigned to or submitted by this person
* 
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* @author  N. Terray
*/
class Widget_MyBugs extends Widget {
    var $content;
    var $can_be_displayed;
    
    function Widget_MyBugs() {
        $this->Widget('mybugs');
        $this->can_be_displayed = true;
        $this->content = '';
        
        $sql='SELECT group_id,COUNT(bug_id) '.
            'FROM bug '.
            'WHERE status_id <> 3 '.
            'AND (assigned_to='.user_getid().
            ' OR submitted_by='.user_getid().') GROUP BY group_id ORDER BY group_id ASC LIMIT 100';
    
        $result=db_query($sql);
        $rows=db_numrows($result);
        
        if ($result && $rows >= 1) {
            $request =& HTTPRequest::instance();
            $this->can_be_displayed = true;
            $this->content .= '<table style="width:100%">';
            for ($j=0; $j<$rows; $j++) {
    
                $group_id = db_result($result,$j,'group_id');
        
                $sql2='SELECT bug_id,severity,assigned_to,submitted_by,date AS open_date,summary '.
                'FROM bug '.
                'WHERE group_id='.$group_id.' AND status_id <> 3 '.
                'AND (assigned_to='.user_getid().
                ' OR submitted_by='.user_getid().') LIMIT 100';
        
                $result2 = db_query($sql2);
                $rows2 = db_numrows($result2);
        
                $hide_item_id = $request->exist('hide_item_id') ? $request->get('hide_item_id') : null;
                $hide_bug     = $request->exist('hide_bug')     ? $request->get('hide_bug')     : null;
                list($hide_now,$count_diff,$hide_url) = my_hide_url('bug',$group_id,$hide_item_id,$rows2,$hide_bug);
                $html_hdr = ($j ? '<tr class="boxitem"><td colspan="2">' : '').
                    $hide_url.'<A HREF="/bugs/?group_id='.$group_id.'">'.
                    group_getname($group_id).'</A>&nbsp;&nbsp;&nbsp;&nbsp;';
                $html = '';
                $count_new = max(0, $count_diff);
                for ($i=0; $i<$rows2; $i++) {
        
                    if (!$hide_now) {
                        // Form the 'Submitted by/Assigned to flag' for marking
                        $AS_flag = my_format_as_flag(db_result($result2,$i,'assigned_to'), db_result($result2,$i,'submitted_by'));
            
                        $html .= '
                        
                        <TR class="'.get_priority_color(db_result($result2,$i,'severity')).
                        '"><TD class="small"><A HREF="/bugs/?func=detailbug&group_id='.
                        $group_id.'&bug_id='.db_result($result2,$i,'bug_id').
                        '">'.db_result($result2,$i,'bug_id').'</A></TD>'.
                        '<TD class="small">'.stripslashes(db_result($result2,$i,'summary')).'&nbsp;'.$AS_flag.'</TD></TR>';
        
                    }
                }
        
                $html_hdr .= my_item_count($rows2,$count_new).'</td></tr>';
                $this->content .= $html_hdr.$html;
            }
    
            $this->content .= '</table>';
        }
    }
    function getTitle() {
        return $GLOBALS['Language']->getText('my_index', 'my_bugs');
    }
    function getContent() {
        return $this->content;
    }
    function canBeDisplayed() {
        return $this->can_be_displayed;
    }
}
?>
