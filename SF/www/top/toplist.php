<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require($DOCUMENT_ROOT.'/include/pre.php');  

$Language->loadLanguageMsg('top/top'); 

if ($GLOBALS[type] == 'downloads_week') {
	$rankfield = 'downloads_week';
	$title = $Language->getText('top_toplist','downl_week');
	$column1 = $Language->getText('top_toplist','downl');
}
else if ($GLOBALS[type] == 'pageviews_proj') {
	$rankfield = 'pageviews_proj';
	$title = $Language->getText('top_toplist','top_pageviews',array($GLOBALS['sys_default_domain'],$GLOBALS['sys_name']));
	$column1 = $Language->getText('top_toplist','pageviews');
}
else if ($GLOBALS[type] == 'forumposts_week') {
	$rankfield = 'forumposts_week';
	$title = $Language->getText('top_toplist','forum_counts');
	$column1 = $Language->getText('top_toplist','posts');
}
// default to downloads
else {
	$rankfield = 'downloads_all';
	$title = $Language->getText('top_index','download');
	$column1 = $Language->getText('top_toplist','downl');
}


$HTML->header(array('title'=>$title));

print '<P><B><FONT size="+1">'.$title.'</FONT></B>
<BR><I>'.$Language->getText('top_mostactive','update_daily').'</I>

<P><A href="/top/">'.$Language->getText('top_mostactive','view_other_top_cat').'</A>

<P><TABLE width="100%" cellpadding=0 cellspacing=0 border=0>
<TR valign="top">
<TD><B>'.$Language->getText('top_mostactive','rank').'</B></TD>
<TD><B>'.$Language->getText('top_mostactive','name').'<BR>&nbsp;</B></TD>
<TD align="right"><B>'.$column1.'</B></TD>
<TD align="right"><B>'.$Language->getText('top_toplist','last_rank').'</B></TD>
<TD align="right"><B>'.$Language->getText('top_toplist','change').'</B>&nbsp;&nbsp;&nbsp;</TD></TR>
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
	print '<TR class="'. util_get_alt_row_color($i) .'"><TD>&nbsp;&nbsp;'.$row_top["rank_$rankfield"]
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
		print $Language->getText('top_toplist','same');
	}
	else if ($diff > 0) {
		print "<span class=\"top_up\">".$Language->getText('top_toplist','up',$diff)."</span>";
	}
	else if ($diff < 0) {
		print "<span class=\"top_down\">".$Language->getText('top_toplist','down',(0-$diff))."</span>";
	}

	print '&nbsp;&nbsp;&nbsp;</TD></TR>
';
}

print '</TABLE>';

$HTML->footer(array());
?>
