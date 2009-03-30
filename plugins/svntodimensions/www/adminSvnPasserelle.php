<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');  
require_once('../include/PluginSvntodimensionsLogDao.class.php');  
$GLOBALS['HTML']->includeJavascriptFile("/scripts/prototype/prototype.js");
session_require(array('group'=>'1','admin_flags'=>'A'));

$hp =& Codendi_HTMLPurifier::instance();

//delete log if func=delete
$request =& HTTPRequest::instance();
$vFunc = new Valid_WhiteList('func',array('delete'));
$vFunc->required();

if ($request->valid($vFunc) && $request->get('func') == 'delete') {
    $vLogId = new Valid_UInt('log_id');
    if ($request->valid($vLogId)) {
        $log_id_to_delete = $request->get('log_id');
        $logs_dao = new PluginSvntodimensionsLogDao(CodendiDataAccess::instance());
        $logs_result =& $logs_dao->delete($log_id);
        if(!$logs_result){
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_svntodimensions','admin_historic_error'));
        } else {
            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_svntodimensions','admin_historic_succes'));
        }
    }
}

$HTML->header(array('title'=>$GLOBALS['Language']->getText('plugin_svntodimensions','admin_historic_title')));


$output = '';
        
        $output .= '<h2>'.$GLOBALS['Language']->getText('plugin_svntodimensions','admin_historic').'</h2>';
        
        $titles = array();
        $titles[] = $GLOBALS['Language']->getText('plugin_svntodimensions','admin_historic_project');
        $titles[] = $GLOBALS['Language']->getText('plugin_svntodimensions','admin_historic_tag');
        $titles[] = $GLOBALS['Language']->getText('plugin_svntodimensions','admin_historic_dp');
        $titles[] = $GLOBALS['Language']->getText('plugin_svntodimensions','admin_historic_date');
        $titles[] = $GLOBALS['Language']->getText('plugin_svntodimensions','admin_historic_submission');
        $titles[] = $GLOBALS['Language']->getText('plugin_svntodimensions','admin_historic_state');
        $titles[] = $GLOBALS['Language']->getText('plugin_svntodimensions','admin_historic_delete');
        
        $output .= html_build_list_table_top($titles);
        
        $logs_dao = new PluginSvntodimensionsLogDao(CodendiDataAccess::instance());
        $logs_result =& $logs_dao->searchAll();
        
        $pm = ProjectManager::instance();
        
        $row_index = 0;
        while($logs_result->valid()){
            $row = $logs_result->current();
            $output .= '<tr class="'.html_get_alt_row_color($row_index).'" >';
            $group = $pm->getProject($row['group_id']);
            $short_name = $group->getUnixName();
            $output .= '<td>'.$short_name.'</td>';
            $output .= '<td>'.$row['tag'].'</td>';
            $output .= '<td>'.$row['design_part'].'</td>';
            $output .= '<td>'.date('Y-m-d H:i', $row['date']).'</td>';
            $output .= '<td>'.user_getname($row['user_id']).'</td>';
            if($row['state']==0){
                $output .= '<td>'.$GLOBALS['Language']->getText('plugin_svntodimensions','admin_historic_state_success').'</td>';
            }elseif($row['state']==1){
                $output .= '<td>'.$GLOBALS['Language']->getText('plugin_svntodimensions','admin_historic_state_inprogress').'</td>';
            }elseif($row['state']==4){
                $output .= '<td>'.$GLOBALS['Language']->getText('plugin_svntodimensions', 'error_transfert_cancel_dmcli_log', $row['error']).'</td>';
            }else{
                $output .= '<td>'.$row['error'].'</td>';
            }
            $output .= '<td align="center">' ;
            
            
            $output .= '<a href="adminSvnPasserelle.php?func=delete&amp;log_id='. $row['log_id'].'" onclick="return confirm(\''.  $hp->purify($GLOBALS['Language']->getText('plugin_svntodimensions', 'admin_historic_warn'), CODENDI_PURIFIER_CONVERT_HTML)  .'\');">'
                           // . $GLOBALS['HTML']->getImage('./themes/default/images/delete.png', array('alt'=> $hp->purify($GLOBALS['Language']->getText('plugin_svntodimensions', 'admin_historic_delete'), CODENDI_PURIFIER_CONVERT_HTML) , 'title'=>  $hp->purify($GLOBALS['Language']->getText('plugin_svntodimensions', 'admin_historic_delete'), CODENDI_PURIFIER_CONVERT_HTML) )) .'</a>';
            
           .'<IMG SRC="./themes/default/images/delete.png" HEIGHT="16" WIDTH="16" BORDER="0"></A></td>';
            $output .= '</tr>';
            $row_index ++;
            $logs_result->next();
        }
        $output .= '</table>';
        
        print $output;

$HTML->footer(array());

?>
