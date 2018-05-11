<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');    

$HTML->header(array(title=>$Language->getText('export_index','exports_available')));
?>
<P><?php print $Language->getText('export_index','format_explain',$GLOBALS['sys_name']); ?>
<P><B><?php print $Language->getText('export_index','news_data'); ?></B>
<UL>
<LI><A href="rss_sfnews.php"><?php print $GLOBALS['sys_name']; ?> <?php print $Language->getText('export_index','front_p_news'); ?></A>
(<A href="http://my.netscape.com/publish/formats/rss-spec-0.91.html">RSS 0.91</A>,
<A href="http://my.netscape.com/publish/formats/rss-0.91.dtd">&lt;rss-0.91.dtd&gt;</A>)
<LI><A href="rss_sfprojects.php?type=rss&option=newest"><?php print $GLOBALS['sys_name']; ?> <?php print $Language->getText('export_index','newest_projects'); ?> - <?php print $Language->getText('export_index','rss_format'); ?></A>
(<A href="http://my.netscape.com/publish/formats/rss-spec-0.91.html">RSS 0.91</A>,
<A href="http://my.netscape.com/publish/formats/rss-0.91.dtd">&lt;rss-0.91.dtd&gt;</A>)
<LI><A href="rss_sfprojects.php?type=csv&option=newest"><?php print $GLOBALS['sys_name']; ?> <?php print $Language->getText('export_index','newest_projects'); ?> - <?php print $Language->getText('export_index','csv_format'); ?></A>
</UL>
<P><B><?php print $Language->getText('export_index','listings'); ?></B>
<UL>
<LI><A href="rss_sfprojects.php?type=rss"><?php print $GLOBALS['sys_name']; ?> <?php print $Language->getText('export_index','full_proj_listing'); ?> - <?php print $Language->getText('export_index','rss_format'); ?></A>
(<A href="http://my.netscape.com/publish/formats/rss-spec-0.91.html">RSS 0.91</A>,
<A href="http://my.netscape.com/publish/formats/rss-0.91.dtd">&lt;rss-0.91.dtd&gt;</A>)
<LI><A href="rss_sfprojects.php?type=csv"><?php print $GLOBALS['sys_name']; ?> <?php print $Language->getText('export_index','full_proj_listing'); ?> - <?php print $Language->getText('export_index','csv_format'); ?></A>
</UL>
<P><B><?php print $Language->getText('export_index','proj_info'); ?></B>
<UL>
<LI><A href="nitf_sfforums.php"><?php print $Language->getText('export_index','proj_forum'); ?></A>, <?php print $Language->getText('export_index','g_id_required'); ?>
(<A href="sf_forum_0.1.dtd.txt">&lt;sf_forum_0.1.dtd&gt;</A>)
</UL>
<?php
$HTML->footer(array());

?>
