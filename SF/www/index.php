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

	<hr width="100%" size="1" noshade>
	<span class="slogan">
	<div align="center">
        <font face="arial, helvetica" size="5">"Breaking Down The Barriers to Open Source Development."</font><br>
	</div>
	</span>
        <hr width="100%" size="1" noshade>
	&nbsp;<br>
<P>
SourceForge is a <B>free service to
<A href="http://www.opensource.org">Open Source</A> developers</B> offering
easy access to the best in CVS, mailing lists, bug tracking, message boards/forums,
task management, site hosting, permanent file archival, full backups,
and total web-based administration. <A href="/docs/site/services.php"><font size="-1">[ more ]</font></A>
<A href="/docs/site/faq.php"><font size="-1">[ FAQ ]</font></A>
<BR>
&nbsp;
<P>
<B>Site Feedback and Participation</B>
<P>
In order to get the most out of SourceForge, you'll need
to <A href="/account/register.php">register as a
site user</A>. This will allow you to participate fully in all we have to
offer. You may of course browse the site without registering, but will
not have access to participate fully.
<P>
&nbsp;
<BR>
<B>Set Up Your Own Project</B>
<P>
<A href="/account/register.php">Register as a site user</A>,
then <A HREF="/account/login.php">Login</A> and finally,
<A HREF="/register/">Register Your Project.</A>
<P>
Thanks... and enjoy the site.
<P>
<?php
$HTML->box1_top('Latest News');
echo news_show_latest(714,5,true);
$HTML->box1_bottom();
?>

</TD>

<?php

echo '<TD width="35%" VALIGN="TOP">';

echo cache_display('show_features_boxes','0',1800);

?>

</TD></TR></TABLE>

<?php

$HTML->footer(array());

?>
