<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require "pre.php";    
$HTML->header(array(title=>"Welcome to Codex"));
?>

<P><h2>Welcome to <?php print $GLOBALS['sys_name']; ?>!</h2>

<P>You are now a registered user on <?php print $GLOBALS['sys_name']; ?>. As a registered user, you can now fully participate in the activities
of the <?php print $GLOBALS['sys_name']; ?> Web Site. You may use forums, subscribe to mailing lists, browse through the list of hosted projects, or even start your own project.

<?php
	$date = getdate(time());
	$hoursleft = ($sys_crondelay - 1) - ($date[hours] % $sys_crondelay);
	$minutesleft = 60 - $date[minutes];
?>

<P><center><b><span class="highlight">** IMPORTANT REMARKS **</span></b></center>
<BR>While your Web account is available right now, it takes some time for <?php print $GLOBALS['sys_name']; ?> to create your <u>Shell account</u> (same user name, same password). Some features like CVS access depend on it. Your Shell account will be activated in about 

<?php print "<b> $hoursleft</B> hour <B>$minutesleft</B> minutes"; ?> from now.

<P>In the meantime we highly recommend that you browse through the site, read the 
<A href="/docs/site/">Site Documentation</A> (e.g. the <?php print $GLOBALS['sys_org_name']; ?> Code eXchange Policy) and finalize the setup of your <a href="/account/">User Profile</a> (Choose your Time Zone, define your skills profile,etc.)

<P><center><b><span class="highlight">*-*-*-*</span></b></center>

<P>Enjoy the site, provide us with feedback on ways
that we can improve <?php print $GLOBALS['sys_name']; ?> and speak of <?php print $GLOBALS['sys_name']; ?> around you.

<P>-- The <?php print $GLOBALS['sys_name']; ?> Team

<?php
$HTML->footer(array());

?>
