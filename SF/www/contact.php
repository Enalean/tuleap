<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require "pre.php";    // Initial db and session library, opens session
$HTML->header(array('title'=>'Contact Us'));
?>

<P>You may contact any of the <A href="staff.php"><B>[ staff ]</B></A> directly via email.
<P>
All <B>support questions</B> should be submitted through the 
<A HREF="/support/?func=addsupport&group_id=1">[ Support Manager ]</A>.
<P>
If you feel you are encountering a <B>bug</B> or unusual error of any kind, 
please <A HREF="/bugs/?func=addbug&group_id=1"><B>[ submit a bug ]</B></A>.

<P>All press inquiries should be directed to:
<UL><LI><A href="mailto:eureka@valinux.com">[ Eureka Endo ]</A>
<BR>Press Relations Manager, VA Linux Systems
</UL>

<?php
$HTML->footer(array());
?>
