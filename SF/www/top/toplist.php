<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require "pre.php";    

if ($GLOBALS[type] == 'downloads_week') {
	$rankfield = 'downloads_week';
	$title = 'Top Downloads in the Past 7 Days';
	$column1 = 'Downloads';
}
else if ($GLOBALS[type] == 'pageviews_proj') {
	$rankfield = 'pageviews_proj';
	$title = 'Top Weekly Project Pageviews at *.'.$GLOBALS['sys_default_domain'].' (from impressions of SF logo)';
	$column1 = 'Pageviews';
}
else if ($GLOBALS[type] == 'forumposts_week') {
	$rankfield = 'forumposts_week';
	$title = 'Top Forum Post Counts';
	$column1 = 'Posts';
}
// default to downloads
else {
	$rankfield = 'downloads_all';
	$title = 'Top Downloads';
	$column1 = 'Downloads';
}


$HTML->header(array('title'=>$title));

print '<P><B><FONT size="+1">'.$title.'</FONT></B>
<BR><I>(Updated Daily)</I>

<P><A href="/top/">[View Other Top Categories]</A>

<P><TABLE width="100%" cellpadding=0 cellspacing=0 border=0>
<TR valign="top">
<TD><B>Rank</B></TD>
<TD><B>Project Name<BR>&nbsp;</B></TD>
<TD align="right"><B>'.$column1.'</B></TD>
<TD align="right"><B>Last Rank</B></TD>
<TD align="right"><B>Change</B>&nbsp;&nbsp;&nbsp;</TD></TR>
';

$res_top = db_query("SELECT groups.group_id,groups.group_name,groups.unix_group_name,top_group.$rankfield,".
	"top_group.rank_$rankfield,top_group.rank_".$rankfield."_old ".
	"FROM groups,top_group ".
	"WHERE top_group.$rankfield > 0 ".
	"AND top_group.group_id=groups.group_id ".
	"ORDER BY top_group.rank_$rankfield LIMIT 100");

echo db_error();

while ($row_top = db_fetch_array($res_top)) {
	$i++;
	print '<TR BGCOLOR="'. util_get_alt_row_color($i) .'"><TD>&nbsp;&nbsp;'.$row_top["rank_$rankfield"]
		.'</TD><TD><A href="/projects/'. strtolower($row_top['unix_group_name']) .'/">'
		.stripslashes($row_top['group_name'])."</A>"
		.'</TD><TD align="right">'.$row_top["$rankfield"]
		.'&nbsp;&nbsp;&nbsp;</TD><TD align="right">'.$row_top["rank_$rankfield"."_old"]
		.'&nbsp;&nbsp;&nbsp;</TD>'
		.'<TD align="right">';

	// calculate change
	$diff = $row_top["rank_$rankfield"."_old"] - $row_top["rank_$rankfield"];
	if (($row_top["rank_$rankfield"."_old"] == 0) || ($row_top["rank_$rankfield"] == 0)) {
		print "N/A";
	}
	else if ($diff == 0) {
		print "Same";
	}
	else if ($diff > 0) {
		print "<FONT color=\"#009900\">Up $diff</FONT>";
	}
	else if ($diff < 0) {
		print "<FONT color=\"#CC0000\">Down ".(0-$diff)."</FONT>";
	}

	print '&nbsp;&nbsp;&nbsp;</TD></TR>
';
}

print '</TABLE>';

$HTML->footer(array());
?>
