<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$ 
require($DOCUMENT_ROOT.'/include/pre.php');
require('./site_stats_utils.php');

$LANG->loadLanguageMsg('stats/stats');

// require you to be a member of the super-admin group
session_require(array('group'=>'1','admin_flags'=>'A'));

$HTML->header(array(title=>$LANG->getText('stats_graph','stats',$GLOBALS['sys_name'])));

//
// BEGIN PAGE CONTENT CODE
//

echo "\n\n";

print '<DIV ALIGN="CENTER">' . "\n";
print '<span class="normal"><b>'.$LANG->getText('stats_index','sitewide_agg_stats').'</b></span><BR>' . "\n";


print '
<HR>

<table width="100%" cellpadding="0" cellspacing="0" border="0">
<tr>
<td align="center"><B>'.$LANG->getText('stats_graph','overview').'</B></td>
<td align="center"><a href="projects.php">'.$LANG->getText('stats_graph','project_stats').'</a></td>
<td align="center"><a href="graphs.php">'.$LANG->getText('stats_graph','site_graphs').'</a></td>
</tr>
</table>

<HR>
';


stats_site_agregate( $group_id );
print '<BR><BR>' . "\n";
stats_site_projects_daily( 14 );
print '<BR><BR>' . "\n";
//stats_site_projects_weekly( 52 );
print '<BR><BR>' . "\n";
print '</DIV>' . "\n";

//
// END PAGE CONTENT CODE
//

$HTML->footer( array() );
?>
