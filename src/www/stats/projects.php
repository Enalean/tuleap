<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
//
require_once('pre.php');
require('./site_stats_utils.php');


$HTML->header(array('title'=> $Language->getText('stats_graph','stats',$GLOBALS['sys_name']), 'main_classes' => array('tlp-framed')));

// require you to be a member of the super-admin group
session_require(array('group'=>'1','admin_flags'=>'A'));


//
// BEGIN PAGE CONTENT CODE
//

echo "\n\n";

print '<DIV ALIGN="CENTER">' . "\n";
print '<span class="normal"><b>'.$Language->getText('stats_projects','comparisons').'</b></span><BR>' . "\n";
print '</DIV>'."\n";

print '

<HR>
<table width="100%" cellpadding="0" cellspacing="0" border="0">
<tr>
<td align="center"><a href="index.php">'.$Language->getText('stats_graph','overview').'</a></td>
<td align="center"><B>'.$Language->getText('stats_graph','project_stats').'</B></td>
<td align="center"><a href="graphs.php">'.$Language->getText('stats_graph','site_graphs').'</a></td>
</tr>
</table>

<HR>
';

if ( isset( $span ) ) {

	if ( !isset($orderby) ) {
		$orderby = "downloads";
	}

	if ( isset( $trovecatid ) && $trovecatid > 0 ) {
		$project_list = stats_generate_trove_grouplist( $trovecatid );
	}

	if ( $span < 1 ) {
		$span = 21;
	}

	if ( !isset($offset) ) {
		$offset = 0;
	}

	if ( $projects != "" ) {
		$project_list = explode(" ", $projects );
		$trovecatid = -1;
	}

	if ( $trovecatid == 0 ) {
		$project_list = 0;
	}

	   // Print the form, passing it the params, so it can save state.
	stats_site_projects_form( $span, $orderby, $offset, $projects, $trovecatid );

	print '<DIV ALIGN="CENTER">' . "\n";
	print '<BR><BR>' . "\n";
	stats_site_projects( $span, $orderby, $offset, $project_list, $trovecatid );
	print '<BR><BR>' . "\n";
	print '</DIV>' . "\n";

} else {

	   // Print the form, passing it the params, so it can save state.
	stats_site_projects_form( $span, $orderby, $offset, $projects, $trovecatid );

}

//
// END PAGE CONTENT CODE
//

$HTML->footer( array() );
?>
