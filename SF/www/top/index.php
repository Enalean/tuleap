<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require ('pre.php');
$HTML->header(array('title'=>'Top Project Listings'));
?>

<P><B>Top SourceForge Projects</B></P>

<P>We track many project usage statistics on SourceForge, and display here
the top ranked projects in several categories.

<UL>
<LI><A href="mostactive.php?type=week">Most Active This Week</A>
<LI><A href="mostactive.php">Most Active All Time</A>
<BR>&nbsp;
<LI><A href="toplist.php?type=downloads">Top Downloads</A>
<LI><A href="toplist.php?type=downloads_week">Top Downloads (Past 7 Days)</A>
<BR>&nbsp;
<LI><A href="toplist.php?type=pageviews_proj">Top Project Pageviews</A> -
Measured by impressions of the SourceForge 'button' logo
<BR>&nbsp;
<LI><A href="toplist.php?type=forumposts_week">Top Forum Post Counts</A>
</UL>

<?php
$HTML->footer(array());
?>
