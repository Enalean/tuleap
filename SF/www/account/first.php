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

<P><h2>Welcome to CodeX!</h2>

<P>You are now a registered user on CodeX. As a registered user, you can now fully participate in the activities
of the CodeX Web Site. You may use forums, subscribe to mailing lists, browse through the list of hosted projects, or even start your own project.

<?php
	// LJ modified. Cron delay now in /etc/local.inc.
	// No longer hardcoded
	$date = getdate(time());
	$hoursleft = ($sys_crondelay - 1) - ($date[hours] % $sys_crondelay);
	$minutesleft = 60 - $date[minutes];
?>

<P><center><b><font color="red">** IMPORTANT REMARKS **</font></b></center>
<BR>While your Web account is available right now, it takes some time for CodeX to create your <u>Shell account</u> (same user name, same password). Some features like CVS access depend on it. Your Shell account will be activated in about 

<?php print "<b> $hoursleft</B> hour <B>$minutesleft</B> minutes"; ?> from now.

<P>In the meantime we highly recommend that you browse through the site, read the 
<A href="/docs/site/">Site Documentation</A> (e.g. the Xerox Code eXchange Policy) and finalize the setup of your <a href="/account/">User Profile</a> (Choose your Time Zone, define your skills profile,etc.)

<P><center><b><font color="red">*-*-*-*</font></b></center>

<P>Enjoy the site, provide us with feedback on ways
that we can improve CodeX and speak of CodeX around you.

<P>-- The CodeX team

<?php
$HTML->footer(array());

?>
