<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require('pre.php');
require('../forum/forum_utils.php');

news_header(array('title'=>'News',
		  'help'=>'NewsService.html'));

echo '<H3>News</H3>
	<P>Choose a News item and you can browse, search, and post messages.<P>';

/*
	Put the result set (list of forums for this group) into a column with folders
*/
if ($group_id && ($group_id != $GLOBALS['sys_news_group'])) {
	$sql="SELECT * FROM news_bytes WHERE group_id='$group_id' AND is_approved <> '4' ORDER BY date DESC";
} else {
	$sql="SELECT * FROM news_bytes WHERE is_approved='1' ORDER BY date DESC";
}

$result=db_query($sql);
$rows=db_numrows($result);

if ($rows < 1) {
	echo '<H2>No News Found';
	if ($group_id) {
		echo ' For '.group_getname($group_id);
	}
	echo '</H2>';
	echo '
		<P>No items were found';
	echo db_error();
} else {
	echo '<table WIDTH="100%" border=0>
		<TR><TD VALIGN="TOP">'; 

	for ($j = 0; $j < $rows; $j++) { 
	  if ($group_id) {
	    echo '
		<A HREF="/forum/forum.php?forum_id='.db_result($result, $j, 'forum_id').
	      '&group_id='.$group_id.'">'.
	      '<IMG SRC="'.util_get_image_theme("ic/cfolder15.png").'" HEIGHT=13 WIDTH=15 BORDER=0> &nbsp;'.
	      stripslashes(db_result($result, $j, 'summary')).'</A> ';
	  } else {
	    echo '
		<A HREF="/forum/forum.php?forum_id='.db_result($result, $j, 'forum_id').
	      '<IMG SRC="'.util_get_image_theme("ic/cfolder15.png").'" HEIGHT=13 WIDTH=15 BORDER=0> &nbsp;'.
	      stripslashes(db_result($result, $j, 'summary')).'</A> ';
	  }
		echo '
		<BR>';
	}

	echo '
	</TD></TR></TABLE>';
}

news_footer(array());

?>
