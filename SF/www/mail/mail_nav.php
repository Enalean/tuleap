<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

echo "\n\n<TABLE BORDER=0 WIDTH=\"100%\">".
	"\n<TR><TD NOWRAP>";

echo "<A HREF=\"/project/?group_id=$group_id\">".
	"<IMG SRC=\"/images/ic/ofolder15.png\" HEIGHT=13 WIDTH=15 BORDER=0> &nbsp; ".group_getname($group_id)." Home Page</A><BR>";
echo " &nbsp; &nbsp; <A HREF=\"/mail/?group_id=$group_id\">".
        "<IMG SRC=\"/images/ic/ofolder15.png\" HEIGHT=13 WIDTH=15 BORDER=0> &nbsp; Mailing Lists</A><BR>";
if ($is_admin_page) {
        echo " &nbsp; &nbsp; &nbsp; &nbsp; <A HREF=\"/survey/admin/?group_id=$group_id\">".
                "<IMG SRC=\"/images/ic/ofolder15.png\" HEIGHT=13 WIDTH=15 BORDER=0> &nbsp; Administration</A>";
}
echo "</TD></TR>";
echo "\n</TABLE>\n";

?>
