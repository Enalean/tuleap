<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require_once('pre.php');
require('../forum/forum_utils.php');

$Language->loadLanguageMsg('news/news');

if (!isset($pv)) $pv=false;
if (isset($group_id)) {
  $title = $Language->getText('news_index','news_for',group_getname($group_id));
 } else {
  $title = $Language->getText('news_index','news');
 }
$params=array('title'=>$title,
              'help'=>'NewsService.html',
              'pv'=>$pv);

news_header($params);

if ($pv) {
    echo '<H3>'.$Language->getText('news_index','news').'</H3>';
} else {
    echo "<TABLE width='100%'><TR><TD>";
    echo '<H3>'.$Language->getText('news_index','news').'</H3>';
    echo "</TD>";
    echo "<TD align='left'> ( <A HREF='".$PHP_SELF."?group_id=$group_id&pv=1'><img src='".util_get_image_theme("msg.png")."' border='0'>&nbsp;".$Language->getText('global','printer_version')."</A> ) </TD>";
    echo "</TR></TABLE>";    
}


    echo '<P>'.$Language->getText('news_index','choose_news').'<P>';

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
	echo '<H2>'.$Language->getText('news_index','no_news_found');
	if ($group_id) {
	  echo ' '.$Language->getText('news_index','for',group_getname($group_id));
	}
	echo '</H2>';
	echo '
		<P>'.$Language->getText('news_index','no_items_found');
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

// Display footer page
news_footer($params);

?>
