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

<P>
All <B>support questions</B> related to CodeX use should be submitted through the CodeX 
<A HREF="/support/?func=addsupport&group_id=1">[ Support Manager ]</A>.

<P>
If you feel you are encountering a <B>bug</B> or unusual error of any kind, 
please <A HREF="/bugs/?func=addbug&group_id=1">[ Submit a bug ]</A>.

<P>
All questions on software development tools and techniques (version control, programming, algorithms,...) should be directed to the appropriate <A HREF="/mail/?group_id=1">[ Developers Channel ]</a> if possible.

<P>All other inquiries should be directed to the 
<A href="mailto:codex-contact@codex.xerox.com">[ CodeX contact ]</A>

<P>You may also contact any member of the <A href="staff.php">[ CodeX Team ]</A> individually directly via email.

<?php
$HTML->footer(array());
?>
