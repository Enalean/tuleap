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
        <font face="arial, helvetica" size="5">"Breaking Down The Barriers to Code Sharing inside Xerox."</font><br>
	</div>
	</span>
        <hr width="100%" size="1" noshade>
	&nbsp;<br>
CodeX is a <B>service to
all Xerox software development teams</B> who want to share their project source code with others <b>inside</b> Xerox. <A href="/docs/site/about_codex.php"><font size="-1">[&nbsp;More about CodeX&nbsp;]</font></A><P>
CodeX offers an easy access to a full featured and totally web-based project management environment. Using CodeX project teams can better focus on software development while making their community of users and developers grow. <A href="/docs/site/"><font size="-1">[&nbsp;More on CodeX Services&nbsp;]</font></A>
<A href="/docman/display_doc.php?docid=17&group_id=1"><font size="-1">[&nbsp;FAQ&nbsp;]</font></A>
<P>
<u><B>Site Participation</B></u>
<BR>
In order to get the most out of CodeX, you should
 <A href="/account/register.php">register as a
site user</A>. It's easy and fast and it allows you to participate fully in all we have to offer. Also make sure you read the <b><A href="/docman/display_doc.php?docid=16&group_id=1">Xerox Code eXchange Policy</a></b> before using this site.
<!--You may of course browse the site without registering, but will
not have access to participate fully. -->
<P>

<u><B>Set Up Your Own Project</B></u>
<BR>
If you want other Xerox developers to know what you are doing first <A href="/account/register.php">Register as a site user</A>,
then <A HREF="/account/login.php">Login</A> and finally,
<A HREF="/register/">Register Your Project.</A> It only takes a couple of minutes to get a fully working environment to share your code.
<P>
Thanks... and enjoy the site.
<P>
<?php
$HTML->box1_top('Latest News');
echo news_show_latest($GLOBALS['sys_news_group'],5,true);
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
