<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001. All Rights Reserved
// http://codex.xerox.com
//
// $Id$ 
require('pre.php');
require('../admin/project_admin_utils.php');
require('source_code_access_utils.php');

// Only for project administrators
session_require(array('group'=>$group_id,'admin_flags'=>'A'));

if ( !$group_id ) {
	exit_error("Invalid Group","That group could not be found.");
}

project_admin_header(array('title'=>"Project Admin: ".group_getname($group_id),
			   'group'=>$group_id,
			   'help' => 'SourceCodeAccessLogs.html'));

//
// BEGIN PAGE CONTENT CODE
//

echo "\n\n";

if ( !$span ) {
	$span = 14;
}

if ( !$view ) { 
	$view = "daily";
}

echo '<h2>Source Code Access Log</h2>';

if ( $view == 'daily' ) {

	print '<P>';
	filedownload_logs_daily( $group_id, $span );
	cvsaccess_logs_daily( $group_id, $span );
	doc_logs_daily( $group_id, $span );

} elseif ( $view == 'weekly' ) {

	print '<P>';
	filedownload_logs_daily( $group_id, $span*7 );
	cvsaccess_logs_daily( $group_id, $span*7 );
	doc_logs_daily( $group_id, $span*7 );

} elseif ( $view == 'monthly' ) {

	print '<P>';
	filedownload_logs_daily( $group_id, $span*30.5 );
	cvsaccess_logs_daily( $group_id, $span*30.5 );
	doc_logs_daily( $group_id, $span*30.5 );

} else {

	// default stats display, DAILY
	print '<P>';
	filedownload_logs_daily( $group_id, $span );
	cvsaccess_logs_daily( $group_id, $span );
	doc_logs_daily( $group_id, $span );

}

print '<BR><P>';
//LJ stats_site_agregate( $group_id );

?>

<DIV ALIGN="center">
<FORM action="<?php echo $PHP_SELF; ?>" method="get">
View the Last <SELECT NAME="span">
<OPTION VALUE="4" <?php if ($span == 4) {echo 'SELECTED';} ?>>4</OPTION>
<OPTION VALUE="7" <?php if ($span == 7 || !isset($span) ) {echo 'SELECTED';} ?>>7</OPTION>
<OPTION VALUE="12" <?php if ($span == 12) {echo 'SELECTED';} ?>>12</OPTION>
<OPTION VALUE="14" <?php if ($span == 14) {echo 'SELECTED';} ?>>14</OPTION>
<OPTION VALUE="30" <?php if ($span == 30) {echo 'SELECTED';} ?>>30</OPTION>
<OPTION VALUE="52" <?php if ($span == 52) {echo 'SELECTED';} ?>>52</OPTION>
</SELECT>
&nbsp;
<SELECT NAME="view">
<OPTION VALUE="monthly" <?php if ($view == "monthly") {echo 'SELECTED';} ?>>Months</OPTION>
<OPTION VALUE="weekly" <?php if ($view == "weekly") {echo 'SELECTED';} ?>>Weeks</OPTION>
<OPTION VALUE="daily" <?php if ($view == "daily" || !isset($view) ) {echo 'SELECTED';} ?>>Days</OPTION>
</SELECT>
&nbsp; 
<INPUT type="submit" value="Change Logs View">
<INPUT type="hidden" name="group_id" value="<?php echo $group_id; ?>">
</FORM>
</DIV>


<?php
//
// END PAGE CONTENT CODE
//

site_project_footer( array() );
?>
