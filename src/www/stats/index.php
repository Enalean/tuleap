<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
//
require_once('pre.php');
require('./site_stats_utils.php');


// require you to be a member of the super-admin group
session_require(array('group'=>'1','admin_flags'=>'A'));

$HTML->header(array('title'=>$Language->getText('stats_graph','stats',$GLOBALS['sys_name']), 'main_classes' => array('tlp-framed')));

//
// BEGIN PAGE CONTENT CODE
//

echo "\n\n";

print '<DIV ALIGN="CENTER">' . "\n";
print '<span class="normal"><b>'.$Language->getText('stats_index','sitewide_agg_stats').'</b></span><BR>' . "\n";


print '
<HR>

<table width="100%" cellpadding="0" cellspacing="0" border="0">
<tr>
<td align="center"><B>'.$Language->getText('stats_graph','overview').'</B></td>
<td align="center"><a href="projects.php">'.$Language->getText('stats_graph','project_stats').'</a></td>
<td align="center"><a href="graphs.php">'.$Language->getText('stats_graph','site_graphs').'</a></td>
</tr>
</table>

<HR>
';


stats_site_agregate();
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
