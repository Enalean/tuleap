<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require ('pre.php');    // Initial db and session library, opens session
require ('cache.php');
require($DOCUMENT_ROOT.'/forum/forum_utils.php');

$HTML->header(array('title'=>'Welcome'));

?>
<!-- whole page table -->
<TABLE width=100% cellpadding=5 cellspacing=0 border=0>
<TR><TD width="65%" VALIGN="TOP">
<? util_get_content('homepage/welcome_intro'); ?>
<?php
$HTML->box1_top('Latest News');
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
