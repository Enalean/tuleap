<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require "pre.php";    
$HTML->header(array(title=>"Welcome to Project Alexandria"));
?>

<p><B>SourceForge Site Documentation</B>

<UL>
<LI><A href="faq.php">Frequently Asked Questions (FAQ)</A>
<LI><A href="services.php">SourceForge Services</A> aka. What do I get?
<LI><A href="/tos/tos.php">Terms of Service Agreement</A> aka. The legal stuff.
<LI><A href="hardware.php">The Hardware Behind SourceForge</A>
<LI><A href="delay.php">The 6 hour cron delay</A> (Important)
</UL>

<?php
	$date = getdate(time());
	$hoursleft = 5 - ($date[hours] % 6);
	$minutesleft = 60 - $date[minutes];
?>

<P>The next cron update is in approximately <B><?php print $hoursleft.'</B> hours, <B>'.$minutesleft.'</B> minutes'; ?>.

<P><B>The SourceForge Documentation Project</B>

<P>We have a SourceForge project setup for our own documentation. This
project is maintained by some of our staff members as well as several volunteers.
This allows us to document in several languages and across several platforms.

<P>If you would like to volunteer to help with this project, please contact
one of the project administrators.

<UL>
<LI><A href="http://sfdocs.sourceforge.net"><B>The SourceForge Documentation Project</B> (http://sfdocs.sourceforge.net)</A>
<LI><A href="/project/?group_id=873"><B>SFDocs Project Page</B></A>
</UL>

<?php
$HTML->footer(array());

?>
