<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require "pre.php";    
$HTML->header(array(title=>"Your account email address"));
?>

<P><B>Your SourceForge email address</B>

<P>With the activation of your user account, you were given an
email address at loginname@users.sourceforge.net.

<P>Throughout the site, when your email address is displayed,
the SourceForge address is given rather than the email address
under which you registered. Changing your personal email address
under "Account Maintenance" will change where this mail forwards.

<P>This keeps your own email private, but allows easy access
to all site users through their SourceForge addresses. We also
filter blacklisted spammers from sending mail to this address. 

<P>You may also use your SourceForge email address outside
of the site. Mail will forward as you would expect it to.
Your username will never change, so this email address is
yours to keep.

<P><A href="/docs/site/">[Return to Site Documentation]</A>

<?php
$HTML->footer(array());

?>
