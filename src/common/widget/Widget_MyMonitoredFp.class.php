<?php

require_once('Widget.class.php');

/**
* Widget_MyMonitoredFp
* 
* Filemodules that are actively monitored
* 
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* @author  N. Terray
*/
class Widget_MyMonitoredFp extends Widget {
    function Widget_MyMonitoredFp() {
        $this->Widget('mymonitoredfp');
    }
    function _getTitle() {
        return $GLOBALS['Language']->getText('my_index', 'my_files');
    }
    function _getContent() {
        $html_my_monitored_fp = '';
        $sql="SELECT groups.group_name,groups.group_id ".
            "FROM groups,filemodule_monitor,frs_package ".
            "WHERE groups.group_id=frs_package.group_id ".
            "AND frs_package.package_id=filemodule_monitor.filemodule_id ".
            "AND filemodule_monitor.user_id='".user_getid()."' GROUP BY group_id ORDER BY group_id ASC LIMIT 100";
    
        $result=db_query($sql);
        $rows=db_numrows($result);
        if (!$result || $rows < 1) {
            $html_my_monitored_fp .= $GLOBALS['Language']->getText('my_index', 'my_files_msg');
            $html_my_monitored_fp .= db_error();
        } else {
            $html_my_monitored_fp .= '<table style="width:100%">';
            $request =& HTTPRequest::instance();
            for ($j=0; $j<$rows; $j++) {
                $group_id = db_result($result,$j,'group_id');
        
                $sql2="SELECT frs_package.name,filemodule_monitor.filemodule_id ".
                    "FROM groups,filemodule_monitor,frs_package ".
                    "WHERE groups.group_id=frs_package.group_id ".
                    "AND groups.group_id=$group_id ".
                    "AND frs_package.package_id=filemodule_monitor.filemodule_id ".
                    "AND filemodule_monitor.user_id='".user_getid()."'  LIMIT 100";
                $result2 = db_query($sql2);
                $rows2 = db_numrows($result2);
        
                $hide_item_id = $request->exist('hide_item_id') ? $request->get('hide_item_id') : null;
                $hide_frs     = $request->exist('hide_frs')     ? $request->get('hide_frs')     : null;
                list($hide_now,$count_diff,$hide_url) = my_hide_url('frs',$group_id,$hide_item_id,$rows2,$hide_frs);
        
                $html_hdr = ($j ? '<tr class="boxitem"><td colspan="2">' : '').
                    $hide_url.'<A HREF="/project/?group_id='.$group_id.'">'.
                    db_result($result,$j,'group_name').'</A>&nbsp;&nbsp;&nbsp;&nbsp;';
        
                $html = '';
                $count_new = max(0, $count_diff);
                for ($i=0; $i<$rows2; $i++) {
                    if (!$hide_now) {
                        $html .='
                        <TR class="'. util_get_alt_row_color($i) .'">'.
                            '<TD WIDTH="99%">&nbsp;&nbsp;&nbsp;&nbsp;-&nbsp;<A HREF="/file/showfiles.php?group_id='.$group_id.'">'.
                            db_result($result2,$i,'name').'</A></TD>'.
                            '<TD><A HREF="/file/filemodule_monitor.php?filemodule_id='.
                            db_result($result2,$i,'filemodule_id').
                            '" onClick="return confirm(\''.$GLOBALS['Language']->getText('my_index', 'stop_file').'\')">'.
                            '<IMG SRC="'.util_get_image_theme("ic/trash.png").'" HEIGHT="16" WIDTH="16" '.
                            'BORDER=0" ALT="'.$GLOBALS['Language']->getText('my_index', 'stop_monitor').'"></A></TD></TR>';
                    }
                }
                
                $html_hdr .= my_item_count($rows2,$count_new).'</td></tr>';
                $html_my_monitored_fp .= $html_hdr .$html;
            }
            $html_my_monitored_fp .= '</table>';
        }
        return $html_my_monitored_fp;
    }
}
?>
