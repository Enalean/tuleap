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

<h2>Contact Points</h2>

<UL>
<li><b>Assistance and Support Request</b>: all support questions, request for help, new feature requests... related to <?php print $GLOBALS['sys_name']; ?> use should be submitted through the <?php print $GLOBALS['sys_name']; ?>  
<A HREF="/support/?func=addsupport&group_id=1">[ Support Manager ]</A>.<br><br>

<li><b>Defects (aka Bugs)</b>:
If you feel you are encountering a bug or an unusual error of any kind, 
please <A HREF="/bugs/?func=addbug&group_id=1">[ Submit a bug ]</A>.<br><br>

<li>
<b>Technical Questions</b>: all questions on software development tools and techniques (version control, programming, algorithms,...) should be directed to the appropriate <A HREF="/mail/?group_id=1">[ Developers Channel ]</a> whenever possible.<br><br>

<li><b>Other Requests</b>: all other inquiries should be directed to the 
<A href="<?php print $GLOBALS['sys_email_contact']; ?>">[ <?php print $GLOBALS['sys_name']; ?> contact ]</A><br><br>
</ul>

<P>You may also contact any member of the <A href="staff.php">[ <?php print $GLOBALS['sys_name']; ?> Team ]</A> individually directly via email (response time may vary in this case)

<?php
$HTML->footer(array());
?>
