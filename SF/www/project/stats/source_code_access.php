<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001. All Rights Reserved
// http://codex.xerox.com
//
// $Id$ 
require_once('pre.php');
require('../admin/project_admin_utils.php');
require('./source_code_access_utils.php');

$Language->loadLanguageMsg('project/project');

// Only for project administrators
session_require(array('group'=>$group_id,'admin_flags'=>'A'));

if ( !$group_id ) {
	exit_error($Language->getText('project_admin_userperms','invalid_g'),$Language->getText('project_admin_userperms','group_not_exist'));
}
$project=new Project($group_id);

project_admin_header(array('title'=>$Language->getText('project_admin_index','p_admin',group_getname($group_id)),
			   'group'=>$group_id,
			   'help' => 'SourceCodeAccessLogs.html'));

//
// BEGIN PAGE CONTENT CODE
//

echo "\n\n";

if ( !$who ) {
    $who = "nonmembers";
}

if ( !$span ) {
	$span = 14;
}

if ( !$view ) { 
	$view = "daily";
}

echo '<h2>'.$Language->getText('project_admin_utils','access_logs').'</h2>';

?>
<FORM action="<?php echo $PHP_SELF; ?>" method="get">
<TABLE BORDER="0" WIDTH="80%">
<tr><td><b><?php echo $Language->getText('project_stats_source_code_access','access_log_from'); ?></b></td><td><b><?php echo $Language->getText('project_stats_source_code_access','for_last'); ?></b></td><td> </td></tr>
<tr><td>
<SELECT NAME="who">
<OPTION VALUE="nonmembers" <?php if ($who == "nonmembers") {echo 'SELECTED';} ?>><?php echo $Language->getText('project_stats_source_code_access','non_proj_members'); ?></OPTION>
<OPTION VALUE="members" <?php if ($who == "members") {echo 'SELECTED';} ?>><?php echo $Language->getText('project_admin_editugroup','proj_members'); ?></OPTION>
<OPTION VALUE="allusers" <?php if ($who == "allusers") {echo 'SELECTED';} ?>><?php echo $Language->getText('project_stats_source_code_access','all_users'); ?></OPTION>
</SELECT></td>
<td> 
<SELECT NAME="span">
<OPTION VALUE="4" <?php if ($span == 4) {echo 'SELECTED';} ?>>4</OPTION>
<OPTION VALUE="7" <?php if ($span == 7 || !isset($span) ) {echo 'SELECTED';} ?>>7</OPTION>
<OPTION VALUE="12" <?php if ($span == 12) {echo 'SELECTED';} ?>>12</OPTION>
<OPTION VALUE="14" <?php if ($span == 14) {echo 'SELECTED';} ?>>14</OPTION>
<OPTION VALUE="30" <?php if ($span == 30) {echo 'SELECTED';} ?>>30</OPTION>
<OPTION VALUE="52" <?php if ($span == 52) {echo 'SELECTED';} ?>>52</OPTION>
</SELECT>
&nbsp;
<SELECT NAME="view">
<OPTION VALUE="monthly" <?php if ($view == "monthly") {echo 'SELECTED';} ?>><?php echo $Language->getText('project_stats_index','months'); ?></OPTION>
<OPTION VALUE="weekly" <?php if ($view == "weekly") {echo 'SELECTED';} ?>><?php echo $Language->getText('project_stats_index','weeks'); ?></OPTION>
<OPTION VALUE="daily" <?php if ($view == "daily" || !isset($view) ) {echo 'SELECTED';} ?>><?php echo $Language->getText('project_stats_index','days'); ?></OPTION>
</SELECT>
</td>
<td>
&nbsp; 
<INPUT type="submit" value="<?php echo $Language->getText('global','btn_browse'); ?>">
<INPUT type="hidden" name="group_id" value="<?php echo $group_id; ?>">
</td>
</tr>
</table>
</FORM>

<?php
if ( $view == 'daily' ) {

	print '<P>';
	filedownload_logs_daily( $project, $span, $who);
	cvsaccess_logs_daily( $project, $span, $who);
	svnaccess_logs_daily( $project, $span, $who);
	doc_logs_daily( $project, $span, $who);

} elseif ( $view == 'weekly' ) {

	print '<P>';
	filedownload_logs_daily( $project, $span*7, $who);
	cvsaccess_logs_daily( $project, $span*7, $who);
	svnaccess_logs_daily( $project, $span*7, $who);
	doc_logs_daily( $project, $span*7, $who);

} elseif ( $view == 'monthly' ) {

	print '<P>';
	filedownload_logs_daily( $project, $span*30.5, $who);
	cvsaccess_logs_daily( $project, $span*30.5, $who);
	svnaccess_logs_daily( $project, $span*30.5, $who);
	doc_logs_daily( $project, $span*30.5, $who);

} else {

	// default stats display, DAILY
	print '<P>';
	filedownload_logs_daily( $project, $span, $who);
	cvsaccess_logs_daily( $project, $span, $who);
	svnaccess_logs_daily( $project, $span, $who);
	doc_logs_daily( $project, $span, $who);

}

print '<BR><P>';
//LJ stats_site_agregate( $group_id );

//
// END PAGE CONTENT CODE
//

site_project_footer( array() );
?>
