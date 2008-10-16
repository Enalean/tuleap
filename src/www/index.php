<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');
require_once('cache.php');
require_once('www/forum/forum_utils.php');
require_once('features_boxes.php');


$HTML->header(array('title'=>$Language->getText('homepage', 'title')));

?>
<!-- whole page table -->
<TABLE width=100% cellpadding=5 cellspacing=0 border=0>
<TR><TD width="65%" VALIGN="TOP">

<?php
echo stripcslashes($Language->getText('homepage', 'introduction',array($GLOBALS['sys_org_name'],$GLOBALS['sys_name'])));
$HTML->box1_top($Language->getText('homepage', 'news_title')."<A href=\"/export/rss_sfnews.php\" title=\"".$Language->getText('homepage', 'news_title2').'">&nbsp;[XML]</A>');
echo news_show_latest($GLOBALS['sys_news_group'],5,true,false,false,5);
$HTML->box1_bottom();
?>

</TD>

<?php

echo '<TD width="35%" VALIGN="TOP">';

echo show_features_boxes();

?>

</TD></TR>
<!-- LJ end of the main page body -->
</TABLE>
<!-- LJ Added a missing end center -->
</CENTER>

<?php

$HTML->footer(array());

?>
