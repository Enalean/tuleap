<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require ('pre.php');    

if (!$offset || $offset < 0) {
	$offset=0;
}

if ($type == 'week') {
	$sql="SELECT groups.group_name,groups.unix_group_name,groups.group_id,project_weekly_metric.ranking,project_weekly_metric.percentile ".
		"FROM groups,project_weekly_metric ".
		"WHERE groups.group_id=project_weekly_metric.group_id AND ".
		"groups.is_public=1 ".
		"ORDER BY ranking ASC LIMIT $offset,50";
	$title = 'Most Active This Week';
} else {
	$sql="SELECT groups.group_name,groups.unix_group_name,groups.group_id,project_metric.ranking,project_metric.percentile ".
		"FROM groups,project_metric ".
		"WHERE groups.group_id=project_metric.group_id AND ".
		"groups.is_public=1 ".
		"ORDER BY ranking ASC LIMIT $offset,50";
	$title = 'Most Active All Time';
}


$HTML->header(array('title'=>$title));

print '<P><B><FONT size="+1">'.$title.'</FONT></B>
<BR><I>(Updated Daily)</I>

<P><A href="/top/">[View Other Top Categories]</A>

<P><TABLE width="100%" cellpadding=0 cellspacing=0 border=0>
<TR valign="top">
<TD><B>Rank</B></TD>
<TD><B>Project Name<BR>&nbsp;</B></TD>
<TD align="right"><B>Percentile</B></TD>
</TR>
';

$res_top=db_query($sql);

while ($row_top = db_fetch_array($res_top)) {
	$i++;
	print '<TR class="'. util_get_alt_row_color($i) .'"><TD>&nbsp;&nbsp;'.$row_top['ranking']
		.'</TD><TD><A href="/projects/'. strtolower($row_top['unix_group_name']) .'/">'
		.$row_top['group_name']."</A>"
		.'</TD><TD align="right">'.$row_top['percentile'].'</TD></TR>';
}

print '<TR class="'.$HTML->COLOR_LTBACK2.'"><TD>'.(($offset>0)?'<A HREF="mostactive.php?type='.$type.'&offset='.($offset-50).'"><B><-- More</B></A>':'&nbsp;').'</TD>
	<TD>&nbsp;</TD>
	<TD ALIGN="RIGHT"><A HREF="mostactive.php?type='.$type.'&offset='.($offset+50).'"><B>More --></B></A></TD></TR>';

print '</TABLE>';

$HTML->footer(array());
?>
