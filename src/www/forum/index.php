<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require_once('pre.php');
require('../forum/forum_utils.php');
$Language->loadLanguageMsg('forum/forum');


if (!$group_id) {
  exit_no_group();
}


$params=array('title'=>$Language->getText('forum_index','forums_for',group_getname($group_id)),
              'help' => 'WebForums.html',
              'pv'   => isset($pv)?$pv:false);
forum_header($params);


if (user_isloggedin() && user_ismember($group_id)) {
    $public_flag='<3';
} else {
    $public_flag='=1';
}

$sql="SELECT g.group_forum_id,g.forum_name, g.description, famc.count as total
    FROM forum_group_list g
    LEFT JOIN forum_agg_msg_count famc USING (group_forum_id)
    WHERE g.group_id='$group_id' AND g.is_public $public_flag;";

$result = db_query ($sql);

$rows = db_numrows($result); 

if (!$result || $rows < 1) {
  echo '<H1>'.$Language->getText('forum_index','no_forums',group_getname($group_id)).'</H1>';
    echo db_error();
    forum_footer($params);
    exit;
}

if (isset($pv)&&$pv) {
    echo '<H3>'.$Language->getText('forum_forum_utils','discuss_forum').'</H3>';
} else {
    echo "<TABLE width='100%'><TR><TD>";
    echo '<H3>'.$Language->getText('forum_forum_utils','discuss_forum').'</H3>';
    echo "</TD>";
        echo "<TD align='left'> ( <A HREF='".$PHP_SELF."?group_id=$group_id&pv=1'><img src='".util_get_image_theme("msg.png")."' border='0'>&nbsp;".$Language->getText('global','printer_version')."</A> ) </TD>";
    echo "</TR></TABLE>";
}

echo '<P>'.$Language->getText('forum_index','choose_forum').'<P>';

/*
  Put the result set (list of forums for this group) into a column with folders
*/

for ($j = 0; $j < $rows; $j++) { 
    echo '<A HREF="forum.php?forum_id='. db_result($result, $j, 'group_forum_id') .'">'.
        html_image("ic/cfolder15.png",array("border"=>"0")) . 
        '&nbsp;' .
        db_result($result, $j, 'forum_name').'</A> ';
    //message count
    echo '('. ((db_result($result, $j, 'total'))?db_result($result, $j, 'total'):'0') .' msgs)';
    echo "<BR>\n";
    echo db_result($result,$j,'description').'<P>';
}
// Display footer page
forum_footer($params);

?>
