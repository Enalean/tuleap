<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require_once('pre.php');    

$Language->loadLanguageMsg('top/top');


if (!isset($offset) || $offset < 0) {
	$offset=0;
}
if (!isset($type) || $type != 'week') {
    $type = '';
}

if ($type == 'week') {
	$sql="SELECT groups.group_name,groups.unix_group_name,groups.group_id,project_weekly_metric.ranking,project_weekly_metric.percentile ".
		"FROM groups,project_weekly_metric ".
		"WHERE groups.group_id=project_weekly_metric.group_id AND ".
		"groups.is_public=1 AND groups.type=1 ".
		"ORDER BY ranking ASC LIMIT $offset,50";
	$title = $Language->getText('top_index','act_week');
} else {
	$sql="SELECT groups.group_name,groups.unix_group_name,groups.group_id,project_metric.ranking,project_metric.percentile ".
		"FROM groups,project_metric ".
		"WHERE groups.group_id=project_metric.group_id AND ".
		"groups.is_public=1 AND groups.type=1 ".
		"ORDER BY ranking ASC LIMIT $offset,50";
	$title = $Language->getText('top_index','act_all_time');
}


$HTML->header(array('title'=>$title));

print '<P><B><FONT size="+1">'.$title.'</FONT></B>
<BR><I>'.$Language->getText('top_mostactive','update_daily').'</I>

<P><A href="/top/">'.$Language->getText('top_mostactive','view_other_top_cat').'</A>

<P><TABLE width="100%" cellpadding=0 cellspacing=0 border=0>
<TR valign="top">
<TD><B>'.$Language->getText('top_mostactive','rank').'</B></TD>
<TD><B>'.$Language->getText('top_mostactive','name').'<BR>&nbsp;</B></TD>
<TD align="right"><B>'.$Language->getText('top_mostactive','percentile').'</B></TD>
</TR>
';

$res_top=db_query($sql);

$i = $offset;
while ($row_top = db_fetch_array($res_top)) {
	$i++;
	//don't take real rank because template and test_projects are still in top_group table
	//but we don't want to show them in the stats ...
	//not very nice ...
	print '<TR class="'. util_get_alt_row_color($i) .'"><TD>&nbsp;&nbsp;'.$i
		.'</TD><TD><A href="/projects/'. strtolower($row_top['unix_group_name']) .'/">'
		.$row_top['group_name']."</A>"
		.'</TD><TD align="right">'.$row_top['percentile'].'</TD></TR>';
}

print '<TR><TD>'.(($offset>0)?'<A HREF="mostactive.php?type='.$type.'&offset='.($offset-50).'"><B><-- '.$Language->getText('top_mostactive','more').'</B></A>':'&nbsp;').'</TD>
	<TD>&nbsp;</TD>
	<TD ALIGN="RIGHT"><A HREF="mostactive.php?type='.$type.'&offset='.($offset+50).'"><B>'.$Language->getText('top_mostactive','more').' --></B></A></TD></TR>';

print '</TABLE>';

$HTML->footer(array());
?>
