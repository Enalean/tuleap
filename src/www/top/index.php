<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require_once('pre.php');

$Language->loadLanguageMsg('top/top');

$HTML->header(array('title'=>$Language->getText('top_index','top_list')));
?>

<H2><?php print $Language->getText('top_index','top_proj',$GLOBALS['sys_name']); ?></H2>

<P><?php print $Language->getText('top_index','rank',$GLOBALS['sys_name']); ?>

<UL>
<LI><A href="mostactive.php?type=week"><?php print $Language->getText('top_index','act_week'); ?></A>
<LI><A href="mostactive.php"><?php print $Language->getText('top_index','act_all_time'); ?></A>
<BR>&nbsp;
<LI><A href="toplist.php?type=downloads"><?php print $Language->getText('top_index','download'); ?></A>
<LI><A href="toplist.php?type=downloads_week"><?php print $Language->getText('top_index','downl_week'); ?></A>
<BR>&nbsp;
<LI><A href="toplist.php?type=pageviews_proj"><?php print $Language->getText('top_index','pageviews'); ?>
<BR>&nbsp;
<LI><A href="toplist.php?type=forumposts_week"><?php print $Language->getText('top_index','forum'); ?></A>
</UL>

<?php
$HTML->footer(array());
?>
