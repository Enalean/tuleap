<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require($DOCUMENT_ROOT.'/include/pre.php');
require($DOCUMENT_ROOT.'/include/cache.php');
require($DOCUMENT_ROOT.'/forum/forum_utils.php');

$LANG->loadLanguageMsg('homepage/homepage');

$HTML->header(array('title'=>$LANG->getText('homepage', 'title')));

?>
<!-- whole page table -->
<TABLE width=100% cellpadding=5 cellspacing=0 border=0>
<TR><TD width="65%" VALIGN="TOP">

<?php
echo stripcslashes($LANG->getText('homepage', 'introduction',array($GLOBALS['sys_org_name'],$GLOBALS['sys_name'])));
$HTML->box1_top($LANG->getText('homepage', 'news_title')."<A href=\"/export/rss_sfnews.php\" title=\"".$LANG->getText('homepage', 'news_title2').'">&nbsp;[XML]</A>');
echo news_show_latest($GLOBALS['sys_news_group'],5,true,false,false,5);
$HTML->box1_bottom();
?>

</TD>

<?php

echo '<TD width="35%" VALIGN="TOP">';

echo cache_display('show_features_boxes','0',1800);

?>

</TD></TR>
<!-- LJ end of the main page body -->
</TABLE>
<!-- LJ Added a missing end center -->
</CENTER>

<?php

$HTML->footer(array());

?>
