<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//

require ('pre.php');

$HTML->header(array('title'=>'Compile Farm'));

?>

<H3>Using the SourceForge Standard CompileFarm</H3>
<P>
The SourceForge CompileFarm provides Open Source developers with a
convenient way to build and test applications on multiple versions of the
Linux and BSD operating systems over the Internet.
<P>
We are currently offering access to systems running: Caldera,  FreeBSD,
Mandrake, Red Hat Linux, Slackware and SuSE.
<P>
Any registered SourceForge developer may access the standard CompileFarm at
any time by using ssh to connect to "linux.compile.sourceforge.net" for
Linux systems and "bsd.compile.sourceforge.net" for BSD, and logging in
using their web/shell user name and password.
<P>
Registered users desiring access to the Itanium(tm) processor prototype
CompileFarm will need to perform the steps listed below.
<P>
<OL>
<LI>Log in to one of the following machines using your normal SourceForge username and password:
        <UL>
                <LI>linux.compile.sourceforge.net
                <LI>bsd.compile.sourceforge.net
        </UL>
<LI>Read the instructions on login and you can pick your distribution of choice.
<LI>Get your sourcecode in the usual manner, whether from cvs or from a tarball. This compile farm is <B>inside the firewall</B>, so you should access the
shell and cvs servers using their short names, like 'cvs' or 'shell1' instead of
fully qualified domain names.
<LI>Compile. Be sure to play nice as a large number of people are expected to be making use of the system.
</OL>
<P>
<H3>Using the SourceForge Itanium (tm) processor prototype compile farm</H3>
<P>
<OL>
<LI>Agree to the <A HREF="/my/intelagreement.php"><B>Terms &amp; Conditions</B></A> and 
<A HREF="/my/intelagreement.php"><B>submit 
your proposal</B></A> for use of the Itanium (tm) processor prototype compile farm.
<LI>Wait for an approval or rejection message.
<LI>Once approved, allow up to 6 hours for your account to be activated.
<LI>Log in to one of the following machines using your normal SourceForge username and password:
	<UL>
		<LI>tl1.compile.sourceforge.net
		<LI>tl2.compile.sourceforge.net
		<LI>tl3.compile.sourceforge.net
		<LI>Each machine runs independently and home directories are not shared.
	</UL>
<LI>Get your sourcecode in the usual manner, whether from cvs or from a tarball. 
This compile farm is <B>outside the firewall</B>, so you should access the
shell and cvs servers using their fully qualified domain names, like 'shell1.sourceforge.net' instead of
short names as you do in the standard compile farm.
<LI>Compile. Be sure to play nice as a large number of people are expected to be making use of the system.
</OL>
<P>
<H3>Stability</H3>
<P>
The Itanium(tm) processor prototype compile farm is expected to be used heavily and is unsupported by 
the SourceForge admin crew. Please bear with us during the initial testing and ramp-up stage.
<P>
To report problems, visit <A HREF="http://ia-64.sourceforge.net/">http://ia-64.sourceforge.net</A>. Forums 
and mailing lists are being set up to help you communicate with other users.
<P>
<H3>Why the proposal is needed</H3>
<P>
Access to the Itanium(tm) processor prototype compile farm is a valuable
resource.  The project proposal is required for three reasons:
<P>
<OL>
<LI>To ensure that meaningful work will be accomplished
<LI>To regulate demand during the initial spike and ramp of access
requests
<LI>To verify that the terms and conditions have been accepted
</OL>
<P>
Please be patient during the first month of access to the Itanium
processor prototype compile farm.  We expect there to be an initial
spike in demand and we will do our best to approve proposals as soon as
possible.  Initially, we expect the approval process to take a week. 
Eventually, we plan for the approval process to take less than 2 working
days.
<P>
Thank you for your interest, and we look forward to you proposal
submission.

<?php

$HTML->footer(array());

?>
