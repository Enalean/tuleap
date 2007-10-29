<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001. All Rights Reserved
// http://codex.xerox.com
//
//  
require_once('pre.php');
require('../admin/project_admin_utils.php');
require('./source_code_access_utils.php');
require('www/project/export/access_logs_export.php');

$Language->loadLanguageMsg('project/project');

// Only for project administrators
session_require(array('group'=>$group_id,'admin_flags'=>'A'));

if ( !$group_id ) {
	exit_error($Language->getText('project_admin_userperms','invalid_g'),$Language->getText('project_admin_userperms','group_not_exist'));
}
$project=new Project($group_id);

if (isset($_REQUEST['SUBMIT'])) {    
        
    switch ($view) {
      case "monthly":
        $period = $span * 30.5;
	break;
      case "weekly":
        $period = $span * 7;
	break;
      case 'daily':
        $period = $span;
	break;
    }
        
    // Send the result in CSV format	
    header ('Content-Type: text/csv');
    header ('Content-Disposition: filename=access_logs.csv');

    export_file_logs($project, $period, $who);
    export_cvs_logs($project, $period, $who);	
    export_svn_logs($project, $period, $who);
    export_doc_logs($project, $period, $who);
    export_wiki_pg_logs($project, $period, $who,0);
    export_wiki_att_logs($project, $period, $who);
    export_document_logs($project, $period, $who);
    exit;

}

project_admin_header(array('title'=>$Language->getText('project_admin_index','p_admin',group_getname($group_id)),
			   'group'=>$group_id,
			   'help' => 'SourceCodeAccessLogs.html'));

//
// BEGIN PAGE CONTENT CODE
//

echo "\n\n";

if ( !isset($who) ) {
    $who = "nonmembers";
}

if ( !isset($span) ) {
	$span = 14;
}

if ( !isset($view) ) { 
	$view = "daily";
}

echo '<h2>'.$Language->getText('project_admin_utils','access_logs').'</h2>';

print '
<FORM action="'.$PHP_SELF.'" method="get">
<TABLE BORDER="0" WIDTH="80%">
<tr><td><b>'.$Language->getText('project_stats_source_code_access','access_log_from').'</b></td><td><b>'.$Language->getText('project_stats_source_code_access','for_last').'</b></td><td> </td></tr>
<tr><td>
<SELECT NAME="who">
<OPTION VALUE="nonmembers" '. (($who == "nonmembers") ? "SELECTED" : "") .'>'.$Language->getText('project_stats_source_code_access','non_proj_members').'</OPTION>
<OPTION VALUE="members" '. (($who == "members") ? "SELECTED" : "") .'>'.$Language->getText('project_admin_editugroup','proj_members').'</OPTION>
<OPTION VALUE="allusers" '. (($who == "allusers") ? "SELECTED" : "") .'>'.$Language->getText('project_stats_source_code_access','all_users').'</OPTION>
</SELECT></td>
<td> 
<SELECT NAME="span">
<OPTION VALUE="4" '. (($span == 4) ? "SELECTED" : "") .'>4</OPTION>
<OPTION VALUE="7" '. (($span == 7 || !isset($span) ) ? "SELECTED" : "") .'>7</OPTION>
<OPTION VALUE="12" '. (($span == 12) ? "SELECTED" : "") .'>12</OPTION>
<OPTION VALUE="14" '. (($span == 14) ? "SELECTED" : "") .'>14</OPTION>
<OPTION VALUE="30" '. (($span == 30) ? "SELECTED" : "") .'>30</OPTION>
<OPTION VALUE="52" '. (($span == 52) ? "SELECTED" : "") .'>52</OPTION>
</SELECT>

<SELECT NAME="view">
<OPTION VALUE="monthly" '. (($view == "monthly") ? "SELECTED" : "") .'>'. $Language->getText('project_stats_index','months') .'</OPTION>
<OPTION VALUE="weekly" '. (($view == "weekly") ? "SELECTED" : "") .'>'. $Language->getText('project_stats_index','weeks') .'</OPTION>
<OPTION VALUE="daily" '. (($view == "daily" || !isset($view)) ? "SELECTED" : "") .'>'. $Language->getText('project_stats_index','days') .'</OPTION>
</SELECT>
</td>
<td>
 
<INPUT type="submit" value="'.$Language->getText('global','btn_browse').'">
<INPUT type="hidden" name="group_id" value="'.$group_id.'">
</td></tr></table></FORM>';

switch($view) {
    case "monthly":
    print '<P>';
    filedownload_logs_daily( $project, $span*30.5, $who);
    cvsaccess_logs_daily( $project, $span*30.5, $who);
    svnaccess_logs_daily( $project, $span*30.5, $who);
    doc_logs_daily( $project, $span*30.5, $who);
    wiki_logs_daily( $project, $span*30.5, $who);
    wiki_attachments_logs_daily( $project, $span*30.5, $who);
    plugins_logs_daily( $project, $span*30.5, $who);
    break;

    case "weekly":
    print '<P>';
    filedownload_logs_daily( $project, $span*7, $who);
    cvsaccess_logs_daily( $project, $span*7, $who);
    svnaccess_logs_daily( $project, $span*7, $who);
    doc_logs_daily( $project, $span*7, $who);
    wiki_logs_daily( $project, $span*7, $who);
    wiki_attachments_logs_daily( $project, $span*7, $who);
    plugins_logs_daily( $project, $span*7, $who);
    break;
  
    case 'daily':
    default:
    filedownload_logs_daily( $project, $span, $who);
    cvsaccess_logs_daily( $project, $span, $who);
    svnaccess_logs_daily( $project, $span, $who);
    doc_logs_daily( $project, $span, $who);
    wiki_logs_daily( $project, $span, $who);
    wiki_attachments_logs_daily( $project, $span, $who);
    plugins_logs_daily( $project, $span, $who);
}


//LJ stats_site_agregate( $group_id );
//Display 'Export Matching Logs' button, only if logs exist
if (access_logs_exist($project, $span, $who)) {
    echo '<BR><FORM METHOD="POST" NAME="access_logs_export_form">
	<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
	<INPUT TYPE="HIDDEN" NAME="who" VALUE="'.$who.'">
	<INPUT TYPE="HIDDEN" NAME="span" VALUE="'.$span.'">
	<INPUT TYPE="HIDDEN" NAME="view" VALUE="'.$view.'">
	<TABLE align="left"><TR><TD>
	<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="'.$GLOBALS['Language']->getText('project_stats_source_code_access','logs_export').'">
	</TD></TR></TABLE></FORM>';
}
print '<BR><P>';

//
// END PAGE CONTENT CODE
//

site_project_footer( array() );
?>
